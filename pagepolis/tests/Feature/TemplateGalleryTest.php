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
            'name'     => 'SaaS',
            'category' => 'Tecnología',
            'html'     => '<div class="hero"><canvas class="hero3d-canvas" data-hero3d></canvas></div>',
            'css'      => '.hero{height:400px}',
            'js'       => '/* hero3d engine */',
        ], $attrs));
    }

    public function test_gallery_includes_js_so_3d_templates_animate_in_preview(): void
    {
        $user = $this->user();
        $this->template();

        $props = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertNotEmpty($props);
        $this->assertArrayHasKey('js', $props[0]);
        $this->assertSame('/* hero3d engine */', $props[0]['js']);
    }

    public function test_gallery_excludes_inactive_templates(): void
    {
        $user = $this->user();
        $this->template(['is_active' => false]);

        $props = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertEmpty($props);
    }
}
