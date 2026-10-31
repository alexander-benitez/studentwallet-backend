<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_pueden_consultar_categorias_sin_autenticacion()
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(401);
    }

    public function test_usuario_puede_consultar_categorias_globales_y_propias()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::create(['name' => 'Comida', 'type' => 'gasto', 'is_default' => true, 'user_id' => null]);
        Category::create(['name' => 'Beca', 'type' => 'ingreso', 'is_default' => false, 'user_id' => $user->id]);
        Category::create(['name' => 'Tutorías', 'type' => 'ingreso', 'is_default' => false, 'user_id' => $otherUser->id]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonFragment(['name' => 'Comida'])
                 ->assertJsonFragment(['name' => 'Beca'])
                 ->assertJsonMissing(['name' => 'Tutorías']);
    }

    public function test_usuario_puede_crear_categoria_propia_con_datos_validos()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/categories', [
                             'name' => 'Transporte Bus',
                             'type' => 'gasto',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.name', 'Transporte Bus')
                 ->assertJsonPath('data.type', 'gasto')
                 ->assertJsonPath('data.is_default', false);

        $this->assertDatabaseHas('categories', [
            'name'    => 'Transporte Bus',
            'user_id' => $user->id,
        ]);
    }

    public function test_no_se_puede_crear_categoria_duplicada_para_el_mismo_usuario_y_tipo()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        Category::create([
            'name'       => 'Libros',
            'type'       => 'gasto',
            'user_id'    => $user->id,
            'is_default' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/categories', [
                             'name' => 'Libros',
                             'type' => 'gasto',
                         ]);

        $response->assertStatus(422);
    }

    public function test_falla_creacion_con_tipo_invalido()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/categories', [
                             'name' => 'Invalida',
                             'type' => 'otro_tipo',
                         ]);

        $response->assertStatus(422);
    }

    public function test_se_impide_modificar_categoria_global()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $global = Category::create([
            'name'       => 'Alimentación General',
            'type'       => 'gasto',
            'is_default' => true,
            'user_id'    => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/categories/{$global->id}", [
                             'name' => 'Nuevo Nombre',
                         ]);

        $response->assertStatus(403);
    }

    public function test_se_impide_eliminar_categoria_global()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $global = Category::create([
            'name'       => 'Salario',
            'type'       => 'ingreso',
            'is_default' => true,
            'user_id'    => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->deleteJson("/api/categories/{$global->id}");

        $response->assertStatus(403);
    }

    public function test_usuario_no_puede_modificar_categoria_de_otro_usuario()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $categoryB = Category::create([
            'name'       => 'Gym User B',
            'type'       => 'gasto',
            'is_default' => false,
            'user_id'    => $userB->id,
        ]);

        $tokenA = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
                         ->putJson("/api/categories/{$categoryB->id}", [
                             'name' => 'Hackeado',
                         ]);

        $response->assertStatus(403);
    }

    public function test_usuario_puede_actualizar_y_eliminar_su_propia_categoria()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name'       => 'Libros',
            'type'       => 'gasto',
            'is_default' => false,
            'user_id'    => $user->id,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $responsePut = $this->withHeader('Authorization', 'Bearer ' . $token)
                            ->putJson("/api/categories/{$category->id}", [
                                'name' => 'Libros y Copias',
                            ]);

        $responsePut->assertStatus(200)
                    ->assertJsonPath('data.name', 'Libros y Copias');

        $responseDel = $this->withHeader('Authorization', 'Bearer ' . $token)
                            ->deleteJson("/api/categories/{$category->id}");

        $responseDel->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_relacion_usuario_con_sus_categorias()
    {
        $user = User::factory()->create();
        Category::create([
            'name'       => 'Ahorro Beca',
            'type'       => 'ingreso',
            'user_id'    => $user->id,
            'is_default' => false,
        ]);

        $this->assertCount(1, $user->categories);
        $this->assertEquals('Ahorro Beca', $user->categories->first()->name);
    }
}
