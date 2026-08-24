<?php

namespace App\Console\Commands;

use App\Services\TemplateEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Escribe los motores compartidos como archivos físicos en public/engine/.
 *
 * POR QUÉ. Servirlos desde una ruta de Laravel obligaba a arrancar el framework
 * entero —sesión, cookies, CSRF— para devolver un CSS: medido, 7,3 segundos por
 * petición. Y la galería monta decenas de miniaturas que lo piden a la vez.
 * Como archivo en public/ lo entrega el servidor web directamente, sin PHP de
 * por medio, y con caché inmutable el navegador solo lo pide una vez.
 *
 * El nombre lleva la huella del código, así que al cambiar el motor la URL
 * cambia y nadie se queda con una versión vieja cacheada un año.
 */
class PublishEngines extends Command
{
    protected $signature = 'engines:publish {--force : Reescribe aunque ya exista}';
    protected $description = 'Publica los motores de plantilla como archivos estáticos en public/engine';

    public function handle(): int
    {
        $dir = public_path('engine');
        File::ensureDirectoryExists($dir);

        $written = 0;
        foreach (TemplateEngine::names() as $engine) {
            foreach (['css', 'js'] as $type) {
                $code = TemplateEngine::code($engine, $type);
                if ($code === '') {
                    continue;
                }
                $path = $dir . '/' . TemplateEngine::fileName($engine, $type);
                if (!file_exists($path) || $this->option('force')) {
                    File::put($path, $code);
                    $written++;
                }
                $this->line(sprintf('  %-22s %6.1f KB', basename($path), strlen($code) / 1024));
            }
        }

        // Limpia versiones antiguas: el nombre lleva huella, así que se
        // acumularían para siempre en cada cambio del motor.
        $vigentes = [];
        foreach (TemplateEngine::names() as $engine) {
            foreach (['css', 'js'] as $type) {
                $vigentes[] = TemplateEngine::fileName($engine, $type);
            }
        }
        $borrados = 0;
        foreach (File::files($dir) as $f) {
            if (!in_array($f->getFilename(), $vigentes, true)) {
                File::delete($f->getPathname());
                $borrados++;
            }
        }

        $this->info("Motores publicados: {$written} escritos, {$borrados} obsoletos eliminados.");

        return self::SUCCESS;
    }
}
