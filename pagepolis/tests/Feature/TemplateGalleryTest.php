<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_ships_js_so_the_live_preview_is_interactive(): void
    {
        Template::create([
            'name'       => 'Plantilla de prueba',
            'category'   => 'Servicios',
            'html'       => '<canvas class="hero3d-canvas" data-hero3d></canvas>',
            'css'        => '',
            'js'         => 'console.log("hero3d init");',
            'tags'       => ['servicios'],
            'is_premium' => false,
            'is_active'  => true,
            'uses_count' => 0,
        ]);

        $templates = $this->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertNotEmpty($templates);
        $this->assertArrayHasKey('js', $templates[0]);
        $this->assertStringContainsString('hero3d init', $templates[0]['js']);
    }

    public function test_inactive_templates_are_not_shipped(): void
    {
        Template::create([
            'name'       => 'Plantilla oculta',
            'category'   => 'Servicios',
            'html'       => '<p>Hola</p>',
            'css'        => '',
            'js'         => '',
            'is_premium' => false,
            'is_active'  => false,
            'uses_count' => 0,
        ]);

        $templates = $this->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertEmpty($templates);
    }
}
