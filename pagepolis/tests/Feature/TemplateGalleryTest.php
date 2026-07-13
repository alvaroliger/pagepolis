<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The template gallery lets clients tell at a glance which templates ship the
 * interactive WebGL 3D hero (has_3d) versus the classic photo/product-led
 * ones, so the flag must actually reach the page props.
 */
class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function template(array $attrs = []): Template
    {
        return Template::create(array_merge([
            'name'       => 'Plantilla de prueba',
            'category'   => 'Servicios',
            'tags'       => ['test'],
            'html'       => '<h1>Test</h1>',
            'css'        => '',
            'js'         => '',
            'is_premium' => false,
            'is_active'  => true,
            'has_3d'     => false,
        ], $attrs));
    }

    public function test_gallery_reports_has_3d_flag_per_template(): void
    {
        $this->template(['name' => 'Con hero 3D', 'has_3d' => true]);
        $this->template(['name' => 'Clásica', 'has_3d' => false]);

        $response = $this->get('/plantillas');

        $response->assertOk();
        $templates = collect($response->viewData('page')['props']['templates']);

        $this->assertTrue($templates->firstWhere('name', 'Con hero 3D')['has_3d']);
        $this->assertFalse($templates->firstWhere('name', 'Clásica')['has_3d']);
    }

    public function test_gallery_hides_inactive_templates(): void
    {
        $this->template(['name' => 'Inactiva', 'is_active' => false]);

        $response = $this->get('/plantillas');

        $response->assertOk();
        $templates = collect($response->viewData('page')['props']['templates']);

        $this->assertNull($templates->firstWhere('name', 'Inactiva'));
    }
}
