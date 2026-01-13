<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Création de l'utilisateur de test (si tu veux éviter l'erreur, tu peux commenter ces lignes si l'user existe déjà)
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // --- AJOUTE CETTE LIGNE POUR LES FORMATIONS ---
        $this->call([
            TrainingSeeder::class,
        ]);
    }
}