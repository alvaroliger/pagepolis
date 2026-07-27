<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Recorrido completo de un usuario real, de principio a fin: registrarse, crear
 * una web desde plantilla, editarla, previsualizarla, publicarla, recibir un
 * mensaje desde la web publicada, gestionarlo, duplicar, despublicar,
 * descargar, tirar a la papelera, restaurar, borrar del todo y darse de baja.
 *
 * Cada web creada se elimina dentro del propio recorrido (y la base de datos de
 * pruebas se recrea en cada test), así que no queda nada colgando.
 */
class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'nuevo@example.com'): User
    {
        // /register va con middleware "guest": si venimos de otro alta hay que
        // cerrar sesión antes, igual que haría una persona en otro navegador.
        auth()->logout();
        session()->flush();

        $this->post('/register', [
            'name'                  => 'Cliente Nuevo',
            'email'                 => $email,
            'password'              => 'password-fuerte-123',
            'password_confirmation' => 'password-fuerte-123',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $user = User::where('email', $email)->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function template(): Template
    {
        return Template::create([
            'name'     => 'Restaurante',
            'category' => 'Hostelería',
            'html'     => '<h1>Mi restaurante</h1>',
            'css'      => 'h1{color:#333}',
            'js'       => '',
            'tags'     => ['comida'],
        ]);
    }

    public function test_journey_from_signup_to_published_site_with_a_lead(): void
    {
        Mail::fake();

        // 1. Alta y panel vacío
        $user = $this->register();
        $this->actingAs($user)->get('/dashboard')->assertSuccessful();

        // 2. Elegir plantilla y crear la web
        $template = $this->template();
        $this->actingAs($user)->get('/plantillas')->assertSuccessful();
        $this->actingAs($user)
            ->post('/proyectos', ['name' => 'La Espiga', 'template_id' => $template->id])
            ->assertRedirect();

        $project = Project::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('<h1>Mi restaurante</h1>', $project->html, 'la plantilla debe copiarse al proyecto');

        // 3. Editar y guardar
        $this->actingAs($user)->get("/editor/{$project->id}")->assertSuccessful();
        $this->actingAs($user)->post("/editor/{$project->id}/guardar", [
            'name' => 'La Espiga',
            'html' => '<h1>La Espiga</h1><p>Pan artesano</p>',
            'css'  => 'h1{color:#8b5a2b}',
            'js'   => '',
        ])->assertSuccessful();
        $this->assertStringContainsString('Pan artesano', $project->fresh()->html);

        // 4. Vista previa
        $this->actingAs($user)->get("/proyectos/{$project->id}/preview")
            ->assertSuccessful()
            ->assertSee('Pan artesano', false);

        // 5. Publicar en el plan gratuito
        $this->actingAs($user)->get("/publicar?project_id={$project->id}")->assertSuccessful();
        // publicar/despublicar responden JSON (el front los llama por axios)
        $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $project->id])
            ->assertSuccessful();
        $project->refresh();
        $this->assertSame('published', $project->status);

        // 6. La web publicada se ve sin estar logueado
        $this->get("/s/{$project->slug}")->assertSuccessful()->assertSee('La Espiga', false);

        // 7. Un visitante deja un mensaje
        $this->postJson("/s/{$project->slug}/lead", [
            'name'    => 'Marta',
            'email'   => 'marta@example.com',
            'message' => 'Quiero encargar una tarta',
        ])->assertSuccessful();
        $lead = Lead::where('project_id', $project->id)->firstOrFail();

        // 8. El dueño lo lee, lo marca y lo exporta
        $this->actingAs($user)->get('/mensajes')->assertSuccessful()->assertSee('Marta');
        $this->actingAs($user)->patch("/mensajes/{$lead->id}/leido")->assertRedirect();
        $this->assertNotNull($lead->fresh()->read_at);
        $this->actingAs($user)->get('/mensajes/exportar')->assertSuccessful();

        // 9. Analítica y píxel de seguimiento de la web publicada
        $this->get("/t/{$project->id}")->assertSuccessful();
        $this->actingAs($user)->get('/analytics')->assertSuccessful();

        // 10. Descargar la web y despublicarla
        $this->actingAs($user)->get("/proyectos/{$project->id}/zip")->assertSuccessful();
        $this->actingAs($user)->postJson('/publicar/despublicar', ['project_id' => $project->id])
            ->assertSuccessful();
        $this->assertNotSame('published', $project->fresh()->status);

        // 11. Limpieza: papelera -> restaurar -> borrado definitivo
        $this->actingAs($user)->delete("/proyectos/{$project->id}")->assertRedirect();
        $this->assertSoftDeleted($project);

        $this->actingAs($user)->post("/proyectos/{$project->id}/restaurar")->assertRedirect();
        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);

        $this->actingAs($user)->delete("/proyectos/{$project->id}/eliminar-definitivo")->assertRedirect();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);

        // El mensaje asociado se va con la web (no quedan datos huérfanos)
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_journey_duplicating_a_project_and_closing_the_account(): void
    {
        $user = $this->register('duplica@example.com');
        $template = $this->template();

        $this->actingAs($user)->post('/proyectos', ['name' => 'Original', 'template_id' => $template->id]);
        $project = Project::where('user_id', $user->id)->firstOrFail();

        // Duplicar deja dos webs independientes
        $this->actingAs($user)->post("/proyectos/{$project->id}/duplicar")->assertRedirect();
        $this->assertSame(2, Project::where('user_id', $user->id)->count());

        // Perfil: ver, editar y exportar los datos
        $this->actingAs($user)->get('/perfil')->assertSuccessful();
        $this->actingAs($user)->patch('/perfil', [
            'name'  => 'Cliente Editado',
            'email' => 'duplica@example.com',
        ])->assertRedirect();
        $this->assertSame('Cliente Editado', $user->fresh()->name);
        $this->actingAs($user)->get('/perfil/exportar')->assertSuccessful();

        // Baja de la cuenta: se lleva las webs por delante
        $this->actingAs($user)->delete('/perfil', ['password' => 'password-fuerte-123'])
            ->assertRedirect('/');
        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_journey_creating_and_refining_a_site_with_ai(): void
    {
        $user = $this->register('conia@example.com');

        // La IA se simula: aquí se prueba el recorrido del usuario, no el modelo.
        $this->mock(\App\Services\AnthropicService::class, function ($mock) {
            $mock->shouldReceive('forUser')->andReturnSelf();
            $mock->shouldReceive('generateWebsite')->andReturn([
                'html' => '<h1>Peluquería Marta</h1>', 'css' => 'h1{color:#111}', 'js' => '',
            ]);
            $mock->shouldReceive('updateWebsite')->andReturn([
                'html' => '<h1>Peluquería Marta</h1><p>Pide cita</p>', 'css' => 'h1{color:#111}', 'js' => '',
            ]);
            $mock->shouldReceive('generateSeoMeta')->andReturn([
                'title' => 'Peluquería Marta', 'description' => 'Peluquería en Sevilla',
            ]);
        });

        // 1. Crear la web describiendo el negocio
        $this->actingAs($user)->post('/crear-con-ia', [
            'business_name' => 'Peluquería Marta',
            'description'   => 'Una peluquería de barrio en Sevilla',
            'location'      => 'Sevilla',
        ])->assertSessionHasNoErrors();

        $project = Project::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->get("/ai/estado/{$project->id}")->assertSuccessful();

        // 2. Pedir un cambio a la IA
        $this->actingAs($user)->postJson('/ai/actualizar', [
            'project_id'  => $project->id,
            'instruction' => 'Añade un botón para pedir cita',
        ])->assertSuccessful();
        $this->assertStringContainsString('Pide cita', $project->fresh()->html);

        // 3. Deshacer el último cambio de la IA
        $this->actingAs($user)->postJson('/ai/deshacer', ['project_id' => $project->id])
            ->assertSuccessful();
        $this->assertStringNotContainsString('Pide cita', $project->fresh()->html);

        // 4. Generar el SEO
        $this->actingAs($user)->postJson('/ai/seo', ['project_id' => $project->id])
            ->assertSuccessful();

        // 5. Limpieza: no dejamos la web de prueba creada
        $this->actingAs($user)->delete("/proyectos/{$project->id}")->assertRedirect();
        $this->actingAs($user)->delete("/proyectos/{$project->id}/eliminar-definitivo")->assertRedirect();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_a_user_cannot_touch_another_users_project(): void
    {
        $ana   = $this->register('ana-journey@example.com');
        $berto = $this->register('berto-journey@example.com');
        $template = $this->template();

        $this->actingAs($berto)->post('/proyectos', ['name' => 'De Berto', 'template_id' => $template->id]);
        $deBerto = Project::where('user_id', $berto->id)->firstOrFail();

        // Ana no puede verlo, editarlo, publicarlo, descargarlo ni borrarlo
        $this->actingAs($ana)->get("/editor/{$deBerto->id}")->assertForbidden();
        $this->actingAs($ana)->get("/proyectos/{$deBerto->id}/preview")->assertForbidden();
        $this->actingAs($ana)->get("/proyectos/{$deBerto->id}/zip")->assertForbidden();
        $this->actingAs($ana)->post("/editor/{$deBerto->id}/guardar", [
            'name' => 'Secuestrada', 'html' => '<h1>hack</h1>', 'css' => '', 'js' => '',
        ])->assertForbidden();
        $this->actingAs($ana)->delete("/proyectos/{$deBerto->id}")->assertForbidden();

        $this->assertSame('De Berto', $deBerto->fresh()->name);
    }
}
