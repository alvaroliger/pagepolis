<?php

namespace App\Services;

/**
 * Utilidades de color para el router de intenciones: convierte nombres en español
 * (y códigos hex) a hex, aclara/oscurece y calcula el color de texto con contraste.
 */
class Color
{
    /** Colores base en español (claves sin acentos ni espacios). */
    private const NAMES = [
        'rojo' => '#dc2626', 'roja' => '#dc2626',
        'azul' => '#2563eb',
        'azulmarino' => '#1e3a5f', 'navy' => '#1e3a5f', 'marino' => '#1e3a5f',
        'celeste' => '#38bdf8', 'cian' => '#06b6d4', 'turquesa' => '#14b8a6',
        'verde' => '#16a34a',
        'amarillo' => '#eab308', 'amarilla' => '#eab308',
        'naranja' => '#ea580c',
        'morado' => '#7c3aed', 'morada' => '#7c3aed', 'violeta' => '#7c3aed',
        'purpura' => '#7c3aed', 'lila' => '#a78bfa', 'malva' => '#a78bfa',
        'rosa' => '#ec4899', 'fucsia' => '#d946ef', 'salmon' => '#fb7185',
        'coral' => '#f97316',
        'negro' => '#111111', 'negra' => '#111111',
        'blanco' => '#ffffff', 'blanca' => '#ffffff',
        'gris' => '#6b7280', 'grisclaro' => '#d1d5db', 'grisoscuro' => '#374151',
        'marron' => '#92400e', 'marronclaro' => '#b45309', 'cafe' => '#7b4b27',
        'beige' => '#e8dcc8', 'crema' => '#faf3e0', 'arena' => '#d8c3a5',
        'dorado' => '#d4af37', 'oro' => '#d4af37', 'plata' => '#c0c0c0', 'plateado' => '#c0c0c0',
        'lima' => '#84cc16', 'menta' => '#34d399',
        'indigo' => '#4f46e5', 'granate' => '#7f1d1d', 'burdeos' => '#7f1d1d', 'vino' => '#7f1d1d',
        'ocre' => '#b8923f', 'mostaza' => '#d4a017',
    ];

    /**
     * Detecta un color en un texto ya normalizado (sin acentos, minúsculas) y lo
     * devuelve en hex. Soporta "#rrggbb", nombres y modificadores "claro/oscuro".
     */
    public static function detect(string $text): ?string
    {
        // Código hex explícito.
        if (preg_match('/#([0-9a-f]{6}|[0-9a-f]{3})\b/', $text, $m)) {
            return self::normalizeHex('#' . $m[1]);
        }

        $lighten = (bool) preg_match('/\b(claro|clara|clarito|suave|pastel)\b/', $text);
        $darken  = (bool) preg_match('/\b(oscuro|oscura|oscurito|intenso)\b/', $text);

        // "gris claro"/"gris oscuro" como nombre compuesto primero.
        foreach (['grisclaro' => 'gris claro', 'grisoscuro' => 'gris oscuro', 'marronclaro' => 'marron claro', 'azulmarino' => 'azul marino'] as $key => $phrase) {
            if (str_contains($text, $phrase)) {
                return self::NAMES[$key];
            }
        }

        foreach (self::NAMES as $name => $hex) {
            // Permite plural/género: azul→azules, rojo→rojos, negra→negras…
            if (preg_match('/\b' . preg_quote($name, '/') . '(?:s|es|a|as|os)?\b/', $text)) {
                if ($lighten) return self::lighten($hex, 0.35);
                if ($darken)  return self::darken($hex, 0.30);
                return $hex;
            }
        }

        return null;
    }

    public static function normalizeHex(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return '#' . strtolower($hex);
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex), '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function fromRgb(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    public static function lighten(string $hex, float $amt): string
    {
        [$r, $g, $b] = self::rgb($hex);
        return self::fromRgb((int) ($r + (255 - $r) * $amt), (int) ($g + (255 - $g) * $amt), (int) ($b + (255 - $b) * $amt));
    }

    public static function darken(string $hex, float $amt): string
    {
        [$r, $g, $b] = self::rgb($hex);
        return self::fromRgb((int) ($r * (1 - $amt)), (int) ($g * (1 - $amt)), (int) ($b * (1 - $amt)));
    }

    /** Color de texto legible (#fff o casi negro) según la luminancia del fondo. */
    public static function contrast(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $lum > 0.6 ? '#111111' : '#ffffff';
    }
}
