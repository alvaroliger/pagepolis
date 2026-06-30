<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_landing_passes_null_activity_when_no_projects(): void
    {
        $response = $this->get('/');
        $response->assertInertia(fn ($page) => $page
            ->component('Landing')
            ->where('lastActivityMinutes', null)
        );
    }

    public function test_landing_passes_activity_minutes_when_project_exists(): void
    {
        $user = User::factory()->create();
        Project::create(['user_id' => $user->id, 'name' => 'Test']);

        $response = $this->get('/');
        $response->assertInertia(fn ($page) => $page
            ->where('lastActivityMinutes', fn ($v) => is_int($v) && $v >= 0)
        );
    }

    public function test_templates_page_loads(): void
    {
        $response = $this->get('/plantillas');
        $response->assertStatus(200);
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_editor_requires_auth(): void
    {
        $response = $this->get('/editor/1');
        $response->assertRedirect('/login');
    }

    public function test_admin_requires_auth(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }
}
