<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_crear_una_meta()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->postJson('/api/goals', [
            'name' => 'Comprar laptop',
            'target_amount' => 1000,
            'deadline' => '2026-12-31'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Comprar laptop');
                 
        $this->assertDatabaseHas('goals', ['name' => 'Comprar laptop']);
    }

    public function test_un_usuario_puede_aportar_a_su_meta_activa()
    {
        $user = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $user->id,
            'name' => 'Viaje',
            'target_amount' => 500,
            'current_amount' => 0,
            'status' => 'active'
        ]);

        $response = $this->actingAs($user)->postJson("/api/goals/{$goal->id}/contributions", [
            'amount' => 100,
            'note' => 'Primer aporte'
        ]);

        $response->assertStatus(201);
        $this->assertEquals(100, $goal->fresh()->current_amount);
    }
}
