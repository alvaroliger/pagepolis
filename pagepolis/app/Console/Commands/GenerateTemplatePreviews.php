<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Services\Chrome\CdpConnection;
use App\Services\Chrome\ChromeProcess;
use App\Services\TemplateEngine;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Genera una MINIATURA EN VÍDEO por plantilla: abre el render en Chrome
 * headless, captura una ráfaga de fotogramas del hero en movimiento y los
 * encadena con ffmpeg en un MP4 pequeño y en bucle.
 *
 * POR QUÉ. La rejilla necesitaba enseñar el movimiento de TODAS las plantillas
 * a la vez y con scroll fluido. Con iframes es imposible: los del mismo origen
 * comparten el único hilo del renderizador y 9 animándose lo ahogan. Un <video>
 * en cambio se descodifica FUERA del hilo principal, así que el navegador puede
 * reproducir muchos a la vez sin tocar la fluidez del scroll. El coste de
 * renderizar la plantilla se paga UNA vez aquí, no en cada visita.
 *
 * El `thumbnail` estático (templates:thumbnails) se conserva: es el `poster` del
 * vídeo, de modo que la tarjeta enseña la imagen real al instante y nunca se ve
 * negro mientras el vídeo carga. En la rejilla solo se reproducen los vídeos
 * visibles (IntersectionObserver): los de fuera de pantalla ni se descargan.
 *
 * ffmpeg se invoca por WSL (no hay binario nativo en el Windows de dev); las
 * rutas se traducen C:\ -> /mnt/c/.
 */
class GenerateTemplatePreviews extends Command
{
    protected $signature = 'templates:previews
        {--url= : URL base donde responde la app (por defecto APP_URL)}
        {--force : Regenera también las que ya están al día}
        {--tabs=4 : Pestañas en paralelo (una por núcleo disponible)}
        {--width=1280 : Ancho del viewport al renderizar}
        {--height=800 : Alto del viewport al renderizar}
        {--scale=0.5 : Escala del vídeo final (0.5 → 640x400)}
        {--duration=2.5 : Segundos de animación a capturar (con boomerang, el bucle dura el doble)}
        {--maxfps=30 : Tope de fotogramas por segundo (Chrome pinta a su ritmo real hasta este tope)}
        {--quality=70 : Calidad JPEG de cada fotograma capturado (1-100)}
        {--crf=30 : Calidad H.264 del MP4 (menor = mejor y más pesado)}
        {--settle=1800 : Milisegundos de margen antes de empezar a capturar}
        {--assets=8000 : Tope de espera por fuentes, imágenes y vídeo, en milisegundos}
        {--loop=boomerang : boomerang (ida+vuelta, sin costura) o plain}
        {--ffmpeg= : Invocación de ffmpeg (por defecto: autodetección, "wsl ffmpeg" en Windows)}
        {--only= : Solo estos ids, separados por coma}';

    protected $description = 'Genera las miniaturas en vídeo (MP4 en bucle) de la galería de plantillas';

    public function handle(): int
    {
        $baseUrl = rtrim($this->option('url') ?: config('app.url'), '/');
        if (!$this->appResponds($baseUrl)) {
            $this->error("La app no responde en {$baseUrl}");
            $this->line('Arráncala o pasa --url=http://127.0.0.1:8000');

            return self::FAILURE;
        }

        $ffmpeg = $this->resolveFfmpeg();
        if ($ffmpeg === null) {
            $this->error('No encuentro ffmpeg (ni nativo ni en WSL). Instálalo o pasa --ffmpeg=.');

            return self::FAILURE;
        }
        $this->line('ffmpeg: ' . implode(' ', $ffmpeg));

        $templates = $this->targets();
        if ($templates->isEmpty()) {
            $this->info('No hay plantillas que procesar. Todo al día.');

            return self::SUCCESS;
        }

        $outDir = public_path('previews');
        $tmpDir = storage_path('app/preview-frames');
        foreach ([$outDir, $tmpDir] as $d) {
            if (!is_dir($d) && !@mkdir($d, 0755, true) && !is_dir($d)) {
                $this->error("No se pudo crear {$d}");

                return self::FAILURE;
            }
        }

        $tabCount = max(1, min((int) $this->option('tabs'), 12));
        $duration = max(0.5, (float) $this->option('duration'));
        $maxFps = max(1, (int) $this->option('maxfps'));

        $this->info("Generando {$templates->count()} vídeos ({$duration}s @ hasta {$maxFps}fps reales) con {$tabCount} pestañas…");

        $chrome = new ChromeProcess();
        $bar = $this->output->createProgressBar($templates->count());
        $bar->start();

        $done = 0;
        $failed = [];

        try {
            $chrome->start();
            $tabs = [];
            for ($i = 0; $i < $tabCount; $i++) {
                $tabs[] = $this->prepareTab($chrome);
            }

            foreach ($templates->chunk($tabCount) as $chunk) {
                $batch = $chunk->values();

                // 1) Navegar todas las pestañas antes de esperar a ninguna
                //    (cada pestaña renderiza en su propio proceso, en paralelo).
                foreach ($batch as $i => $template) {
                    try {
                        $tabs[$i]->call('Page.navigate', [
                            'url' => "{$baseUrl}/plantillas/{$template->id}/render",
                        ], 20);
                    } catch (Throwable $e) {
                        $failed[$template->id] = $e->getMessage();
                    }
                }

                // 2) DOMContentLoaded (no 'load': el vídeo remoto no lo dispara).
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

                // 3) Fuentes, imágenes y primer fotograma de vídeo, en paralelo.
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
                        // Un recurso remoto caído no debe tumbar el lote.
                    }
                }

                // 4) Margen para que arranquen las animaciones de entrada.
                usleep((int) $this->option('settle') * 1000);

                // 5) Capturar cada pestaña por SCREENCAST: Chrome empuja los
                //    fotogramas a su RITMO REAL de pintado (sin una petición por
                //    fotograma), así el movimiento sale suave. Es secuencial por
                //    pestaña (un screencast a la vez por socket), pero la carga y
                //    el margen de arriba sí fueron en paralelo; y mientras capturo
                //    una, las demás siguen animando (flags anti-throttling), de
                //    modo que su captura arranca igual de viva.
                foreach ($batch as $i => $template) {
                    if (isset($failed[$template->id])) {
                        $bar->advance();

                        continue;
                    }
                    $fd = "{$tmpDir}/{$template->id}";
                    $this->emptyDir($fd);
                    @mkdir($fd, 0755, true);

                    try {
                        $n = $this->captureScreencast($tabs[$i], $fd, $duration);
                        if ($n < 2) {
                            throw new \RuntimeException('El screencast no entregó fotogramas.');
                        }
                        // fps REAL de captura = lo que pintó Chrome. El MP4 se
                        // codifica a ESE ritmo, así va a la MISMA velocidad que la
                        // animación real. Si supera el tope, se DESCARTAN fotogramas
                        // (nunca se ralentiza): suave hasta maxfps y sin cámara lenta.
                        $inFps  = max(1, (int) round($n / $duration));
                        $outFps = max(1, min($maxFps, $inFps));
                        $this->encode($ffmpeg, $fd, $template, $outDir, $inFps, $outFps);
                        $done++;
                    } catch (Throwable $e) {
                        $failed[$template->id] = $e->getMessage();
                    }
                    $this->emptyDir($fd);
                    @rmdir($fd);
                    $bar->advance();
                }
            }
        } catch (Throwable $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('Fallo al generar los vídeos: ' . $e->getMessage());
            $chrome->stop();

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);
        $chrome->stop();

        $this->info("Vídeos generados: {$done}");
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

        return $templates->filter(function (Template $t) {
            $expected = $this->fileName($t);

            return $t->preview_video !== "/previews/{$expected}"
                || !is_file(public_path("previews/{$expected}"));
        })->values();
    }

    /**
     * Huella del contenido (misma lógica que las miniaturas): si la plantilla o
     * el motor cambian, el nombre cambia y ningún navegador sirve el vídeo viejo.
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
        return "t{$template->id}-{$this->fingerprint($template)}.mp4";
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

    private function assetCap(): int
    {
        return max(500, (int) $this->option('assets'));
    }

    /**
     * Espera (ya en primer plano) a que los vídeos estén reproduciéndose de
     * verdad: currentTime avanzando. Tope de 2,5 s para no colgarse con un
     * vídeo remoto que no llega. Sin vídeos, resuelve al instante.
     */
    private function playWaitParams(): array
    {
        $script = <<<JS
        (async () => {
            const vids = [...document.querySelectorAll('video')];
            if (!vids.length) return true;
            const cap = new Promise(r => setTimeout(r, 2500));
            await Promise.race([
                Promise.all(vids.map(v => new Promise(resolve => {
                    try { v.muted = true; v.loop = true; const p = v.play(); if (p && p.catch) p.catch(() => {}); } catch (e) {}
                    const start = v.currentTime;
                    const iv = setInterval(() => {
                        if (v.currentTime > start + 0.15) { clearInterval(iv); resolve(); }
                    }, 40);
                    v.addEventListener('error', () => { clearInterval(iv); resolve(); }, { once: true });
                }))),
                cap,
            ]);
            return true;
        })()
        JS;

        return [
            'expression'    => $script,
            'awaitPromise'  => true,
            'returnByValue' => true,
        ];
    }

    private function assetWaitParams(): array
    {
        $cap = $this->assetCap();

        $script = <<<JS
        (async () => {
            const cap = new Promise(resolve => setTimeout(resolve, {$cap}));
            const fonts = document.fonts ? document.fonts.ready.catch(() => {}) : Promise.resolve();
            const images = Promise.all(
                [...document.images].filter(img => !img.complete).map(img => new Promise(resolve => {
                    img.addEventListener('load', resolve, { once: true });
                    img.addEventListener('error', resolve, { once: true });
                }))
            );
            // Aquí solo se espera al PRIMER fotograma (loadeddata). Que el vídeo
            // esté REPRODUCIÉNDOSE se asegura después, ya en primer plano, justo
            // antes de grabar (en headless Chrome solo decodifica el vídeo de la
            // pestaña de delante, así que esperar a que avance en segundo plano
            // no serviría y agotaría el tope).
            const videos = Promise.all(
                [...document.querySelectorAll('video')].map(v => new Promise(resolve => {
                    if (v.readyState >= 2) { resolve(); return; }
                    v.addEventListener('loadeddata', resolve, { once: true });
                    v.addEventListener('error', resolve, { once: true });
                    try { v.preload = 'auto'; v.muted = true; v.loop = true; v.load(); const p = v.play(); if (p && p.catch) p.catch(() => {}); } catch (e) {}
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

    /**
     * Captura la animación de una pestaña por screencast durante $duration
     * segundos, escribiendo cada fotograma en $frameDir. Devuelve cuántos
     * fotogramas se guardaron. Hay que ACK cada fotograma o Chrome deja de
     * enviarlos; los fotogramas llegan al ritmo real de pintado de la página.
     */
    private function captureScreencast(CdpConnection $tab, string $frameDir, float $duration): int
    {
        $scale = (float) $this->option('scale');
        $tab->clearPending();
        // En headless Chrome solo compone (pinta) Y decodifica vídeo la pestaña
        // en primer plano; sin esto el screencast no emite fotogramas y los
        // vídeos ni avanzan. Como capturo de una en una, traigo cada pestaña al
        // frente justo antes de grabarla.
        try {
            $tab->call('Page.bringToFront', [], 5);
        } catch (Throwable $e) {
            // si no está soportado, seguimos: puede que ya pinte
        }
        // Ya en primer plano: asegurar que los vídeos están REPRODUCIÉNDOSE (que
        // currentTime avance) antes de grabar, para captar movimiento y no el
        // vídeo aún bufferándose (si no, salía a 3-4 fps).
        try {
            $tab->call('Runtime.evaluate', $this->playWaitParams(), 6);
        } catch (Throwable $e) {
            // sin vídeo o vídeo caído: se graba lo que haya (animación CSS/WebGL)
        }
        $tab->call('Page.startScreencast', [
            'format'        => 'jpeg',
            'quality'       => (int) $this->option('quality'),
            'everyNthFrame' => 1,
            'maxWidth'      => (int) round((int) $this->option('width') * $scale),
            'maxHeight'     => (int) round((int) $this->option('height') * $scale),
        ]);

        $deadline = microtime(true) + $duration;
        $n = 0;
        while (microtime(true) < $deadline) {
            $msg = $tab->nextEvent('Page.screencastFrame', $deadline - microtime(true));
            if ($msg === null) {
                break;
            }
            $params = $msg['params'] ?? [];
            if (isset($params['sessionId'])) {
                // ACK cuanto antes para que Chrome siga empujando fotogramas.
                $tab->send('Page.screencastFrameAck', ['sessionId' => $params['sessionId']]);
            }
            $data = base64_decode($params['data'] ?? '', true);
            if ($data !== false && $data !== '') {
                file_put_contents(sprintf('%s/f%05d.jpg', $frameDir, $n), $data);
                $n++;
            }
        }

        try {
            $tab->call('Page.stopScreencast', [], 5);
        } catch (Throwable $e) {
            // ignorable
        }
        $tab->clearPending(); // fuera respuestas de los acks antes de la siguiente

        return $n;
    }

    /**
     * Encadena los fotogramas de $frameDir en un MP4 en bucle con ffmpeg.
     * $inFps = ritmo real al que se capturaron (marca la velocidad); $outFps =
     * ritmo del MP4 (si es menor, ffmpeg descarta fotogramas, sin ralentizar).
     */
    private function encode(array $ffmpeg, string $frameDir, Template $template, string $outDir, int $inFps, int $outFps): void
    {
        if (count(glob("{$frameDir}/f*.jpg") ?: []) < 2) {
            throw new \RuntimeException('No se capturaron fotogramas suficientes.');
        }

        $fileName = $this->fileName($template);
        // glob en vez de f%03d.jpg: tolera un fotograma perdido sin cortar el
        // vídeo (el patrón numérico se para en el primer hueco).
        $inGlob = $this->wslPath("{$frameDir}/f*.jpg");
        $outPath = $this->wslPath("{$outDir}/{$fileName}");

        // yuv420p + dimensiones pares: imprescindible para que reproduzca en
        // todos los navegadores. Boomerang (ida+vuelta) = bucle sin costura.
        // `fps={outFps}` al final baja el ritmo descartando fotogramas si la
        // captura fue más rápida que el tope, MANTENIENDO la duración (velocidad
        // real). yuv420p + dimensiones pares: reproduce en todos los navegadores.
        $even = 'scale=trunc(iw/2)*2:trunc(ih/2)*2';
        $filter = $this->option('loop') === 'plain'
            ? "[0:v]{$even},fps={$outFps},format=yuv420p[v]"
            : "[0:v]{$even},split[f][r];[r]reverse[rr];[f][rr]concat=n=2:v=1,fps={$outFps},format=yuv420p[v]";

        $args = array_merge($ffmpeg, [
            '-y', '-hide_banner', '-loglevel', 'error',
            '-framerate', (string) $inFps,
            '-pattern_type', 'glob', '-i', $inGlob,
            '-filter_complex', $filter,
            '-map', '[v]',
            '-c:v', 'libx264', '-profile:v', 'baseline', '-level', '3.0',
            '-pix_fmt', 'yuv420p', '-crf', (string) (int) $this->option('crf'),
            '-preset', 'veryfast', '-movflags', '+faststart', '-an',
            $outPath,
        ]);

        $proc = new Process($args);
        $proc->setTimeout(120);
        $proc->run();
        if (!$proc->isSuccessful()) {
            throw new \RuntimeException('ffmpeg: ' . trim($proc->getErrorOutput() ?: $proc->getOutput()));
        }
        if (!is_file("{$outDir}/{$fileName}")) {
            throw new \RuntimeException('ffmpeg no escribió el MP4.');
        }

        // Fuera las versiones antiguas de esta misma plantilla.
        foreach (glob("{$outDir}/t{$template->id}-*.mp4") ?: [] as $old) {
            if (basename($old) !== $fileName) {
                @unlink($old);
            }
        }

        $template->forceFill(['preview_video' => "/previews/{$fileName}"])->saveQuietly();
    }

    /**
     * Determina cómo invocar ffmpeg. En este Windows solo hay binario en WSL,
     * así que se usa "wsl ffmpeg"; en Linux/Mac, ffmpeg nativo.
     */
    private function resolveFfmpeg(): ?array
    {
        if ($custom = $this->option('ffmpeg')) {
            return preg_split('/\s+/', trim($custom));
        }
        foreach ([['ffmpeg'], ['wsl', 'ffmpeg']] as $cand) {
            $p = new Process(array_merge($cand, ['-version']));
            $p->setTimeout(15);
            try {
                $p->run();
                if ($p->isSuccessful()) {
                    return $cand;
                }
            } catch (Throwable $e) {
                // siguiente candidato
            }
        }

        return null;
    }

    /** C:\a\b -> /mnt/c/a/b (para pasar rutas de Windows a ffmpeg de WSL). */
    private function wslPath(string $winPath): string
    {
        $p = str_replace('\\', '/', $winPath);
        if (preg_match('#^([A-Za-z]):/(.*)$#', $p, $m)) {
            return '/mnt/' . strtolower($m[1]) . '/' . $m[2];
        }

        return $p;
    }

    private function emptyDir(string $dir): void
    {
        foreach (glob("{$dir}/*") ?: [] as $f) {
            @unlink($f);
        }
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
