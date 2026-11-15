<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_puede_obtener_respaldo_sin_autenticacion()
    {
        $response = $this->getJson('/api/user/backup');

        $response->assertStatus(401);
    }

    public function test_usuario_puede_generar_su_respaldo()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $category = Category::create([
            'name' => 'Comida',
            'type' => 'gasto',
            'user_id' => $user->id,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 50.00,
            'type' => 'gasto',
            'date' => '2026-11-01',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/backup');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'categories',
                    'transactions',
                    'budgets',
                    'goals',
                    'contributions',
                ],
            ]);
    }
}
