<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_sends_template_js_so_preview_can_run_hero_3d_and_interactive_elements(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Template::create([
            'name'       => 'App / SaaS',
            'category'   => 'SaaS',
            'tags'       => ['saas'],
            'html'       => '<canvas class="hero3d-canvas" data-hero3d></canvas>',
            'css'        => '',
            'js'         => "console.log('hero3d init');",
            'is_premium' => false,
            'is_active'  => true,
        ]);

        $templates = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertSame("console.log('hero3d init');", $templates[0]['js']);
    }

    public function test_inactive_templates_are_not_sent_to_the_gallery(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Template::create([
            'name'       => 'Plantilla oculta',
            'category'   => 'SaaS',
            'tags'       => [],
            'html'       => '<div></div>',
            'css'        => '',
            'js'         => '',
            'is_premium' => false,
            'is_active'  => false,
        ]);

        $templates = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertCount(0, $templates);
    }
}
