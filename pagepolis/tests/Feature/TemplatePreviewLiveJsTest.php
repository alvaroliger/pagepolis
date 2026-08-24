<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La vista previa tiene que ANIMARSE: si solo llegaran html/css, hasta las
 * plantillas "3D" se verían estáticas justo en el momento en que el cliente
 * decide si la elige.
 *
 * Ese código ya no viaja en las props de la galería (218 plantillas × su
 * código eran 2,95 MB por visita); ahora la vista previa carga la plantilla
 * desde /plantillas/{id}/render. Esto fija que ese endpoint sigue entregando
 * el JS, el CSS y el motor compartido.
 */
class TemplatePreviewLiveJsTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_endpoint_serves_js_css_and_engine_for_live_preview(): void
    {
        $template = Template::create([
            'name'       => 'App / SaaS',
            'category'   => 'SaaS',
            'html'       => '<canvas class="hero3d-canvas" data-hero3d></canvas>',
            'css'        => '.hero3d-canvas{display:block}',
            'js'         => 'console.log("hero3d engine");',
            'engine'     => 'classic',
            'tags'       => ['saas'],
            'is_premium' => false,
            'is_active'  => true,
            'uses_count' => 0,
        ]);

        $response = $this->get("/plantillas/{$template->id}/render");

        $response->assertOk();
        $response->assertSee('console.log("hero3d engine");', false);
        $response->assertSee('.hero3d-canvas{display:block}', false);
        $response->assertSee('data-hero3d', false);
        // El motor compartido va enlazado (no incrustado): el navegador lo
        // cachea y lo reutiliza entre vistas previas.
        $response->assertSee('/engine/classic.', false);
    }

    public function test_render_endpoint_is_not_indexable(): void
    {
        $template = Template::factory()->create(['is_active' => true]);

        $this->get("/plantillas/{$template->id}/render")
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
