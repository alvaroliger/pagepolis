<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El JS de una plantilla (interacciones + hero 3D) se pide bajo demanda al
 * abrir su vista previa, para no meterlo en la carga inicial de la galería.
 */
class TemplateJsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_js_for_active_template(): void
    {
        $template = Template::create([
            'name' => 'Plantilla de prueba',
            'category' => 'Servicios',
            'html' => '<main>Hola</main>',
            'css' => 'body{color:red}',
            'js' => 'console.log("hero3d")',
            'is_premium' => false,
            'is_active' => true,
        ]);

        $response = $this->get("/plantillas/{$template->id}/js");

        $response->assertOk();
        $response->assertJson(['js' => 'console.log("hero3d")']);
    }

    public function test_inactive_template_js_is_not_exposed(): void
    {
        $template = Template::create([
            'name' => 'Plantilla inactiva',
            'category' => 'Servicios',
            'html' => '<main>Hola</main>',
            'css' => 'body{color:red}',
            'js' => 'console.log("no debería verse")',
            'is_premium' => false,
            'is_active' => false,
        ]);

        $response = $this->get("/plantillas/{$template->id}/js");

        $response->assertNotFound();
    }
}
