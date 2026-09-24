<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos el usuario Supervisor (Acceso total)
        User::updateOrCreate(
            ['email' => 'magali@refinansa.com'],
            [
                'name' => 'Magali Ramirez',
                'password' => Hash::make('password123'),
                'role' => 'supervisor',
                'estado' => 'Activo'
            ]
        );

        // 2. Creamos un usuario Asesor (Acceso restringido)
        User::updateOrCreate(
            ['email' => 'elvis@refinansa.com'],
            [
                'name' => 'Elvis Lopez',
                'password' => Hash::make('password123'),
                'role' => 'asesor',
                'estado' => 'Activo'
            ]
        );
                User::updateOrCreate(
            ['email' => 'admin@refinansa.com'],
            [
                'name' => 'Ruben Chambi',
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'estado' => 'Activo'
            ]
        );
                
                User::updateOrCreate(
            ['email' => 'cesar@refinansa.com'],
            [
                'name' => 'Cesar Rodriguez',
                'password' => Hash::make('password123'),
                'role' => 'supervisor',
                'estado' => 'Activo'
            ]
        );

        
    }
}