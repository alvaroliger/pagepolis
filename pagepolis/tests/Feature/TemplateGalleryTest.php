<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_templates_with_hero3d_canvas_as_3d(): void
    {
        Template::create([
            'name'     => 'Con hero 3D',
            'category' => 'SaaS',
            'html'     => '<section class="hero"><canvas class="hero3d-canvas" data-hero3d></canvas></section>',
            'css'      => ':root{--brand:#7c3aed}',
            'js'       => '',
        ]);
        Template::create([
            'name'     => 'Sin hero 3D',
            'category' => 'Restaurante',
            'html'     => '<section class="hero"><h1>Hola</h1></section>',
            'css'      => ':root{--brand:#7c3aed}',
            'js'       => '',
        ]);

        $response = $this->get('/plantillas')->assertStatus(200);

        $props = $response->original->getData()['page']['props'];

        $this->assertNotEmpty($props['hero3dJs']);

        $templates = collect($props['templates']);
        $this->assertTrue($templates->firstWhere('name', 'Con hero 3D')['is_3d']);
        $this->assertFalse($templates->firstWhere('name', 'Sin hero 3D')['is_3d']);
    }
}
