<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first()
            ?? User::where('email', 'admin@example.com')->first();

        if ($admin) {
            $admin->update([
                'name'      => 'Administrador General',
                'email'     => 'admin@gmail.com',
                'password'  => Hash::make('12345678'),
                'rol'       => 'administrador',
                'is_active' => true,
            ]);
            return;
        }

        User::create([
            'name'      => 'Administrador General',
            'email'     => 'admin@gmail.com',
            'password'  => Hash::make('12345678'),
            'rol'       => 'administrador',
            'is_active' => true,
        ]);
    }
}
