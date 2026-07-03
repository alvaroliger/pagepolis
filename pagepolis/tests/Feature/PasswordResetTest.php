<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_sent_to_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_not_sent_for_unknown_email(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_reset_link_requires_valid_email_format(): void
    {
        $this->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_is_reset_with_valid_token(): void
    {
        $user  = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_old_password_no_longer_works_after_reset(): void
    {
        $user  = User::factory()->create(['password' => Hash::make('old-password-123')]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertRedirect(route('login'));

        $this->assertFalse(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token'                 => 'bad-token',
            'email'                 => $user->email,
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_fails_with_mismatched_passwords(): void
    {
        $user  = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-secret-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_fails_when_password_too_short(): void
    {
        $user  = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        // Password unchanged
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_token_is_invalidated_after_use(): void
    {
        $user  = User::factory()->create();
        $token = Password::createToken($user);

        // First use — succeeds
        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertRedirect(route('login'));

        // Same token reused — fails
        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'another-new-password',
            'password_confirmation' => 'another-new-password',
        ])->assertSessionHasErrors('email');

        // Password stays as set in first reset
        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }
}
