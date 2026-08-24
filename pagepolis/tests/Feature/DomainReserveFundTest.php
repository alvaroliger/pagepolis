<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainReserveMovement;
use App\Models\Project;
use App\Models\User;
use App\Services\DomainReserveFund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fondo de reserva de dominios: la pieza que resuelve el descuadre entre lo que
 * cobra el registrador (anual por adelantado) y lo que se cobra al cliente
 * (por ciclos). Verifica la matemática del margen, la idempotencia (webhooks
 * repetidos), la auditabilidad (histórico append-only) y los casos límite.
 */
class DomainReserveFundTest extends TestCase
{
    use RefreshDatabase;

    private function fund(): DomainReserveFund
    {
        return app(DomainReserveFund::class);
    }

    private function domain(): Domain
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        return Domain::create([
            'user_id'    => $user->id,
            'project_id' => Project::create(['user_id' => $user->id, 'name' => 'X', 'status' => 'published'])->id,
            'domain'     => 'cliente-' . uniqid() . '.com',
            'type'       => 'custom',
            'status'     => 'active',
        ]);
    }

    // ── Matemática del margen: 12 mensualidades cubren la renovación anual ──────
    public function test_doce_mensualidades_superan_el_coste_anual(): void
    {
        config(['pagepolis.domain.annual_cost_cents' => 1200]); // 12 €/año
        config(['pagepolis.domain.reserve_margin' => 1.25]);    // +25 % colchón

        $domain = $this->domain();
        $fund   = $this->fund();

        for ($mes = 1; $mes <= 12; $mes++) {
            $fund->accrue($domain, 'month', "invoice_mes_{$mes}");
        }

        // Objetivo con margen = 1200 * 1.25 = 1500; la porción mensual es
        // ceil(1500/12)=125, x12 = 1500 >= coste anual (1200) con colchón.
        $this->assertSame(1500, $fund->balance($domain));
        $this->assertTrue($fund->canCover($domain, 1200), 'debe cubrir el coste anual');
    }

    public function test_la_porcion_mensual_es_menor_que_una_cuota_tipica(): void
    {
        config(['pagepolis.domain.annual_cost_cents' => 1200]);
        config(['pagepolis.domain.reserve_margin' => 1.25]);

        // 125 cént./mes apartados; la cuota del cliente (varios €/mes) es mucho
        // mayor, así que el fondo nunca se come el margen de la plataforma.
        $this->assertSame(125, $this->fund()->accrualForCycle('month'));
        $this->assertSame(1500, $this->fund()->accrualForCycle('year')); // anual: todo de una
    }

    // ── Idempotencia: un webhook repetido NO cuenta dos veces ──────────────────
    public function test_accrual_es_idempotente_por_referencia(): void
    {
        $domain = $this->domain();
        $fund   = $this->fund();

        $first  = $fund->accrue($domain, 'month', 'invoice_ABC');
        $second = $fund->accrue($domain, 'month', 'invoice_ABC'); // mismo webhook, reintento

        $this->assertNotNull($first);
        $this->assertNull($second, 'la segunda entrega del mismo cobro no debe apuntar nada');
        $this->assertSame(1, DomainReserveMovement::where('domain_id', $domain->id)->count());
    }

    // ── Renovación: debita el fondo con el precio REAL del registrador ─────────
    public function test_renovacion_debita_el_fondo_con_precio_real(): void
    {
        config(['pagepolis.domain.annual_cost_cents' => 1200]);
        config(['pagepolis.domain.reserve_margin' => 1.25]);

        $domain = $this->domain();
        $fund   = $this->fund();
        for ($m = 1; $m <= 12; $m++) {
            $fund->accrue($domain, 'month', "inv_{$m}");
        }
        $this->assertSame(1500, $fund->balance($domain));

        // El registrador cobra en vivo 1300 (subió respecto a los 1200 estimados).
        $this->assertTrue($fund->canCover($domain, 1300));
        $fund->debitForRegistrar($domain, 'renewal', 1300, 'renew_2027');

        // Queda 200 de colchón para el siguiente año.
        $this->assertSame(200, $fund->balance($domain));
    }

    public function test_renovacion_es_idempotente_ante_reintento_del_job(): void
    {
        $domain = $this->domain();
        $fund   = $this->fund();
        $fund->accrue($domain, 'year', 'inv_anual');       // aparta 1500 de una

        $fund->debitForRegistrar($domain, 'renewal', 1200, 'renew_X');
        $fund->debitForRegistrar($domain, 'renewal', 1200, 'renew_X'); // el job reintenta

        // Solo debita una vez, aunque el job se reintente.
        $this->assertSame(300, $fund->balance($domain));
        $this->assertSame(1, DomainReserveMovement::where('domain_id', $domain->id)
            ->where('type', 'renewal')->count());
    }

    // ── Auditabilidad: cada fila lleva su balance_after, en orden ──────────────
    public function test_historico_es_auditable_con_balance_en_cada_fila(): void
    {
        config(['pagepolis.domain.annual_cost_cents' => 1200]);
        config(['pagepolis.domain.reserve_margin' => 1.25]);

        $domain = $this->domain();
        $fund   = $this->fund();

        $fund->accrue($domain, 'month', 'i1');            // +125 -> 125
        $fund->accrue($domain, 'month', 'i2');            // +125 -> 250
        $fund->debitForRegistrar($domain, 'purchase', 1200, 'buy'); // -1200 -> -950
        $fund->refund($domain, 1200, 'buy_refund');       // +1200 -> 250 (compra fallida devuelta)

        $saldos = DomainReserveMovement::where('domain_id', $domain->id)
            ->orderBy('id')->pluck('balance_after_cents')->all();

        $this->assertSame([125, 250, -950, 250], $saldos);
        // El saldo final = suma de movimientos (invariante).
        $this->assertSame(250, $fund->balance($domain));
    }

    // ── Caso límite: primera compra deja el fondo en negativo (esperado) ───────
    public function test_primera_compra_puede_dejar_negativo_y_el_margen_lo_recupera(): void
    {
        config(['pagepolis.domain.annual_cost_cents' => 1200]);
        config(['pagepolis.domain.reserve_margin' => 1.25]);

        $domain = $this->domain();
        $fund   = $this->fund();

        // Se compra el dominio al contratar, con solo el primer mes apartado.
        $fund->accrue($domain, 'month', 'inv_1');            // +125
        $fund->debitForRegistrar($domain, 'purchase', 1200, 'buy'); // -1200
        $this->assertSame(-1075, $fund->balance($domain), 'negativo el primer mes: es esperado');

        // Durante el año siguiente se recupera y sobra colchón.
        for ($m = 2; $m <= 13; $m++) {
            $fund->accrue($domain, 'month', "inv_{$m}");     // +125 x12
        }
        $this->assertSame(425, $fund->balance($domain)); // -1075 + 1500
        $this->assertTrue($fund->balance($domain) >= 0);
    }
}
