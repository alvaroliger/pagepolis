<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The template gallery lets clients filter between "Clásico" and "3D"
 * templates, so `is_3d` must round-trip from DB to the Inertia page as a
 * real boolean the frontend can branch on.
 */
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
            'name'     => 'Plantilla de prueba',
            'category' => 'Servicios',
            'html'     => '<h1>Test</h1>',
            'css'      => '',
            'is_3d'    => false,
        ], $attrs));
    }

    public function test_gallery_exposes_is_3d_flag_per_template(): void
    {
        $this->template(['name' => 'Clásica', 'is_3d' => false]);
        $this->template(['name' => '3D', 'is_3d' => true]);

        $templates = $this->actingAs($this->user())
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $byName = collect($templates)->keyBy('name');

        $this->assertFalse($byName['Clásica']['is_3d']);
        $this->assertTrue($byName['3D']['is_3d']);
    }

    public function test_inactive_templates_are_not_listed(): void
    {
        $this->template(['name' => 'Oculta', 'is_active' => false]);

        $templates = $this->actingAs($this->user())
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertFalse(collect($templates)->pluck('name')->contains('Oculta'));
    }
}
