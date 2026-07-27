<?php

namespace Tests\Feature;

use App\Jobs\DeploySite;
use App\Jobs\PurchaseDomain;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use App\Services\DinahostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * CAJA NEGRA — flujos de dominio propio: reservar (provisiona) y editar después de
 * publicado (re-despliega). Usa un admin (isSubscribed()==true sin Stripe).
 */
class DomainFlowTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'role' => 'admin']);
    }

    private function project(User $u): Project
    {
        return Project::create([
            'user_id' => $u->id, 'name' => 'Web', 'status' => 'draft',
            'html' => '<h1>Hola</h1>', 'css' => 'h1{}', 'js' => '',
        ]);
    }

    public function test_reserving_domain_provisions_it(): void
    {
        Queue::fake();
        $u = $this->subscribedUser();
        $p = $this->project($u);

        $this->actingAs($u)->postJson('/dominios/reservar', [
            'domain' => 'minegocio.com', 'type' => 'custom', 'project_id' => $p->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('domains', ['project_id' => $p->id, 'domain' => 'minegocio.com', 'status' => 'pending']);
        Queue::assertPushed(PurchaseDomain::class);
    }

    public function test_reserving_unsubscribed_goes_to_payment_not_blocked(): void
    {
        Queue::fake();
        $u = User::factory()->create(['email_verified_at' => now(), 'role' => 'user']); // no suscrito
        $p = $this->project($u);

        // No debe bloquear el embudo: reserva OK y pide pago; provisiona tras pagar.
        $this->actingAs($u)->postJson('/dominios/reservar', [
            'domain' => 'minegocio.com', 'type' => 'custom', 'project_id' => $p->id,
        ])->assertOk()->assertJson(['needs_payment' => true]);

        $this->assertDatabaseHas('domains', ['project_id' => $p->id, 'status' => 'pending']);
        Queue::assertNothingPushed();   // aún no se provisiona (falta pagar)
    }

    public function test_editing_redeploys_to_live_custom_domain(): void
    {
        Queue::fake();
        $u = $this->subscribedUser();
        $p = $this->project($u);
        Domain::create(['user_id' => $u->id, 'project_id' => $p->id, 'domain' => 'minegocio.com', 'type' => 'custom', 'status' => 'active']);

        $this->actingAs($u)->postJson("/editor/{$p->id}/guardar", ['html' => '<h1>Nuevo</h1>'])
            ->assertOk();

        Queue::assertPushed(DeploySite::class);   // la web en internet se actualiza
    }

    public function test_editing_without_domain_does_not_deploy(): void
    {
        Queue::fake();
        $u = $this->subscribedUser();
        $p = $this->project($u);

        $this->actingAs($u)->postJson("/editor/{$p->id}/guardar", ['html' => '<h1>Nuevo</h1>'])->assertOk();

        Queue::assertNotPushed(DeploySite::class);
    }

    public function test_free_path_site_does_not_need_redeploy(): void
    {
        Queue::fake();
        $u = $this->subscribedUser();
        $p = $this->project($u);
        // Sitio gratis en /s/{slug}: se sirve en vivo desde la BD, no es estático.
        Domain::create(['user_id' => $u->id, 'project_id' => $p->id, 'domain' => $p->slug, 'type' => 'path', 'status' => 'active']);

        $this->actingAs($u)->postJson("/editor/{$p->id}/guardar", ['html' => '<h1>Nuevo</h1>'])->assertOk();

        Queue::assertNotPushed(DeploySite::class);
    }

    public function test_cannot_reserve_domain_for_another_users_project(): void
    {
        Queue::fake();
        $owner  = $this->subscribedUser();
        $hacker = $this->subscribedUser();
        $p      = $this->project($owner);

        // A different authenticated user tries to attach a domain to a project they don't own.
        $this->actingAs($hacker)->postJson('/dominios/reservar', [
            'domain'     => 'stolen.com',
            'type'       => 'custom',
            'project_id' => $p->id,
        ])->assertStatus(404);

        $this->assertDatabaseMissing('domains', ['domain' => 'stolen.com']);
        Queue::assertNothingPushed();
    }

    public function test_check_reports_available_domain(): void
    {
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkAvailability')->with('librenegocio.com')
                ->andReturn(['available' => true, 'price' => 12.00]);
        });

        $this->actingAs($this->subscribedUser())
            ->postJson('/dominios/verificar', ['domain' => 'librenegocio.com'])
            ->assertOk()
            ->assertJson(['available' => true]);
    }

    public function test_check_reports_taken_domain(): void
    {
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkAvailability')->with('google-clone.com')
                ->andReturn(['available' => false, 'price' => 12.00]);
        });

        $this->actingAs($this->subscribedUser())
            ->postJson('/dominios/verificar', ['domain' => 'google-clone.com'])
            ->assertOk()
            ->assertJson(['available' => false]);
    }

    public function test_check_blocks_forbidden_domain_without_calling_registrar(): void
    {
        $this->actingAs($this->subscribedUser())
            ->postJson('/dominios/verificar', ['domain' => 'google.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    public function test_check_rejects_invalid_domain_format(): void
    {
        $this->actingAs($this->subscribedUser())
            ->postJson('/dominios/verificar', ['domain' => 'not a domain'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    public function test_check_requires_authentication(): void
    {
        $this->postJson('/dominios/verificar', ['domain' => 'minegocio.com'])
            ->assertStatus(401);
    }
}
