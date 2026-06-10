<?php

namespace Tests\Feature;

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
