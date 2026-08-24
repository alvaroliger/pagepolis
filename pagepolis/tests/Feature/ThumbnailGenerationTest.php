<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El comando que genera las miniaturas necesita Chrome, así que aquí no se
 * ejecuta la captura. Lo que sí se fija es el contrato del que depende: que
 * exista la ruta que Chrome abre y que sirva la plantilla entera.
 */
class ThumbnailGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_route_serves_a_complete_standalone_document(): void
    {
        $template = Template::factory()->create([
            'html'      => '<section class="hero"><h1>Titular</h1></section>',
            'css'       => '.hero{background:#111}',
            'engine'    => 'kinetic',
            'is_active' => true,
        ]);

        $response = $this->get("/plantillas/{$template->id}/render");

        $response->assertOk();
        $html = $response->getContent();

        // Documento completo: sin esto Chrome capturaría un fragmento suelto.
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<section class="hero">', $html);
        $this->assertStringContainsString('.hero{background:#111}', $html);
        $this->assertStringContainsString('/engine/kinetic.', $html);
    }

    public function test_render_route_rejects_non_numeric_ids(): void
    {
        $this->get('/plantillas/abc/render')->assertNotFound();
    }

    public function test_command_fails_clearly_when_the_app_is_unreachable(): void
    {
        Template::factory()->create(['is_active' => true]);

        // Puerto cerrado a propósito: el comando debe avisar en vez de quedarse
        // esperando. La versión anterior usaba file_get_contents sin tiempo de
        // espera y se colgaba 60 s por consulta contra el propio Chrome.
        $exit = $this->artisan('templates:thumbnails', [
            '--url' => 'http://127.0.0.1:9',
        ])
            ->expectsOutputToContain('La app no responde')
            ->run();

        $this->assertSame(1, $exit);
    }
}
