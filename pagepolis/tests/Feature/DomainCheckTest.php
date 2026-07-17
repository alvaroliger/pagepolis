<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DinahostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * CAJA NEGRA — /dominios/verificar: la comprobación de disponibilidad que el
 * paso "dominio propio" del publicador consulta antes de dejar continuar.
 */
class DomainCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_domain_returns_available_true_with_price(): void
    {
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkAvailability')->with('minegocio.com')
                ->andReturn(['available' => true, 'price' => 12.0]);
        });

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->postJson('/dominios/verificar', ['domain' => 'minegocio.com'])
            ->assertOk()
            ->assertJson(['available' => true, 'price' => 12.0]);
    }

    public function test_taken_domain_returns_available_false(): void
    {
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkAvailability')->with('google-clone.com')
                ->andReturn(['available' => false, 'price' => 12.0]);
        });

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->postJson('/dominios/verificar', ['domain' => 'google-clone.com'])
            ->assertOk()
            ->assertJson(['available' => false]);
    }

    public function test_forbidden_domain_is_rejected_without_calling_registrar(): void
    {
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('checkAvailability');
        });

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->postJson('/dominios/verificar', ['domain' => 'google.com'])
            ->assertStatus(422);
    }

    public function test_malformed_domain_is_rejected_by_validation(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->postJson('/dominios/verificar', ['domain' => 'not a domain'])
            ->assertStatus(422);
    }

    public function test_guest_cannot_check_domain_availability(): void
    {
        $this->postJson('/dominios/verificar', ['domain' => 'minegocio.com'])
            ->assertStatus(401);
    }
}
