<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function template(array $attrs = []): Template
    {
        return Template::create(array_merge([
            'name'       => 'Plantilla',
            'category'   => 'Servicios',
            'html'       => '<body><h1>Hola</h1></body>',
            'css'        => '',
            'js'         => '',
            'is_premium' => false,
            'is_active'  => true,
        ], $attrs));
    }

    public function test_gallery_flags_templates_with_a_3d_hero(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $withHero3d = $this->template([
            'name' => 'SaaS',
            'html' => '<body><canvas class="hero3d-canvas" data-hero3d></canvas></body>',
        ]);
        $plain = $this->template(['name' => 'Restaurante']);

        $response = $this->actingAs($user)->get('/plantillas')->assertStatus(200);

        $templates = collect($response->original->getData()['page']['props']['templates']);
        $this->assertTrue($templates->firstWhere('id', $withHero3d->id)['is_3d']);
        $this->assertFalse($templates->firstWhere('id', $plain->id)['is_3d']);
    }
}
