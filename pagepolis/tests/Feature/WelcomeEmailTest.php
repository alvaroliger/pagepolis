<?php

namespace Tests\Feature;

use App\Mail\WelcomeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
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

        Mail::assertQueued(WelcomeMail::class, function (WelcomeMail $mail) {
            return $mail->user->email === 'ana@example.com';
        });
    }

    public function test_welcome_email_has_correct_subject(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name'                  => 'Carlos López',
            'email'                 => 'carlos@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Mail::assertQueued(WelcomeMail::class, function (WelcomeMail $mail) {
            $envelope = $mail->envelope();
            return str_contains($envelope->subject, 'Bienvenido');
        });
    }

    public function test_only_one_welcome_email_queued_per_registration(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name'                  => 'María Torres',
            'email'                 => 'maria@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Mail::assertQueued(WelcomeMail::class, 1);
    }
}
