<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_pueden_consultar_movimientos_sin_token()
    {
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(401);
    }

    public function test_usuario_puede_crear_un_movimiento_valido()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $category = Category::create([
            'name'       => 'Alimentación',
            'type'       => 'gasto',
            'is_default' => true,
            'user_id'    => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'category_id' => $category->id,
                'amount'      => 15.50,
                'type'        => 'gasto',
                'description' => 'Almuerzo universitario',
                'date'        => '2026-10-31',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', 15.50)
            ->assertJsonPath('data.type', 'gasto')
            ->assertJsonPath('data.description', 'Almuerzo universitario');

        $this->assertDatabaseHas('transactions', [
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'amount'      => 15.50,
        ]);
    }

    public function test_rechaza_creacion_con_monto_menor_o_igual_a_cero()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $category = Category::create([
            'name'       => 'Sueldo',
            'type'       => 'ingreso',
            'is_default' => true,
            'user_id'    => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'category_id' => $category->id,
                'amount'      => 0,
                'type'        => 'ingreso',
                'date'        => '2026-10-31',
            ]);

        $response->assertStatus(422);

        $responseNeg = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'category_id' => $category->id,
                'amount'      => -20.00,
                'type'        => 'ingreso',
                'date'        => '2026-10-31',
            ]);

        $responseNeg->assertStatus(422);
    }

    public function test_rechaza_creacion_si_tipo_de_movimiento_no_coincide_con_categoria()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $categoryGasto = Category::create([
            'name'       => 'Transporte',
            'type'       => 'gasto',
            'is_default' => true,
            'user_id'    => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'category_id' => $categoryGasto->id,
                'amount'      => 50.00,
                'type'        => 'ingreso',
                'date'        => '2026-10-31',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_rechaza_creacion_si_categoria_pertenece_a_otro_usuario()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $tokenA = $userA->createToken('test')->plainTextToken;

        $categoryB = Category::create([
            'name'       => 'Privada de B',
            'type'       => 'gasto',
            'is_default' => false,
            'user_id'    => $userB->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/transactions', [
                'category_id' => $categoryB->id,
                'amount'      => 10.00,
                'type'        => 'gasto',
                'date'        => '2026-10-31',
            ]);

        $response->assertStatus(403);
    }

    public function test_usuario_solo_puede_consultar_sus_propios_movimientos()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $category = Category::create([
            'name'       => 'Comida',
            'type'       => 'gasto',
            'is_default' => true,
            'user_id'    => null,
        ]);

        Transaction::create([
            'user_id'     => $userA->id,
            'category_id' => $category->id,
            'amount'      => 12.00,
            'type'        => 'gasto',
            'date'        => '2026-10-31',
            'description' => 'Cena de User A',
        ]);

        Transaction::create([
            'user_id'     => $userB->id,
            'category_id' => $category->id,
            'amount'      => 30.00,
            'type'        => 'gasto',
            'date'        => '2026-10-31',
            'description' => 'Gasto secreto User B',
        ]);

        $tokenA = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['description' => 'Cena de User A'])
            ->assertJsonMissing(['description' => 'Gasto secreto User B']);
    }

    public function test_usuario_no_puede_modificar_ni_eliminar_movimiento_ajeno()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $category = Category::create(['name' => 'General', 'type' => 'gasto', 'is_default' => true, 'user_id' => null]);

        $txB = Transaction::create([
            'user_id'     => $userB->id,
            'category_id' => $category->id,
            'amount'      => 20.00,
            'type'        => 'gasto',
            'date'        => '2026-10-31',
        ]);

        $tokenA = $userA->createToken('test')->plainTextToken;

        $resPut = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->putJson("/api/transactions/{$txB->id}", ['amount' => 99.00]);
        $resPut->assertStatus(403);

        $resDel = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->deleteJson("/api/transactions/{$txB->id}");
        $resDel->assertStatus(403);
    }
}
