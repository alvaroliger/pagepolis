<?php

namespace Tests\Feature;

use App\Mail\NudgePublishMail;
use App\Mail\WelcomeMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OnboardingEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_queued_on_registration(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name'                  => 'Ana García',
            'email'                 => 'ana@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Mail::assertQueued(WelcomeMail::class, fn ($mail) => $mail->hasTo('ana@example.com'));
    }

    public function test_nudge_sent_to_user_registered_48_to_72_hours_ago_without_published_site(): void
    {
        Mail::fake();

        User::factory()->create([
            'email'      => 'inactive@example.com',
            'created_at' => now()->subHours(60),
        ]);

        $this->artisan('pagepolis:onboarding-nudges')->assertExitCode(0);

        Mail::assertQueued(NudgePublishMail::class, fn ($mail) => $mail->hasTo('inactive@example.com'));
    }

    public function test_nudge_not_sent_to_user_who_has_published_site(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'      => 'active@example.com',
            'created_at' => now()->subHours(60),
        ]);

        Project::create([
            'user_id' => $user->id,
            'name'    => 'Mi web',
            'html'    => '',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
        ]);

        $this->artisan('pagepolis:onboarding-nudges')->assertExitCode(0);

        Mail::assertNotQueued(NudgePublishMail::class, fn ($mail) => $mail->hasTo('active@example.com'));
    }

    public function test_nudge_not_sent_to_user_registered_less_than_48_hours_ago(): void
    {
        Mail::fake();

        User::factory()->create([
            'email'      => 'new@example.com',
            'created_at' => now()->subHours(24),
        ]);

        $this->artisan('pagepolis:onboarding-nudges')->assertExitCode(0);

        Mail::assertNotQueued(NudgePublishMail::class, fn ($mail) => $mail->hasTo('new@example.com'));
    }

    public function test_nudge_not_sent_to_user_registered_more_than_72_hours_ago(): void
    {
        Mail::fake();

        User::factory()->create([
            'email'      => 'old@example.com',
            'created_at' => now()->subHours(96),
        ]);

        $this->artisan('pagepolis:onboarding-nudges')->assertExitCode(0);

        Mail::assertNotQueued(NudgePublishMail::class, fn ($mail) => $mail->hasTo('old@example.com'));
    }

    public function test_welcome_mail_has_correct_subject(): void
    {
        $user = User::factory()->make(['name' => 'Carlos']);
        $mail = new WelcomeMail($user);

        $this->assertStringContainsString('Bienvenido', $mail->envelope()->subject);
    }

    public function test_nudge_mail_has_correct_subject(): void
    {
        $user = User::factory()->make();
        $mail = new NudgePublishMail($user);

        $this->assertStringContainsString('publicado', $mail->envelope()->subject);
    }
}
