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
            'name'        => 'Plantilla de prueba',
            'category'    => 'Saas',
            'html'        => '<h1>Hola</h1>',
            'css'         => '',
            'is_premium'  => false,
            'is_active'   => true,
            'uses_count'  => 0,
        ], $attrs));
    }

    public function test_template_with_hero3d_canvas_is_flagged_as_3d(): void
    {
        $user = $this->user();
        $this->template(['html' => '<canvas class="hero3d-canvas" data-hero3d></canvas>']);

        $templates = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertTrue($templates[0]['has3d']);
    }

    public function test_template_without_hero3d_canvas_is_not_flagged_as_3d(): void
    {
        $user = $this->user();
        $this->template(['html' => '<div class="hero-img"></div>']);

        $templates = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertFalse($templates[0]['has3d']);
    }

    public function test_inactive_templates_are_not_listed(): void
    {
        $user = $this->user();
        $this->template(['is_active' => false]);

        $templates = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertCount(0, $templates);
    }
}
