<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;

class BalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_puede_consultar_saldo_sin_token()
    {
        $response = $this->getJson('/api/balance');
        $response->assertStatus(401);
    }

    public function test_calculo_de_saldo_inicial_en_cero()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/balance');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_incomes', 0)
            ->assertJsonPath('data.total_expenses', 0)
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.is_negative', false);
    }

    public function test_calculo_correcto_con_multiples_ingresos_y_gastos()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $catIngreso = Category::create(['name' => 'Beca', 'type' => 'ingreso', 'is_default' => true, 'user_id' => null]);
        $catGasto = Category::create(['name' => 'Comida', 'type' => 'gasto', 'is_default' => true, 'user_id' => null]);

        // Ingresos: 100 + 50 = 150
        Transaction::create(['user_id' => $user->id, 'category_id' => $catIngreso->id, 'amount' => 100.00, 'type' => 'ingreso', 'date' => '2026-10-01']);
        Transaction::create(['user_id' => $user->id, 'category_id' => $catIngreso->id, 'amount' => 50.00, 'type' => 'ingreso', 'date' => '2026-10-05']);

        // Gastos: 30 + 20 = 50
        Transaction::create(['user_id' => $user->id, 'category_id' => $catGasto->id, 'amount' => 30.00, 'type' => 'gasto', 'date' => '2026-10-10']);
        Transaction::create(['user_id' => $user->id, 'category_id' => $catGasto->id, 'amount' => 20.00, 'type' => 'gasto', 'date' => '2026-10-12']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/balance');

        // Saldo esperado: 150 - 50 = 100
        $response->assertStatus(200)
            ->assertJsonPath('data.total_incomes', 150)
            ->assertJsonPath('data.total_expenses', 50)
            ->assertJsonPath('data.balance', 100)
            ->assertJsonPath('data.is_negative', false);
    }

    public function test_calculo_de_saldo_negativo()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $catIngreso = Category::create(['name' => 'Beca', 'type' => 'ingreso', 'is_default' => true, 'user_id' => null]);
        $catGasto = Category::create(['name' => 'Libros', 'type' => 'gasto', 'is_default' => true, 'user_id' => null]);

        // Ingreso: 40 | Gasto: 60 => Saldo: -20
        Transaction::create(['user_id' => $user->id, 'category_id' => $catIngreso->id, 'amount' => 40.00, 'type' => 'ingreso', 'date' => '2026-10-01']);
        Transaction::create(['user_id' => $user->id, 'category_id' => $catGasto->id, 'amount' => 60.00, 'type' => 'gasto', 'date' => '2026-10-02']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/balance');

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', -20)
            ->assertJsonPath('data.is_negative', true);
    }

    public function test_aislamiento_de_saldo_entre_usuarios()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $cat = Category::create(['name' => 'General', 'type' => 'ingreso', 'is_default' => true, 'user_id' => null]);

        Transaction::create(['user_id' => $userA->id, 'category_id' => $cat->id, 'amount' => 500.00, 'type' => 'ingreso', 'date' => '2026-10-01']);
        Transaction::create(['user_id' => $userB->id, 'category_id' => $cat->id, 'amount' => 1000.00, 'type' => 'ingreso', 'date' => '2026-10-01']);

        $tokenA = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/balance');

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 500);
    }

    public function test_modificar_o_eliminar_movimiento_actualiza_el_saldo()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $cat = Category::create(['name' => 'Sueldo', 'type' => 'ingreso', 'is_default' => true, 'user_id' => null]);

        $tx = Transaction::create([
            'user_id'     => $user->id,
            'category_id' => $cat->id,
            'amount'      => 100.00,
            'type'        => 'ingreso',
            'date'        => '2026-10-01',
        ]);

        // 1. Saldo inicial: 100
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/balance');
        $res1->assertJsonPath('data.balance', 100);

        // 2. Modificar el monto a 250 a través de la API
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/transactions/{$tx->id}", ['amount' => 250.00]);

        $res2 = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/balance');
        $res2->assertJsonPath('data.balance', 250);

        // 3. Eliminar el movimiento a través de la API
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/transactions/{$tx->id}");

        $res3 = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/balance');
        $res3->assertJsonPath('data.balance', 0);
    }

    public function test_filtro_por_rango_de_fechas()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $cat = Category::create(['name' => 'Varios', 'type' => 'ingreso', 'is_default' => true, 'user_id' => null]);

        // Octubre: 80 | Noviembre: 120
        Transaction::create(['user_id' => $user->id, 'category_id' => $cat->id, 'amount' => 80.00, 'type' => 'ingreso', 'date' => '2026-10-15']);
        Transaction::create(['user_id' => $user->id, 'category_id' => $cat->id, 'amount' => 120.00, 'type' => 'ingreso', 'date' => '2026-11-05']);

        // Consultar solo rango de octubre
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/balance?start_date=2026-10-01&end_date=2026-10-31');

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 80)
            ->assertJsonPath('data.total_incomes', 80);
    }
}
