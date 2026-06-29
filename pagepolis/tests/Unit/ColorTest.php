<?php

namespace Tests\Unit;

use App\Services\Color;
use PHPUnit\Framework\TestCase;

/**
 * CAJA BLANCA — lógica interna del helper de color (nombres ES, hex, claro/oscuro,
 * contraste, aclarar/oscurecer). Sin dependencias de Laravel.
 */
class ColorTest extends TestCase
{
    public function test_named_colors(): void
    {
        $this->assertSame('#dc2626', Color::detect('quiero rojo'));
        $this->assertSame('#2563eb', Color::detect('pon azul'));
        $this->assertSame('#16a34a', Color::detect('el verde'));
        $this->assertSame('#111111', Color::detect('negro total'));
        $this->assertSame('#7c3aed', Color::detect('morado'));
    }

    public function test_plural_and_gender(): void
    {
        $this->assertSame('#2563eb', Color::detect('botones azules'));
        $this->assertSame('#dc2626', Color::detect('letras rojas'));
        $this->assertSame('#16a34a', Color::detect('tonos verdes'));
    }

    public function test_hex_codes(): void
    {
        $this->assertSame('#aabbcc', Color::detect('usa #abc'));
        $this->assertSame('#1a2b3c', Color::detect('color #1a2b3c por favor'));
    }

    public function test_light_and_dark_modifiers(): void
    {
        $this->assertSame('#7199f2', Color::detect('azul claro'));    // lighten 35%
        $this->assertSame('#1945a4', Color::detect('azul oscuro'));   // darken 30%
    }

    public function test_compound_names(): void
    {
        $this->assertSame('#1e3a5f', Color::detect('azul marino'));
        $this->assertSame('#d1d5db', Color::detect('gris claro'));
    }

    public function test_no_color_returns_null(): void
    {
        $this->assertNull(Color::detect('hazme un logo bonito'));
        $this->assertNull(Color::detect(''));
    }

    public function test_contrast(): void
    {
        $this->assertSame('#111111', Color::contrast('#ffffff'));
        $this->assertSame('#ffffff', Color::contrast('#000000'));
        $this->assertSame('#ffffff', Color::contrast('#2563eb'));   // azul medio -> texto claro
        $this->assertSame('#111111', Color::contrast('#f5ede1'));   // crema -> texto oscuro
    }

    public function test_lighten_and_darken(): void
    {
        $this->assertSame('#ffffff', Color::lighten('#000000', 1.0));
        $this->assertSame('#000000', Color::darken('#ffffff', 1.0));
        // Aclarar sube los componentes; oscurecer los baja.
        $this->assertSame('#7f7f7f', Color::lighten('#000000', 0.5));
    }

    public function test_normalize_hex(): void
    {
        $this->assertSame('#aabbcc', Color::normalizeHex('#abc'));
        $this->assertSame('#1a2b3c', Color::normalizeHex('#1A2B3C'));
    }
}
