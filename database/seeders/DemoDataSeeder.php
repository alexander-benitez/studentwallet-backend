<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear 1 Administrador
        User::create([
            'name' => 'Admin de Prueba',
            'email' => 'admin@prueba.com',
            'password' => Hash::make('password'),
            'rol' => 'administrador',
            'is_active' => 1,
        ]);

        // 2. Crear 3 Usuarios normales
        $usuarios = [
            ['name' => 'Estudiante 1', 'email' => 'estudiante1@prueba.com'],
            ['name' => 'Estudiante 2', 'email' => 'estudiante2@prueba.com'],
            ['name' => 'Estudiante 3', 'email' => 'estudiante3@prueba.com'],
        ];

        foreach ($usuarios as $u) {
            $user = User::create([
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make('password'),
                'rol' => 'estudiante',
                'is_active' => 1,
            ]);

            // 3. Crear Categorías (name y type)
            $catIngreso = Category::create(['user_id' => $user->id, 'name' => 'Beca', 'type' => 'ingreso']);
            $catGasto = Category::create(['user_id' => $user->id, 'name' => 'Comida', 'type' => 'gasto']);

            // 4. Crear Transacciones (amount, type, description, date)
            Transaction::create([
                'user_id' => $user->id, 
                'category_id' => $catIngreso->id, 
                'amount' => 500.00, 
                'type' => 'ingreso', 
                'description' => 'Beca mensual',
                'date' => now()->format('Y-m-d')
            ]);
            Transaction::create([
                'user_id' => $user->id, 
                'category_id' => $catGasto->id, 
                'amount' => 50.00, 
                'type' => 'gasto', 
                'description' => 'Almuerzo',
                'date' => now()->format('Y-m-d')
            ]);
            Transaction::create([
                'user_id' => $user->id, 
                'category_id' => $catGasto->id, 
                'amount' => 120.00, 
                'type' => 'gasto', 
                'description' => 'Supermercado',
                'date' => now()->format('Y-m-d')
            ]);
        }
    }
}