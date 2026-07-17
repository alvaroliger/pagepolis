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

    public function test_delete_account_error_reaches_the_profile_page_props(): void
    {
        // La página de perfil debe recibir el error bajo `errors.userDeletion.password`
        // (así lo lee `Profile/Edit.tsx`); si no, el cliente no ve ningún aviso de por
        // qué no se pudo eliminar la cuenta tras escribir mal la contraseña.
        $user = User::factory()->create();

        $this->actingAs($user)->from('/perfil')->delete('/perfil', [
            'password' => 'wrong-password',
        ])->assertRedirect('/perfil');

        $errors = $this->actingAs($user)->get('/perfil')
            ->original->getData()['page']['props']['errors'];

        $this->assertNotEmpty($errors->userDeletion->password ?? null);
    }
}
