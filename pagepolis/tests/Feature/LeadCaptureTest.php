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

    public function test_owner_sees_inbox_and_leads_get_marked_read(): void
    {
        $this->withoutVite();
        $project = $this->publishedProject();
        $lead = $project->leads()->create(['email' => 'c@d.com', 'message' => 'hola', 'payload' => []]);
        $this->assertNull($lead->read_at);

        $this->actingAs($project->user)->get('/mensajes')->assertStatus(200);

        $this->assertNotNull($lead->fresh()->read_at);
    }

    public function test_inbox_marks_only_displayed_leads_read_when_there_are_more_than_200(): void
    {
        $this->withoutVite();
        $project = $this->publishedProject();

        // Create 205 leads; the inbox displays the 200 most recent.
        for ($i = 1; $i <= 205; $i++) {
            $project->leads()->create([
                'email'   => "user{$i}@example.com",
                'message' => "Message {$i}",
                'payload' => [],
            ]);
        }

        $this->actingAs($project->user)->get('/mensajes')->assertOk();

        // The 200 most recently created leads should now be read.
        $markedRead = \App\Models\Lead::where('project_id', $project->id)
            ->whereNotNull('read_at')
            ->count();
        $this->assertEquals(200, $markedRead);

        // The 5 oldest leads were not displayed and must remain unread.
        $stillUnread = \App\Models\Lead::where('project_id', $project->id)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(5, $stillUnread);
    }
}
