<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Services\Chrome\CdpConnection;
use App\Services\Chrome\ChromeProcess;
use App\Services\TemplateEngine;
use Illuminate\Console\Command;
use Throwable;

/**
 * Genera la miniatura de cada plantilla abriéndola en Chrome headless y
 * capturando su hero.
 *
 * POR QUÉ EXISTE. La galería enseñaba las plantillas montando un iframe por
 * cada una. Con 218 activas eso eran 299 documentos y 38 MB de red (cada
 * iframe se descargaba además las fotos y vídeos remotos de su plantilla),
 * y como los iframes del mismo origen comparten el hilo principal del
 * renderizador, el navegador se comía 12,5 s de CPU antes de responder.
 *
 * Una imagen no tiene nada de eso: el navegador la descarga en paralelo, la
 * descodifica en un hilo aparte y con `loading="lazy"` ni siquiera pide las
 * que no se ven. El coste de renderizar las plantillas se paga UNA vez aquí,
 * en paralelo entre varias pestañas, y no en cada visita de cada cliente.
 */
class GenerateTemplateThumbnails extends Command
{
    protected $signature = 'templates:thumbnails
        {--url= : URL base donde responde la app (por defecto APP_URL)}
        {--force : Regenera también las que ya están al día}
        {--tabs=4 : Pestañas en paralelo (una por núcleo disponible)}
        {--width=1280 : Ancho del viewport al renderizar}
        {--height=800 : Alto del viewport al renderizar}
        {--scale=0.5 : Escala de la imagen final (0.5 → 640x400)}
        {--quality=80 : Calidad WebP (1-100)}
        {--settle=2500 : Milisegundos de margen para animaciones y vídeo}
        {--assets=8000 : Tope de espera por fuentes, imágenes y vídeo, en milisegundos}
        {--only= : Solo estos ids, separados por coma}';

    protected $description = 'Genera las miniaturas WebP de la galería de plantillas';

    public function handle(): int
    {
        $baseUrl = rtrim($this->option('url') ?: config('app.url'), '/');
        if (!$this->appResponds($baseUrl)) {
            $this->error("La app no responde en {$baseUrl}");
            $this->line('Arráncala (php artisan serve) o pasa --url=http://127.0.0.1:8000');

            return self::FAILURE;
        }

        $templates = $this->targets();
        if ($templates->isEmpty()) {
            $this->info('No hay plantillas que procesar. Todo al día.');

            return self::SUCCESS;
        }

        $dir = public_path('thumbs');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->error("No se pudo crear {$dir}");

            return self::FAILURE;
        }

        $tabCount = max(1, min((int) $this->option('tabs'), 12));
        $this->info("Generando {$templates->count()} miniaturas con {$tabCount} pestañas en paralelo…");

        $chrome = new ChromeProcess();
        $bar = $this->output->createProgressBar($templates->count());
        $bar->start();

        $done = 0;
        $failed = [];
        $timing = ['arranque' => 0.0, 'navegar' => 0.0, 'carga' => 0.0, 'recursos' => 0.0, 'margen' => 0.0, 'captura' => 0.0];
        $phase = microtime(true);

        try {
            $chrome->start();
            $tabs = [];
            for ($i = 0; $i < $tabCount; $i++) {
                $tabs[] = $this->prepareTab($chrome);
            }
            $timing['arranque'] = microtime(true) - $phase;

            foreach ($templates->chunk($tabCount) as $chunk) {
                $batch = $chunk->values();

                // 1) Se lanza la navegación de TODAS las pestañas antes de
                //    esperar a ninguna: Chrome renderiza cada pestaña en su
                //    propio proceso, así que trabajan a la vez.
                $phase = microtime(true);
                foreach ($batch as $i => $template) {
                    try {
                        $tabs[$i]->call('Page.navigate', [
                            'url' => "{$baseUrl}/plantillas/{$template->id}/render",
                        ], 20);
                    } catch (Throwable $e) {
                        $failed[$template->id] = $e->getMessage();
                    }
                }
                $timing['navegar'] += microtime(true) - $phase;
                $phase = microtime(true);

                // 2) Espera de carga. Se espera a DOMContentLoaded, NO al
                //    evento `load`: varias plantillas traen vídeo remoto de
                //    varios MB y el `load` no dispara hasta que termina de
                //    descargarse (o nunca). El hero ya está pintado antes.
                foreach ($batch as $i => $template) {
                    if (isset($failed[$template->id])) {
                        continue;
                    }
                    try {
                        $tabs[$i]->waitForEvent('Page.domContentEventFired', 20);
                    } catch (Throwable $e) {
                        $failed[$template->id] = $e->getMessage();
                    }
                }

                // 3) Espera de fuentes e imágenes EN PARALELO: se lanza en
                //    todas las pestañas y luego se recogen. Hacerlo pestaña a
                //    pestaña sumaba el tope de cada una (6 × 3,5 s = 21 s por
                //    lote) en vez de solaparlos.
                $timing['carga'] += microtime(true) - $phase;
                $phase = microtime(true);

                $assetIds = [];
                foreach ($batch as $i => $template) {
                    if (!isset($failed[$template->id])) {
                        $assetIds[$i] = $tabs[$i]->send('Runtime.evaluate', $this->assetWaitParams());
                    }
                }
                foreach ($assetIds as $i => $requestId) {
                    try {
                        $tabs[$i]->receive($requestId, ($this->assetCap() / 1000) + 10);
                    } catch (Throwable $e) {
                        // Una foto remota caída no debe tumbar el lote.
                    }
                }
                $timing['recursos'] += microtime(true) - $phase;
                $phase = microtime(true);

                // Margen común para animaciones de entrada y primer fotograma
                // de vídeo. Uno solo para todo el lote, no uno por plantilla.
                usleep((int) $this->option('settle') * 1000);
                $timing['margen'] += microtime(true) - $phase;
                $phase = microtime(true);

                // 4) Capturas, también encadenadas: se piden todas y luego se
                //    recogen, para que Chrome las vaya componiendo a la vez.
                $shotIds = [];
                foreach ($batch as $i => $template) {
                    if (!isset($failed[$template->id])) {
                        $shotIds[$i] = $tabs[$i]->send('Page.captureScreenshot', $this->captureParams());
                    }
                }
                foreach ($batch as $i => $template) {
                    if (isset($shotIds[$i])) {
                        try {
                            $this->store($tabs[$i]->receive($shotIds[$i], 30), $template, $dir);
                            $done++;
                        } catch (Throwable $e) {
                            $failed[$template->id] = $e->getMessage();
                        }
                    }
                    $bar->advance();
                }
                $timing['captura'] += microtime(true) - $phase;
            }
        } catch (Throwable $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('Fallo al generar las miniaturas: ' . $e->getMessage());
            $chrome->stop();

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);
        $chrome->stop();

        $this->info("Miniaturas generadas: {$done}");
        if ($this->output->isVerbose()) {
            $this->line('Reparto del tiempo:');
            foreach ($timing as $label => $seconds) {
                $this->line(sprintf('  %-9s %7.1f s', $label, $seconds));
            }
        }
        if ($failed !== []) {
            $this->warn('Fallaron ' . count($failed) . ':');
            foreach (array_slice($failed, 0, 10, true) as $id => $reason) {
                $this->line("  #{$id}: {$reason}");
            }
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /** Plantillas activas que hay que (re)generar. */
    private function targets()
    {
        $query = Template::query()->where('is_active', true);

        if ($only = $this->option('only')) {
            $ids = array_filter(array_map('trim', explode(',', $only)));
            $query->whereIn('id', $ids);
        }

        $templates = $query->orderBy('id')->get();
        if ($this->option('force') || $this->option('only')) {
            return $templates;
        }

        // Sin --force solo se rehacen las que cambiaron o no tienen imagen.
        return $templates->filter(function (Template $t) {
            $expected = $this->fileName($t);

            return $t->thumbnail !== "/thumbs/{$expected}"
                || !is_file(public_path("thumbs/{$expected}"));
        })->values();
    }

    /**
     * Nombre con la huella del contenido dentro: si la plantilla cambia, la
     * URL cambia y ningún navegador se queda con la miniatura vieja cacheada.
     *
     * La huella incluye la VERSIÓN del motor compartido, no solo su nombre:
     * tocar kinetic.css cambia cómo se ve toda plantilla que lo use, y sin
     * esto sus miniaturas se quedarían obsoletas sin que nada lo detectara.
     */
    private function fingerprint(Template $template): string
    {
        $engine = $template->engine ?? '';
        $engineVersion = TemplateEngine::exists($engine)
            ? TemplateEngine::version($engine, 'css') . TemplateEngine::version($engine, 'js')
            : '';

        return substr(md5(
            ($template->html ?? '') . ($template->css ?? '') .
            ($template->js ?? '') . $engine . $engineVersion
        ), 0, 10);
    }

    private function fileName(Template $template): string
    {
        return "t{$template->id}-{$this->fingerprint($template)}.webp";
    }

    private function prepareTab(ChromeProcess $chrome): CdpConnection
    {
        $tab = $chrome->newTab();
        $tab->call('Page.enable');
        $tab->call('Runtime.enable');
        $tab->call('Emulation.setDeviceMetricsOverride', [
            'width'             => (int) $this->option('width'),
            'height'            => (int) $this->option('height'),
            'deviceScaleFactor' => 1,
            'mobile'            => false,
        ]);

        return $tab;
    }

    /**
     * Espera a que tipografías e imágenes estén listas. Sin esto la captura
     * puede salir con la fuente de reserva o con huecos donde van las fotos.
     */
    private function assetCap(): int
    {
        return max(500, (int) $this->option('assets'));
    }

    /**
     * Espera a que tipografías e imágenes estén listas, contra un tope duro:
     * document.fonts.ready no resuelve nunca si una fuente remota no llega, y
     * sin techo una sola plantilla dejaba colgado el lote entero.
     */
    private function assetWaitParams(): array
    {
        $cap = $this->assetCap();

        $script = <<<JS
        (async () => {
            const cap = new Promise(resolve => setTimeout(resolve, {$cap}));
            const fonts = document.fonts
                ? document.fonts.ready.catch(() => {})
                : Promise.resolve();
            const images = Promise.all(
                [...document.images]
                    .filter(img => !img.complete)
                    .map(img => new Promise(resolve => {
                        img.addEventListener('load', resolve, { once: true });
                        img.addEventListener('error', resolve, { once: true });
                    }))
            );
            // Los héroes con vídeo de fondo llegan con preload="none" y los
            // dispara el motor al entrar en viewport. En la captura headless eso
            // NO basta: si solo esperamos 'loadeddata' de forma pasiva, en un
            // vídeo con preload="none" el navegador nunca lo pide, el evento no
            // llega, gana el timeout y la miniatura sale con solo el degradado
            // (le pasaba a las 84 plantillas con vídeo de Pexels). La solución es
            // FORZAR la carga a mano: preload='auto' + load() + play(), y esperar
            // a que haya un primer fotograma (readyState>=2).
            const videos = Promise.all(
                [...document.querySelectorAll('video')]
                    .map(v => new Promise(resolve => {
                        if (v.readyState >= 2) { resolve(); return; }
                        v.addEventListener('loadeddata', resolve, { once: true });
                        v.addEventListener('error', resolve, { once: true });
                        try {
                            v.preload = 'auto';
                            v.muted = true;            // autoplay solo si va silenciado
                            v.load();
                            const p = v.play();
                            if (p && p.catch) p.catch(() => {});
                        } catch (e) {}
                    }))
            );
            await Promise.race([Promise.all([fonts, images, videos]), cap]);
            return true;
        })()
        JS;

        return [
            'expression'    => $script,
            'awaitPromise'  => true,
            'returnByValue' => true,
        ];
    }

    private function captureParams(): array
    {
        return [
            'format'  => 'webp',
            'quality' => (int) $this->option('quality'),
            // Se recorta el hero, no la página entera: en la rejilla lo que
            // identifica una plantilla es su cabecera, y encoger la página
            // completa dejaba el texto ilegible.
            'clip' => [
                'x'      => 0,
                'y'      => 0,
                'width'  => (int) $this->option('width'),
                'height' => (int) $this->option('height'),
                'scale'  => (float) $this->option('scale'),
            ],
            'captureBeyondViewport' => false,
        ];
    }

    private function store(array $result, Template $template, string $dir): void
    {
        $data = base64_decode($result['data'] ?? '', true);
        if ($data === false || $data === '') {
            throw new \RuntimeException('Chrome devolvió una captura vacía.');
        }

        $fileName = $this->fileName($template);
        if (@file_put_contents("{$dir}/{$fileName}", $data) === false) {
            throw new \RuntimeException("No se pudo escribir {$fileName}");
        }

        // Fuera las versiones antiguas de esta misma plantilla.
        foreach (glob("{$dir}/t{$template->id}-*.webp") ?: [] as $old) {
            if (basename($old) !== $fileName) {
                @unlink($old);
            }
        }

        $template->forceFill(['thumbnail' => "/thumbs/{$fileName}"])->saveQuietly();
    }

    private function appResponds(string $baseUrl): bool
    {
        $context = stream_context_create(['http' => [
            'method'        => 'GET',
            'timeout'       => 5,
            'ignore_errors' => true,
        ]]);

        return @file_get_contents("{$baseUrl}/up", false, $context) !== false;
    }
}
