<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
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
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)->postJson('/api/budgets', [
            'category_id' => $category->id,
            'amount' => 200,
            'month' => 11,
            'year' => 2026
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budgets', ['amount' => 200]);
    }
}
