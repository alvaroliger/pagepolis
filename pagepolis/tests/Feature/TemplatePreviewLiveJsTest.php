<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The template preview modal renders `js` inside the iframe so the hero-3D
 * canvas/tilt effect actually animates during preview (previously only
 * `html`/`css` reached the frontend, so even "3D" templates looked static
 * before a client committed to one). This locks in that the `js` field keeps
 * flowing from controller to page props.
 */
class TemplatePreviewLiveJsTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_gallery_exposes_js_for_live_preview(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Template::create([
            'name'        => 'App / SaaS',
            'category'    => 'SaaS',
            'html'        => '<canvas class="hero3d-canvas" data-hero3d></canvas>',
            'css'         => '',
            'js'          => 'console.log("hero3d engine");',
            'tags'        => ['saas'],
            'is_premium'  => false,
            'is_active'   => true,
            'uses_count'  => 0,
        ]);

        $templates = $this->actingAs($user)
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertSame('console.log("hero3d engine");', $templates[0]['js']);
    }
}
