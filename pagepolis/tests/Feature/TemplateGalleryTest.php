<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The template gallery flags which templates ship with the interactive 3D
 * hero (data-hero3d marker in the HTML) so clients can tell classic and 3D
 * templates apart before picking one. This must stay in sync with the
 * actual markup — a stale/hardcoded flag would mislabel templates.
 */
class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_templates_with_hero3d_marker_are_flagged_as_3d(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $with3d = Template::create([
            'name' => '3D template', 'category' => 'SaaS', 'tags' => [],
            'html' => '<section><canvas data-hero3d></canvas></section>',
            'css' => '', 'js' => '', 'is_premium' => false, 'is_active' => true,
        ]);

        $without3d = Template::create([
            'name' => 'Classic template', 'category' => 'Restaurante', 'tags' => [],
            'html' => '<section><h1>Hola</h1></section>',
            'css' => '', 'js' => '', 'is_premium' => false, 'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/plantillas');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Templates/Index')
            ->where('templates.0.has_3d', fn ($v) => in_array($v, [true, false], true))
        );

        $templates = $response->viewData('page')['props']['templates'];
        $byName = collect($templates)->keyBy('name');

        $this->assertTrue($byName[$with3d->name]['has_3d']);
        $this->assertFalse($byName[$without3d->name]['has_3d']);
    }

    public function test_inactive_templates_are_not_listed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Template::create([
            'name' => 'Hidden template', 'category' => 'SaaS', 'tags' => [],
            'html' => '<section></section>', 'css' => '', 'js' => '',
            'is_premium' => false, 'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get('/plantillas');

        $templates = $response->viewData('page')['props']['templates'];
        $this->assertFalse(collect($templates)->contains('name', 'Hidden template'));
    }
}
