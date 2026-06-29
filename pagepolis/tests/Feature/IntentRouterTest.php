<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\IntentRouter;
use PHPUnit\Framework\TestCase;

/**
 * Verifica que el router LOCAL resuelve los cambios más comunes (pedidos de muchas
 * formas distintas) sin tocar la IA. Cada caso que pasa = dinero ahorrado.
 */
class IntentRouterTest extends TestCase
{
    private function project(): Project
    {
        $css = "/* ── Base compartida de las plantillas ── */\n"
             . ":root{--bg:#ffffff;--surface:#ffffff;--text:#111111;--muted:#666;--border:#eee;"
             . "--brand:#7c3aed;--brand-2:#6d28d9;--on-brand:#fff;--brand-soft:rgba(124,58,237,.12);"
             . "--radius:14px;--font-head:'Inter';--font-body:'Inter';--header-bg:rgba(255,255,255,.9);}\nbody{color:var(--text)}";
        $js  = "const WHATSAPP_NUMERO = '34600000000';\nconst EMAIL_PEDIDOS = 'pedidos@negocio.com';\n(function(){})();";
        $html = '<header id="site-header"><a href="#" class="logo">Mi Negocio</a></header>'
              . '<main>'
              . '<section class="stats"><div class="stats-grid">x</div></section>'
              . '<section class="testi"><div class="testi-slider">op</div></section>'
              . '<section id="precios"><div class="price-grid">p</div></section>'
              . '<section class="faq-sec"><div class="faq-item">q</div></section>'
              . '</main>';

        return new Project(['html' => $html, 'css' => $css, 'js' => $js]);
    }

    private function router(): IntentRouter
    {
        return new IntentRouter();
    }

    public function test_background_color(): void
    {
        $cases = [
            'cambia el fondo a azul' => '#2563eb',
            'pon el fondo de color rojo' => '#dc2626',
            'quiero el background negro' => '#111111',
            'ponlo con el fondo verde' => '#16a34a',
            'el color de fondo que sea gris' => '#6b7280',
        ];
        foreach ($cases as $phrase => $hex) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringContainsString("--bg:{$hex}", $r['css'], "Mal color en: {$phrase}");
            $this->assertSame(['css'], $r['changed']);
        }
    }

    public function test_brand_color(): void
    {
        $cases = [
            'cambia el color principal a rojo' => '#dc2626',
            'pon los botones azules' => '#2563eb',
            'quiero el color de marca verde' => '#16a34a',
            'ponlo todo morado' => '#7c3aed',
            'cambia el color primario a naranja' => '#ea580c',
            'los enlaces de color turquesa' => '#14b8a6',
        ];
        foreach ($cases as $phrase => $hex) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringContainsString("--brand:{$hex}", $r['css'], "Mal color en: {$phrase}");
        }
    }

    public function test_text_color(): void
    {
        $r = $this->router()->handle($this->project(), 'cambia el color del texto a gris');
        $this->assertNotNull($r);
        $this->assertStringContainsString('--text:#6b7280', $r['css']);
    }

    public function test_rounded(): void
    {
        $cases = [
            'haz los bordes más redondeados' => '20px',
            'quiero las esquinas cuadradas' => '2px',
            'pon los bordes muy redondeados' => '28px',
        ];
        foreach ($cases as $phrase => $radius) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringContainsString("--radius:{$radius}", $r['css'], "Mal radio en: {$phrase}");
        }
    }

    public function test_font(): void
    {
        $cases = [
            'cambia la tipografía a Poppins' => 'Poppins',
            'usa la fuente Montserrat' => 'Montserrat',
            'quiero el tipo de letra Roboto' => 'Roboto',
        ];
        foreach ($cases as $phrase => $family) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringContainsString($family, $r['css']);
            $this->assertStringContainsString('@import', $r['css']);
        }
    }

    public function test_whatsapp(): void
    {
        foreach (['mi whatsapp es +34 611 222 333', 'pon el whatsapp 611 222 333', 'cambia el numero de pedidos al 611222333'] as $phrase) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringContainsString('34611222333', $r['js'], "Mal número en: {$phrase}");
            $this->assertSame(['js'], $r['changed']);
        }
    }

    public function test_order_email(): void
    {
        $r = $this->router()->handle($this->project(), 'que los pedidos lleguen al correo ventas@miweb.com');
        $this->assertNotNull($r);
        $this->assertStringContainsString('ventas@miweb.com', $r['js']);
    }

    public function test_business_name(): void
    {
        $r = $this->router()->handle($this->project(), 'cambia el nombre a Pizzería Roma');
        $this->assertNotNull($r);
        $this->assertStringContainsString('Pizzería Roma', $r['html']);
        $this->assertStringNotContainsString('Mi Negocio', $r['html']);
    }

    public function test_hide_section(): void
    {
        $cases = [
            'quita los testimonios' => 'testi-slider',
            'elimina la sección de precios' => 'price-grid',
            'oculta las preguntas frecuentes' => 'faq-item',
            'no quiero las opiniones' => 'testi-slider',
        ];
        foreach ($cases as $phrase => $gone) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringNotContainsString($gone, $r['html'], "No quitó en: {$phrase}");
            $this->assertSame(['html'], $r['changed']);
        }
    }

    public function test_background_universal_on_custom_site(): void
    {
        // Web sin el motor base, con nombres de variable propios pero SIN conflicto
        // de rol: --crema solo como fondo, --tinta solo como texto.
        $css = ":root{--crema:#f5ede1;--tinta:#33271c;--ac:#a05a2c}"
             . ".hero{background:var(--crema)} .zona{background:var(--crema)} body{color:var(--tinta)} h1{color:var(--tinta)}";
        $p = new Project(['html' => '<header></header>', 'css' => $css, 'js' => '']);

        $r = $this->router()->handle($p, 'cambia el fondo a azul');
        $this->assertNotNull($r, 'Debería resolver el fondo en local');
        $this->assertStringContainsString('--crema:#2563eb', $r['css']);   // fondo -> azul
        $this->assertStringContainsString('--tinta:#ffffff', $r['css']);   // texto -> contraste
    }

    public function test_background_conflict_goes_to_ai(): void
    {
        // --tinta hace de fondo (footer) Y de texto -> cambio inseguro -> IA (null).
        $css = ":root{--crema:#f5ede1;--tinta:#33271c}"
             . ".hero{background:var(--crema)} body{color:var(--tinta)} .foot{background:var(--tinta)}";
        $p = new Project(['html' => '<header></header>', 'css' => $css, 'js' => '']);

        $this->assertNull($this->router()->handle($p, 'cambia el fondo a azul'));
    }

    public function test_help_and_greetings_reply_free(): void
    {
        foreach (['hola', 'buenas, ¿qué puedes hacer?', 'ayuda', 'no sé qué poner', 'opciones'] as $msg) {
            $r = $this->router()->handle($this->project(), $msg);
            $this->assertNotNull($r, "No respondió a: {$msg}");
            $this->assertSame([], $r['changed']);                 // es respuesta, no cambia nada
            $this->assertNotEmpty($r['description']);
        }
    }

    public function test_incomplete_requests_ask_back(): void
    {
        $cases = [
            'cambia el fondo'            => 'color',     // falta el color
            'pon el color principal'     => 'color',
            'cambia el nombre'           => 'nombre',
            'cambia la tipografía'       => 'tipograf',
            'pon mi whatsapp'            => 'whatsapp',
            'azul',                       // solo un color, sin decir dónde
        ];
        foreach ($cases as $msg => $needle) {
            // soporta el caso 'azul' (clave numérica)
            if (is_int($msg)) { $msg = $needle; $needle = 'fondo'; }
            $r = $this->router()->handle($this->project(), $msg);
            $this->assertNotNull($r, "No pidió aclaración en: {$msg}");
            $this->assertSame([], $r['changed'], "No debería cambiar nada en: {$msg}");
            $this->assertStringContainsString($needle, mb_strtolower($r['description']), "Aclaración rara en: {$msg}");
        }
    }

    public function test_complete_request_still_applies_not_clarified(): void
    {
        // Una petición COMPLETA no debe pedir aclaración, sino aplicarse.
        $r = $this->router()->handle($this->project(), 'pon el fondo azul');
        $this->assertNotNull($r);
        $this->assertSame(['css'], $r['changed']);
    }

    public function test_unknown_falls_back_to_ai(): void
    {
        $r = $this->router()->handle($this->project(), 'añádeme un apartado con un mapa interactivo y un blog con tres artículos');
        $this->assertNull($r);   // no programado -> IA
    }

    public function test_color_without_target_or_verb_returns_null(): void
    {
        // Hay un color pero ni parte ni verbo de cambio -> ambiguo -> IA.
        $this->assertNull($this->router()->handle($this->project(), 'me gusta el azul del mar'));
    }

    public function test_empty_message_returns_null(): void
    {
        $this->assertNull($this->router()->handle($this->project(), '   '));
    }

    public function test_overrides_merge_across_intents(): void
    {
        $p  = $this->project();
        $r1 = $this->router()->handle($p, 'color principal rojo');
        $p2 = new Project(['html' => $p->html, 'css' => $r1['css'], 'js' => $p->js]);
        $r2 = $this->router()->handle($p2, 'bordes redondeados');
        $this->assertSame(1, substr_count($r2['css'], 'PP-OVR-START'));
        $this->assertStringContainsString('--brand:#dc2626', $r2['css']);   // se conserva
        $this->assertStringContainsString('--radius:20px', $r2['css']);     // se añade
    }

    public function test_font_import_is_idempotent(): void
    {
        $p  = $this->project();
        $r1 = $this->router()->handle($p, 'cambia la fuente a Poppins');
        $p2 = new Project(['html' => $p->html, 'css' => $r1['css'], 'js' => $p->js]);
        $r2 = $this->router()->handle($p2, 'mejor la fuente Montserrat');
        $this->assertSame(1, substr_count($r2['css'], 'PP-FONT-START'));    // un solo @import gestionado
        $this->assertStringContainsString('Montserrat', $r2['css']);
        $this->assertStringNotContainsString('family=Poppins', $r2['css']);
    }

    public function test_hide_ambiguous_needs_section_cue(): void
    {
        // "quita el precio" (ambiguo, sin "sección") NO debe borrar la sección -> IA.
        $this->assertNull($this->router()->handle($this->project(), 'quita el precio de la pizza'));
        // Con "sección" sí.
        $r = $this->router()->handle($this->project(), 'quita la seccion de precios');
        $this->assertNotNull($r);
        $this->assertStringNotContainsString('price-grid', $r['html']);
    }

    public function test_name_extraction_patterns(): void
    {
        foreach ([
            'renombra la web a Bar Pepe'        => 'Bar Pepe',
            'que se llame Café Sol'             => 'Café Sol',
            'cambia el nombre por Pizzería Uno' => 'Pizzería Uno',
        ] as $phrase => $expected) {
            $r = $this->router()->handle($this->project(), $phrase);
            $this->assertNotNull($r, "No reconoció: {$phrase}");
            $this->assertStringContainsString($expected, $r['html'], "Mal nombre en: {$phrase}");
        }
    }

    public function test_idempotent_overrides_do_not_stack(): void
    {
        $p  = $this->project();
        $r1 = $this->router()->handle($p, 'pon el fondo azul');
        $p2 = new Project(['html' => $p->html, 'css' => $r1['css'], 'js' => $p->js]);
        $r2 = $this->router()->handle($p2, 'pon el fondo rojo');
        $this->assertSame(1, substr_count($r2['css'], 'PP-OVR-START'));
        $this->assertStringContainsString('--bg:#dc2626', $r2['css']);
        $this->assertStringNotContainsString('--bg:#2563eb', $r2['css']);
    }
}
