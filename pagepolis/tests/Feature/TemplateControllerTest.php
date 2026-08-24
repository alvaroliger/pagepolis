<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La galería enseña una miniatura pre-generada por plantilla, así que NO
     * debe llevarse el html/css/js de las 218: eso eran 2,95 MB de documento
     * y 218 iframes compitiendo por el único hilo principal del navegador.
     */
    public function test_gallery_does_not_ship_template_code(): void
    {
        Template::factory()->create([
            'html'      => '<h1>Hola</h1>',
            'css'       => 'h1{color:red}',
            'js'        => 'console.log("hero3d")',
            'is_active' => true,
        ]);

        $response = $this->get('/plantillas');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->missing('templates.0.html')
            ->missing('templates.0.css')
            ->missing('templates.0.js')
        );
    }

    public function test_gallery_ships_the_thumbnail_and_metadata_the_grid_needs(): void
    {
        $template = Template::factory()->create([
            'thumbnail' => '/thumbs/t1-abc123.webp',
            'category'  => 'SaaS',
            'is_active' => true,
        ]);

        $response = $this->get('/plantillas');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->where('templates.0.id', $template->id)
            ->where('templates.0.thumbnail', '/thumbs/t1-abc123.webp')
            ->where('templates.0.category', 'SaaS')
        );
    }

    public function test_gallery_omits_inactive_templates(): void
    {
        Template::factory()->create(['is_active' => false]);

        $response = $this->get('/plantillas');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->where('templates', [])
        );
    }
}
