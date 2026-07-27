<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Barrido de humo: recorre todas las páginas navegables con un usuario real y
 * comprueba que ninguna devuelve 500. Es la red que caza los fallos de "la vista
 * espera una variable que el controlador no le pasa", que no se ven hasta que
 * alguien abre esa página.
 */
class SmokeAllPagesTest extends TestCase
{
    use RefreshDatabase;

    private function userWithProject(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'Mi web',
            'slug'    => 'mi-web-smoke',
            'html'    => '<h1>Hola</h1>',
            'css'     => 'h1{color:red}',
            'js'      => '',
            'status'  => 'draft',
        ]);

        return [$user, $project];
    }

    public static function publicPages(): array
    {
        return [
            'landing'    => ['/'],
            'login'      => ['/login'],
            'registro'   => ['/register'],
            'plantillas' => ['/plantillas'],
            'términos'   => ['/terminos'],
            'privacidad' => ['/privacidad'],
            'sitemap'    => ['/sitemap.xml'],
            'robots'     => ['/robots.txt'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_public_pages_do_not_fail(string $path): void
    {
        $this->get($path)->assertSuccessful();
    }

    public static function privatePages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'crear'     => ['/crear'],
            'analítica' => ['/analytics'],
            'mensajes'  => ['/mensajes'],
            'perfil'    => ['/perfil'],
            'publicar'  => ['/publicar'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('privatePages')]
    public function test_authenticated_pages_do_not_fail(string $path): void
    {
        [$user] = $this->userWithProject();

        $this->actingAs($user)->get($path)->assertSuccessful();
    }

    public function test_editor_and_preview_do_not_fail(): void
    {
        [$user, $project] = $this->userWithProject();

        $this->actingAs($user)->get("/editor/{$project->id}")->assertSuccessful();
        $this->actingAs($user)->get("/proyectos/{$project->id}/preview")->assertSuccessful();
    }

    public function test_publish_page_with_project_context_does_not_fail(): void
    {
        [$user, $project] = $this->userWithProject();

        $this->actingAs($user)->get("/publicar?project_id={$project->id}")->assertSuccessful();
    }
}
