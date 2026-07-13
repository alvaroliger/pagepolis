<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function template(array $attrs = []): Template
    {
        return Template::create(array_merge([
            'name'       => 'Plantilla de prueba',
            'category'   => 'Servicios',
            'html'       => '<section><h1>Hola</h1></section>',
            'css'        => '',
            'is_active'  => true,
            'is_premium' => false,
        ], $attrs));
    }

    public function test_marks_templates_with_hero3d_canvas_as_3d(): void
    {
        $this->template([
            'name' => 'Con hero 3D',
            'html' => '<section><canvas class="hero3d-canvas" data-hero3d></canvas></section>',
        ]);
        $this->template([
            'name' => 'Sin hero 3D',
            'html' => '<section><h1>Sin canvas</h1></section>',
        ]);

        $templates = collect(
            $this->actingAs($this->user())
                ->get('/plantillas')
                ->assertOk()
                ->inertiaProps('templates')
        )->keyBy('name');

        $this->assertTrue($templates['Con hero 3D']['has_3d']);
        $this->assertFalse($templates['Sin hero 3D']['has_3d']);
    }

    public function test_inactive_templates_are_excluded(): void
    {
        $this->template(['name' => 'Inactiva', 'is_active' => false]);

        $templates = collect(
            $this->actingAs($this->user())
                ->get('/plantillas')
                ->assertOk()
                ->inertiaProps('templates')
        );

        $this->assertFalse($templates->contains('name', 'Inactiva'));
    }
}
