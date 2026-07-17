<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La app fuerza el locale 'es' (config('app.locale')) pero, sin `lang/es/*.php`,
 * Laravel no encuentra traducción y devuelve la clave sin traducir (p.ej.
 * "validation.required") en vez de un mensaje legible. Estos tests comprueban
 * que los mensajes de validación/autenticación que ve el cliente están en
 * español real, no la clave interna de Laravel.
 */
class SpanishValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_validation_errors_are_translated(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors('name');
        $errors = session('errors')->getBag('default');

        $this->assertSame('El campo nombre es obligatorio.', $errors->first('name'));
        $this->assertStringNotContainsString('validation.', $errors->first('name'));
    }

    public function test_login_failure_message_is_translated(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $message = session('errors')->getBag('default')->first('email');

        $this->assertSame('Esas credenciales no coinciden con nuestros registros.', $message);
    }

    public function test_delete_account_wrong_password_message_is_translated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/perfil')->delete('/perfil', [
            'password' => 'wrong-password',
        ]);

        $errors = $this->actingAs($user)->get('/perfil')
            ->original->getData()['page']['props']['errors'];

        $this->assertSame('La contraseña es incorrecta.', $errors->userDeletion->password ?? null);
    }
}
