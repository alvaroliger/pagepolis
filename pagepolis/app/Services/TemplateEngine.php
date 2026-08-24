<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Motores compartidos de las plantillas (kinetic.css/js, base.css, engine.js…).
 *
 * POR QUÉ EXISTE. Hasta ahora cada plantilla guardaba en la base de datos una
 * COPIA del motor completo: ~110 KB por fila. Con 170 plantillas eran 17,6 MB
 * de los que 14 eran el mismo código repetido, y cada proyecto que un cliente
 * creaba se llevaba otra copia. Además la galería llegó a tumbar la petición
 * (30 s de tiempo máximo) solo por serializar todo eso a JSON.
 *
 * Ahora el motor vive UNA vez en disco y las filas guardan únicamente su parte
 * propia. Al publicar, el motor se enlaza como archivo estático con caché
 * larga, así que el navegador se lo descarga una vez y lo reutiliza entre
 * páginas: menos espacio en el servidor, menos bytes por visita y primera
 * pintada más rápida.
 */
class TemplateEngine
{
    /**
     * Fuentes de Google que antes vivían como @import dentro de kinetic.css.
     * Un @import es bloqueante en cascada: el navegador no aplica ni una sola
     * regla del resto del archivo hasta que ese import se resuelve. Dentro de
     * un iframe (miniaturas de la galería, vista previa) eso deja el motor
     * entero sin aplicar — comprobado con una prueba aislada. Como <link>
     * independiente, la fuente carga en paralelo y no bloquea nada.
     */
    public const FONT_LINK = '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700'
        . '&family=Instrument+Serif:ital@0;1&family=Inter:wght@300..800&family=JetBrains+Mono:wght@400;500'
        . '&family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap">';

    /**
     * Un @import de Google Fonts dentro del CSS PROPIO de una plantilla tiene
     * el mismo problema. Se extrae y se devuelve como <link> aparte, dejando
     * el resto del CSS limpio para que se aplique sin esperar a la fuente.
     */
    public static function extractFontImports(string $css): array
    {
        $links = '';
        $pattern = '/@import\s+url\(([\'"]?)(https:\/\/fonts\.googleapis\.com[^\'")]+)\1\)\s*;?/i';
        $clean = preg_replace_callback(
            $pattern,
            function ($m) use (&$links) {
                $links .= '<link rel="stylesheet" href="' . e($m[2]) . '">';
                return '';
            },
            $css
        ) ?? $css;

        return [$links, $clean];
    }
    /** Piezas que compone cada motor, en orden. */
    private const PARTS = [
        'kinetic' => [
            'css' => ['kinetic.css'],
            'js'  => ['kinetic.js', 'kx3d.js'],
        ],
        'classic' => [
            'css' => ['base.css'],
            'js'  => ['engine.js', 'hero3d.js', 'hero-photo.js'],
        ],
        // 'standalone' no tiene motor: la plantilla trae todo su código.
    ];

    public static function names(): array
    {
        return array_keys(self::PARTS);
    }

    public static function exists(?string $engine): bool
    {
        return $engine !== null && isset(self::PARTS[$engine]);
    }

    /**
     * Código del motor ya concatenado. Se cachea en memoria del proceso y en el
     * caché de la aplicación porque se pide en cada página publicada.
     */
    public static function code(string $engine, string $type): string
    {
        if (!isset(self::PARTS[$engine][$type])) {
            return '';
        }

        return Cache::rememberForever("engine:{$engine}:{$type}:" . self::version($engine, $type), function () use ($engine, $type) {
            $dir = database_path('templates');
            $out = '';
            foreach (self::PARTS[$engine][$type] as $file) {
                $code = @file_get_contents("{$dir}/{$file}");
                if ($code === false) {
                    continue;
                }
                // Cada pieza va aislada: si una lanza, las demás siguen vivas.
                $out .= $type === 'js' ? "try{\n{$code}\n}catch(e){}\n" : "{$code}\n\n";
            }

            return $out;
        });
    }

    /**
     * Huella del motor, para que la URL cambie cuando cambie el código y los
     * navegadores no se queden con una versión vieja cacheada un año.
     */
    public static function version(string $engine, string $type): string
    {
        $dir = database_path('templates');
        $stamp = '';
        foreach (self::PARTS[$engine][$type] ?? [] as $file) {
            $stamp .= (string) @filemtime("{$dir}/{$file}");
        }

        return substr(md5($stamp), 0, 8);
    }

    /** Nombre del archivo publicado, con la huella del código incrustada. */
    public static function fileName(string $engine, string $type): string
    {
        return "{$engine}.{$type}" === '' ? '' : "{$engine}." . self::version($engine, $type) . ".{$type}";
    }

    /**
     * URL del motor. Apunta al archivo estático de public/engine si existe
     * (lo entrega el servidor web sin arrancar PHP) y, si aún no se ha
     * publicado, cae a la ruta dinámica para no dejar la web sin estilos.
     */
    public static function url(string $engine, string $type): string
    {
        // Ruta RELATIVA a propósito. Con URL absoluta se usaría APP_URL, que
        // puede no coincidir con el host por el que entra el visitante
        // (localhost frente a 127.0.0.1, o dominio propio frente al de la app).
        // Las miniaturas viven en iframes con sandbox —origen opaco— y ahí un
        // CSS de otro origen no se aplica: la plantilla salía sin estilos.
        $file = self::fileName($engine, $type);
        if (is_file(public_path("engine/{$file}"))) {
            return "/engine/{$file}";
        }

        return "/engine/{$engine}.{$type}?v=" . self::version($engine, $type);
    }

    /**
     * Etiquetas <link>/<script> del motor de una plantilla o proyecto.
     * Si no usa motor compartido devuelve cadena vacía.
     */
    public static function tags(?string $engine): array
    {
        if (!self::exists($engine)) {
            return ['css' => '', 'js' => ''];
        }

        return [
            // Las fuentes van SIEMPRE delante del CSS del motor: si el propio
            // motor las llevara con @import, bloquearía su propia aplicación.
            'css' => self::FONT_LINK . '<link rel="stylesheet" href="' . e(self::url($engine, 'css')) . '">',
            'js'  => '<script src="' . e(self::url($engine, 'js')) . '" defer></script>',
        ];
    }

    /**
     * Composición en línea (motor + propio). Solo para descargas del sitio o
     * para vistas que no pueden enlazar archivos externos.
     */
    public static function inline(?string $engine, string $own, string $type): string
    {
        $engineCode = self::exists($engine) ? self::code($engine, $type) : '';

        return trim($engineCode . "\n" . $own);
    }
}
