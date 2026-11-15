<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_crear_un_presupuesto()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Comida',
            'type' => 'gasto',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/budgets', [
            'category_id' => $category->id,
            'amount' => 200,
            'month' => 11,
            'year' => 2026,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budgets', ['amount' => 200]);
    }

    public function test_no_se_puede_crear_presupuesto_duplicado_para_la_misma_categoria_mes_y_año()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Comida',
            'type' => 'gasto',
            'user_id' => $user->id,
        ]);

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 200,
            'month' => 11,
            'year' => 2026,
        ]);

        $response = $this->actingAs($user)->postJson('/api/budgets', [
            'category_id' => $category->id,
            'amount' => 300,
            'month' => 11,
            'year' => 2026,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('budgets', 1);
    }
}
