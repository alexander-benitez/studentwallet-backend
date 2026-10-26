<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase; // Limpia la base de datos en cada prueba

    public function test_usuario_puede_registrarse()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('status', 'success');
                 
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_no_puede_registrarse_email_duplicado()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User 2',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422); // Error de validación
    }

    public function test_usuario_puede_hacer_login()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    public function test_login_falla_con_credenciales_incorrectas()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'noexiste@example.com',
            'password' => 'incorrecta'
        ]);

        $response->assertStatus(401); // No autorizado
    }
}