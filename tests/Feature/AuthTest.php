<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_registrarse()
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonMissing(['password'])
                 ->assertJsonMissing(['password_hash']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_no_puede_registrarse_email_duplicado()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/register', [
            'name'     => 'Test User 2',
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_usuario_puede_hacer_login()
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonMissing(['password']);
    }

    public function test_login_falla_con_credenciales_incorrectas()
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'noexiste@example.com',
            'password' => 'incorrecta',
        ]);

        $response->assertStatus(401);
    }

    public function test_ruta_protegida_rechaza_acceso_sin_token()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_usuario_autenticado_puede_consultar_su_perfil()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/user');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.email', $user->email)
                 ->assertJsonMissing(['password']);
    }

    public function test_logout_invalida_el_token_actual()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        // Ejecutar logout
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        // Verificar que el token fue eliminado de la tabla personal_access_tokens
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Limpiar el estado en memoria de la sesión de testing
        app('auth')->forgetGuards();

        // Al intentar acceder de nuevo, el token ya no existe en BD
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                                   ->getJson('/api/user');

        $subsequentResponse->assertStatus(401);
    }
}
