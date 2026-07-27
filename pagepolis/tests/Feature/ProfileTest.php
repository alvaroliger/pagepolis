<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/perfil');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/perfil', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/perfil');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/perfil', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/perfil');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/perfil', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        // User usa SoftDeletes: la cuenta se marca como eliminada (soft delete).
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/perfil')
            ->delete('/perfil', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', ['password'])
            ->assertRedirect('/perfil');

        $this->assertNotNull($user->fresh());
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/perfil')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'nueva-contraseña-segura',
                'password_confirmation' => 'nueva-contraseña-segura',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/perfil');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nueva-contraseña-segura', $user->fresh()->password));
    }

    public function test_correct_current_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        // El controlador valida con el error bag "updatePassword"
        // (ProfileController::update usa "userDeletion", PasswordController::update
        // usa "updatePassword"): el frontend (Profile/Edit.tsx) depende de que los
        // errores lleguen anidados bajo ese nombre para poder mostrarlos.
        $response = $this
            ->actingAs($user)
            ->from('/perfil')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'nueva-contraseña-segura',
                'password_confirmation' => 'nueva-contraseña-segura',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', ['current_password'])
            ->assertRedirect('/perfil');
    }
}
