<?php

namespace Tests\Feature;

use App\Mail\SubscriptionExpiringMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for the pagepolis:expiry-reminders artisan command.
 *
 * The command runs daily and sends a retention email to users whose grace period
 * ends in approximately 7 days. Zero test coverage previously existed for this path,
 * making it a silent failure risk for subscription renewals.
 */
class ExpiryReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_expiring_in_7_days_receives_reminder(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'grace_period_ends_at' => now()->addDays(7),
        ]);

        $this->artisan('pagepolis:expiry-reminders')->assertSuccessful();

        Mail::assertQueued(
            SubscriptionExpiringMail::class,
            fn ($m) => $m->hasTo($user->email)
        );
    }

    public function test_user_without_grace_period_is_not_reminded(): void
    {
        Mail::fake();

        User::factory()->create(['grace_period_ends_at' => null]);

        $this->artisan('pagepolis:expiry-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_user_expiring_too_soon_or_too_late_is_not_reminded(): void
    {
        Mail::fake();

        // Expires tomorrow — past the 7-day reminder window
        User::factory()->create(['grace_period_ends_at' => now()->addDay()]);

        // Expires in 30 days — too far away for this reminder
        User::factory()->create(['grace_period_ends_at' => now()->addDays(30)]);

        // Already expired — nothing to remind about
        User::factory()->create(['grace_period_ends_at' => now()->subDay()]);

        $this->artisan('pagepolis:expiry-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_days_left_passed_to_mailable_is_at_least_one(): void
    {
        Mail::fake();

        // Grace period expires exactly at the start of the 7-day window.
        // Carbon's ceil() could round down to 0; max(1, ...) must prevent that.
        User::factory()->create([
            'grace_period_ends_at' => now()->addDays(7)->startOfDay(),
        ]);

        $this->artisan('pagepolis:expiry-reminders');

        Mail::assertQueued(
            SubscriptionExpiringMail::class,
            fn (SubscriptionExpiringMail $m) => $m->daysLeft >= 1
        );
    }

    public function test_all_users_in_window_receive_reminder(): void
    {
        Mail::fake();

        User::factory()->count(3)->create([
            'grace_period_ends_at' => now()->addDays(7),
        ]);

        $this->artisan('pagepolis:expiry-reminders')->assertSuccessful();

        Mail::assertQueued(SubscriptionExpiringMail::class, 3);
    }
}
