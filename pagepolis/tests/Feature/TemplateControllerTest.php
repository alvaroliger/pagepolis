<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private function template(array $attrs = []): Template
    {
        return Template::create(array_merge([
            'name'     => 'Plantilla de prueba',
            'category' => 'Servicios',
            'html'     => '<h1>Hola</h1>',
            'css'      => '',
            'tags'     => ['test'],
            'is_active' => true,
        ], $attrs));
    }

    public function test_index_exposes_is_3d_flag_per_template(): void
    {
        $simple = $this->template(['name' => 'Simple', 'is_3d' => false]);
        $threeD = $this->template(['name' => '3D', 'is_3d' => true]);

        $templates = $this
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $byName = collect($templates)->keyBy('name');

        $this->assertFalse($byName[$simple->name]['is_3d']);
        $this->assertTrue($byName[$threeD->name]['is_3d']);
    }

    public function test_is_3d_defaults_to_false_when_not_specified(): void
    {
        $template = $this->template(['name' => 'Sin especificar']);

        $this->assertFalse($template->fresh()->is_3d);
    }

    public function test_inactive_templates_are_not_listed(): void
    {
        $this->template(['name' => 'Oculta', 'is_active' => false, 'is_3d' => true]);

        $templates = $this
            ->get('/plantillas')
            ->assertOk()
            ->inertiaProps('templates');

        $this->assertFalse(collect($templates)->pluck('name')->contains('Oculta'));
    }
}
