<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_puede_acceder_al_reporte_sin_autenticacion()
    {
        $response = $this->getJson('/api/admin/reports');

        $response->assertStatus(401);
    }

    public function test_solo_administradores_pueden_acceder_al_reporte()
    {
        $user = User::factory()->create(['rol' => 'estudiante']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/reports');

        $response->assertStatus(403);
    }

    public function test_administrador_puede_consultar_reporte_de_transacciones()
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $token = $admin->createToken('admin')->plainTextToken;

        $category = Category::create([
            'name' => 'Comida',
            'type' => 'gasto',
            'is_default' => true,
            'user_id' => null,
        ]);

        Transaction::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'type' => 'gasto',
            'date' => '2026-11-01',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'totals',
                    'monthly',
                    'categories',
                ],
            ]);
    }

    public function test_administrador_puede_consultar_reporte_con_rango_de_fechas_valido()
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $token = $admin->createToken('admin')->plainTextToken;

        $category = Category::create([
            'name' => 'Comida',
            'type' => 'gasto',
            'is_default' => true,
            'user_id' => null,
        ]);

        Transaction::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'type' => 'gasto',
            'date' => '2026-11-01',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/reports?start_date=2026-11-01&end_date=2026-11-30');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
}
