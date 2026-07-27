<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_includes_template_js_so_the_preview_can_render_it(): void
    {
        $template = Template::factory()->create([
            'js' => 'console.log("hero3d")',
            'is_active' => true,
        ]);

        $response = $this->get('/plantillas');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->where('templates.0.id', $template->id)
            ->where('templates.0.js', 'console.log("hero3d")')
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
