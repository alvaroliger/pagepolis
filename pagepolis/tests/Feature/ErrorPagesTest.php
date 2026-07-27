<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_an_unknown_published_site_slug_shows_the_branded_404_page(): void
    {
        $response = $this->get('/s/este-slug-no-existe-nunca');

        $response->assertStatus(404);
        $response->assertSee('pagepolis', false);
        $response->assertSee('Esta página no existe');
    }

    public function test_visiting_an_unknown_route_shows_the_branded_404_page(): void
    {
        $response = $this->get('/esta-ruta-no-existe');

        $response->assertStatus(404);
        $response->assertSee('Esta página no existe');
    }
}
