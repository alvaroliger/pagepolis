<?php

namespace Tests\Feature;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_is_queued_on_registration(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name'                  => 'Ana García',
            'email'                 => 'ana@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard', absolute: false));

        Mail::assertQueued(WelcomeMail::class, fn ($m) => $m->hasTo('ana@example.com'));
    }

    public function test_welcome_email_is_queued_exactly_once_per_registration(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name'                  => 'Carlos López',
            'email'                 => 'carlos@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Mail::assertQueued(WelcomeMail::class, 1);
    }

    public function test_welcome_mail_has_correct_subject(): void
    {
        $user = User::factory()->make(['name' => 'Marta']);
        $mail = new WelcomeMail($user);

        $this->assertSame('¡Bienvenido a Pagepolis! Crea tu primera web 🚀', $mail->envelope()->subject);
    }

    public function test_welcome_mail_renders_user_name(): void
    {
        $user = User::factory()->make(['name' => 'Marta']);
        $mail = new WelcomeMail($user);

        $rendered = $mail->render();

        $this->assertStringContainsString('Marta', $rendered);
        $this->assertStringContainsString('/crear', $rendered);
    }

    public function test_failed_registration_sends_no_welcome_email(): void
    {
        Mail::fake();

        // Missing password confirmation — validation should fail
        $this->post('/register', [
            'name'     => 'Bad User',
            'email'    => 'bad@example.com',
            'password' => 'password123',
        ]);

        Mail::assertNothingQueued();
    }
}
