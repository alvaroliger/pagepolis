<?php

namespace Tests\Feature;

use App\Mail\NewLeadMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function publishedProject(): Project
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        return Project::create([
            'user_id' => $user->id,
            'name'    => 'Panadería La Espiga',
            'html'    => '<form><input name="nombre"><input name="email"><textarea name="mensaje"></textarea><button type="submit">Enviar</button></form>',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
            'slug'    => 'panaderia-x',
        ]);
    }

    public function test_published_site_injects_lead_capture_script(): void
    {
        $this->publishedProject();

        $this->get('/s/panaderia-x')
            ->assertStatus(200)
            ->assertSee('/s/panaderia-x/lead', false)
            ->assertSee('addEventListener("submit"', false);
    }

    public function test_lead_is_stored_and_owner_notified(): void
    {
        Mail::fake();
        $project = $this->publishedProject();

        $this->postJson('/s/panaderia-x/lead', ['fields' => [
            'nombre'   => 'Ana',
            'email'    => 'ana@example.com',
            'telefono' => '600111222',
            'mensaje'  => 'Quiero encargar una tarta',
        ]])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('leads', [
            'project_id' => $project->id,
            'name'       => 'Ana',
            'email'      => 'ana@example.com',
            'phone'      => '600111222',
            'message'    => 'Quiero encargar una tarta',
        ]);

        Mail::assertQueued(NewLeadMail::class, fn ($m) => $m->hasTo($project->user->email));
    }

    public function test_email_is_extracted_from_value_when_field_name_is_weird(): void
    {
        Mail::fake();
        $this->publishedProject();

        $this->postJson('/s/panaderia-x/lead', ['fields' => [
            'campo_1' => 'hola@correo.com',
            'campo_2' => 'Necesito un presupuesto para mi negocio, gracias.',
        ]])->assertOk();

        $this->assertDatabaseHas('leads', ['email' => 'hola@correo.com']);
    }

    public function test_empty_submission_is_rejected(): void
    {
        Mail::fake();
        $this->publishedProject();

        $this->postJson('/s/panaderia-x/lead', ['fields' => ['x' => '', 'y' => '   ']])
            ->assertStatus(422);

        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
    }

    public function test_lead_to_unknown_site_returns_404(): void
    {
        $this->postJson('/s/no-existe/lead', ['fields' => ['email' => 'a@b.com']])
            ->assertStatus(404);
    }

    public function test_email_subject_includes_lead_name_when_known(): void
    {
        Mail::fake();
        $this->publishedProject();

        $this->postJson('/s/panaderia-x/lead', ['fields' => [
            'nombre' => 'Carlos',
            'email'  => 'carlos@example.com',
        ]])->assertOk();

        Mail::assertQueued(NewLeadMail::class, fn ($m) => str_contains($m->envelope()->subject, 'Carlos'));
    }

    public function test_email_subject_omits_name_gracefully_when_lead_is_anonymous(): void
    {
        Mail::fake();
        $this->publishedProject();

        $this->postJson('/s/panaderia-x/lead', ['fields' => [
            'email'   => 'anon@example.com',
            'mensaje' => 'Sin nombre',
        ]])->assertOk();

        Mail::assertQueued(NewLeadMail::class, function ($m) {
            $subject = $m->envelope()->subject;
            return str_contains($subject, 'Panadería La Espiga') && ! str_contains($subject, 'null');
        });
    }

    public function test_reply_to_address_includes_lead_name(): void
    {
        Mail::fake();
        $this->publishedProject();

        $this->postJson('/s/panaderia-x/lead', ['fields' => [
            'nombre' => 'María',
            'email'  => 'maria@example.com',
        ]])->assertOk();

        Mail::assertQueued(NewLeadMail::class, function ($m) {
            $replyTo = $m->envelope()->replyTo;
            return count($replyTo) === 1
                && $replyTo[0]->address === 'maria@example.com'
                && $replyTo[0]->name === 'María';
        });
    }

    public function test_owner_sees_inbox_and_leads_stay_unread_until_marked(): void
    {
        // Viewing the inbox must not silently clear the "Nuevo" badge: the
        // owner decides what's read, so they can still tell who's pending a reply.
        $this->withoutVite();
        $project = $this->publishedProject();
        $lead = $project->leads()->create(['email' => 'c@d.com', 'message' => 'hola', 'payload' => []]);
        $this->assertNull($lead->read_at);

        $this->actingAs($project->user)->get('/mensajes')->assertStatus(200);

        $this->assertNull($lead->fresh()->read_at);
    }

    public function test_inbox_only_shows_own_leads(): void
    {
        // User A submits a lead to their own published site.
        $projectA = $this->publishedProject();
        $projectA->leads()->create(['email' => 'visitor@example.com', 'message' => 'hola', 'payload' => []]);

        // User B has a completely separate account with no leads.
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($userB)->get('/mensajes')->assertStatus(200);

        // User B must not see User A's lead in the inbox data.
        $leads = $response->original->getData()['page']['props']['leads'] ?? [];
        $this->assertEmpty($leads, 'User B should not see leads belonging to User A');
    }

    // ── CSV export ──────────────────────────────────────────────────────

    public function test_csv_export_requires_auth(): void
    {
        $this->get('/mensajes/exportar')->assertRedirect('/login');
    }

    public function test_csv_export_returns_correct_headers_and_data(): void
    {
        $project = $this->publishedProject();
        $project->leads()->create([
            'name'    => 'Pedro',
            'email'   => 'pedro@example.com',
            'phone'   => '600999888',
            'message' => 'Quiero información',
            'payload' => [],
        ]);

        $response = $this->actingAs($project->user)->get('/mensajes/exportar');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();
        // BOM UTF-8 presente para Excel
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('Fecha,Web,Nombre,Email,', $body);
        $this->assertStringContainsString('Pedro', $body);
        $this->assertStringContainsString('pedro@example.com', $body);
        $this->assertStringContainsString('Quiero información', $body);
    }

    public function test_csv_export_with_no_leads_returns_header_row_only(): void
    {
        $project = $this->publishedProject();

        $response = $this->actingAs($project->user)->get('/mensajes/exportar');

        $response->assertStatus(200);
        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", ltrim($body, "\xEF\xBB\xBF")));
        $this->assertCount(1, $lines, 'Solo debe haber la fila de cabecera');
    }

    public function test_csv_export_does_not_include_other_users_leads(): void
    {
        $project      = $this->publishedProject();
        $otherUser    = User::factory()->create(['email_verified_at' => now()]);
        $otherProject = Project::create([
            'user_id' => $otherUser->id,
            'name'    => 'Otra web',
            'html'    => '', 'css' => '', 'js' => '',
            'status'  => 'published',
            'slug'    => 'otra-web-x',
        ]);
        $otherProject->leads()->create(['email' => 'secreto@rival.com', 'message' => 'dato privado', 'payload' => []]);

        $body = $this->actingAs($project->user)
            ->get('/mensajes/exportar')
            ->streamedContent();

        $this->assertStringNotContainsString('secreto@rival.com', $body);
        $this->assertStringNotContainsString('dato privado', $body);
    }

    public function test_inbox_does_not_mark_leads_beyond_the_200_display_limit(): void
    {
        $this->withoutVite();
        $project = $this->publishedProject();

        // Create 205 leads; the inbox only displays the 200 most recent.
        for ($i = 1; $i <= 205; $i++) {
            $project->leads()->create([
                'email'   => "user{$i}@example.com",
                'message' => "Message {$i}",
                'payload' => [],
            ]);
        }

        $this->actingAs($project->user)->get('/mensajes')->assertOk();

        $this->assertEquals(0, \App\Models\Lead::where('project_id', $project->id)->whereNotNull('read_at')->count());
        $this->assertEquals(205, \App\Models\Lead::where('project_id', $project->id)->whereNull('read_at')->count());
    }

    // ── Marcar leído/no leído manualmente ───────────────────────────────

    public function test_owner_can_toggle_a_lead_between_read_and_unread(): void
    {
        $project = $this->publishedProject();
        $lead = $project->leads()->create(['email' => 'c@d.com', 'message' => 'hola', 'payload' => []]);

        $this->actingAs($project->user)
            ->patch("/mensajes/{$lead->id}/leido")
            ->assertRedirect('/mensajes');
        $this->assertNotNull($lead->fresh()->read_at);

        $this->actingAs($project->user)
            ->patch("/mensajes/{$lead->id}/leido")
            ->assertRedirect('/mensajes');
        $this->assertNull($lead->fresh()->read_at);
    }

    public function test_toggle_read_requires_auth(): void
    {
        $project = $this->publishedProject();
        $lead = $project->leads()->create(['email' => 'c@d.com', 'message' => 'hola', 'payload' => []]);

        $this->patch("/mensajes/{$lead->id}/leido")->assertRedirect('/login');
        $this->assertNull($lead->fresh()->read_at);
    }

    public function test_toggle_read_rejects_other_users_lead(): void
    {
        $project = $this->publishedProject();
        $lead = $project->leads()->create(['email' => 'c@d.com', 'message' => 'hola', 'payload' => []]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($otherUser)
            ->patch("/mensajes/{$lead->id}/leido")
            ->assertStatus(404);
        $this->assertNull($lead->fresh()->read_at);
    }

    public function test_mark_all_read_clears_unread_count_without_affecting_other_users(): void
    {
        $project = $this->publishedProject();
        $leadA1 = $project->leads()->create(['email' => 'a1@d.com', 'message' => 'hola', 'payload' => []]);
        $leadA2 = $project->leads()->create(['email' => 'a2@d.com', 'message' => 'hola', 'payload' => []]);

        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $otherProject = Project::create([
            'user_id' => $otherUser->id, 'name' => 'Otra web',
            'html' => '', 'css' => '', 'js' => '', 'status' => 'published', 'slug' => 'otra-web-y',
        ]);
        $leadB = $otherProject->leads()->create(['email' => 'b@d.com', 'message' => 'hola', 'payload' => []]);

        $this->actingAs($project->user)
            ->post('/mensajes/marcar-todos-leidos')
            ->assertRedirect('/mensajes');

        $this->assertNotNull($leadA1->fresh()->read_at);
        $this->assertNotNull($leadA2->fresh()->read_at);
        $this->assertNull($leadB->fresh()->read_at, "Marking all as read must not touch another user's leads");
    }
}
