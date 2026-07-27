<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los mensajes (leads) que capturan las webs publicadas son datos de clientes:
 * un usuario NO puede ver ni tocar los de otro. El código ya lo respeta, pero
 * sin tests nada impide que un cambio futuro abra la fuga.
 */
class LeadIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Lead} Dueño y un lead suyo. */
    private function ownerWithLead(string $email): array
    {
        $user = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'Web de ' . $email,
            'slug'    => 'web-' . uniqid(),
            'html'    => '<h1>Hola</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
        ]);
        $lead = Lead::create([
            'project_id' => $project->id,
            'name'       => 'Cliente de ' . $email,
            'email'      => 'cliente-' . uniqid() . '@example.com',
            'message'    => 'Quiero presupuesto',
        ]);

        return [$user, $lead];
    }

    public function test_inbox_only_lists_own_leads(): void
    {
        [$ana, $leadDeAna]     = $this->ownerWithLead('ana@example.com');
        [, $leadDeBerto]       = $this->ownerWithLead('berto@example.com');

        $response = $this->actingAs($ana)->get('/mensajes');

        $response->assertOk();
        $response->assertSee($leadDeAna->name);
        $response->assertDontSee($leadDeBerto->name);
    }

    public function test_cannot_toggle_read_on_someone_elses_lead(): void
    {
        [$ana]           = $this->ownerWithLead('ana2@example.com');
        [, $leadDeBerto] = $this->ownerWithLead('berto2@example.com');

        $this->actingAs($ana)
            ->patch("/mensajes/{$leadDeBerto->id}/leido")
            ->assertNotFound();

        $this->assertNull($leadDeBerto->fresh()->read_at);
    }

    public function test_mark_all_read_does_not_touch_other_users_leads(): void
    {
        [$ana]           = $this->ownerWithLead('ana3@example.com');
        [, $leadDeBerto] = $this->ownerWithLead('berto3@example.com');

        $this->actingAs($ana)->post('/mensajes/marcar-todos-leidos');

        $this->assertNull($leadDeBerto->fresh()->read_at);
    }

    public function test_export_only_contains_own_leads(): void
    {
        [$ana, $leadDeAna] = $this->ownerWithLead('ana4@example.com');
        [, $leadDeBerto]   = $this->ownerWithLead('berto4@example.com');

        $response = $this->actingAs($ana)->get('/mensajes/exportar');

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString($leadDeAna->email, $csv);
        $this->assertStringNotContainsString($leadDeBerto->email, $csv);
    }

    public function test_guest_cannot_reach_the_inbox(): void
    {
        $this->get('/mensajes')->assertRedirect('/login');
    }
}
