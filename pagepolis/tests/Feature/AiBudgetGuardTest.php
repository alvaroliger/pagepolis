<?php

namespace Tests\Feature;

use App\Models\AiSpend;
use App\Services\AiBudgetGuard;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiBudgetGuardTest extends TestCase
{
    use RefreshDatabase;

    private function guard(): AiBudgetGuard
    {
        return app(AiBudgetGuard::class);
    }

    public function test_estimates_cost_per_model(): void
    {
        $g = $this->guard();

        // Opus: 15/MTok in, 75/MTok out → 1M in + 1M out = 15 + 75 = 90
        $this->assertEqualsWithDelta(90.0, $g->estimateCost('claude-opus-4-8', 1_000_000, 1_000_000), 0.001);
        // Sonnet: 3/MTok in, 15/MTok out → 3 + 15 = 18
        $this->assertEqualsWithDelta(18.0, $g->estimateCost('claude-sonnet-4-6', 1_000_000, 1_000_000), 0.001);
    }

    public function test_record_accumulates_spend(): void
    {
        $g = $this->guard();
        $g->record('claude-sonnet-4-6', 1_000_000, 1_000_000); // 18 USD
        $g->record('claude-sonnet-4-6', 1_000_000, 0);          // +3 USD

        $this->assertEqualsWithDelta(21.0, $g->monthToDateUsd(), 0.001);
        $this->assertEqualsWithDelta(21.0, $g->todayUsd(), 0.001);
    }

    public function test_blocks_when_monthly_budget_exceeded(): void
    {
        config(['services.anthropic.monthly_budget_usd' => 10, 'services.anthropic.daily_budget_usd' => 0]);
        AiSpend::create(['day' => now()->startOfMonth()->toDateString(), 'est_cost_usd' => 12]);

        $g = $this->guard();
        $this->assertFalse($g->isWithinBudget());
        $this->assertSame('mensual', $g->blockedReason());
    }

    public function test_blocks_when_daily_budget_exceeded(): void
    {
        config(['services.anthropic.monthly_budget_usd' => 1000, 'services.anthropic.daily_budget_usd' => 5]);
        AiSpend::create(['day' => now()->toDateString(), 'est_cost_usd' => 6]);

        $g = $this->guard();
        $this->assertFalse($g->isWithinBudget());
        $this->assertSame('diario', $g->blockedReason());
    }

    public function test_no_budget_means_unlimited(): void
    {
        config(['services.anthropic.monthly_budget_usd' => 0, 'services.anthropic.daily_budget_usd' => 0]);
        AiSpend::create(['day' => now()->toDateString(), 'est_cost_usd' => 99999]);

        $this->assertTrue($this->guard()->isWithinBudget());
    }

    public function test_anthropic_service_throws_when_over_budget_without_calling_api(): void
    {
        config(['services.anthropic.monthly_budget_usd' => 1, 'services.anthropic.daily_budget_usd' => 0]);
        AiSpend::create(['day' => now()->toDateString(), 'est_cost_usd' => 5]);

        $this->expectException(\Exception::class);

        // El guardián bloquea ANTES de cualquier llamada HTTP, así que no se gasta nada.
        app(AnthropicService::class)->generateWebsite('una web de prueba');
    }
}
