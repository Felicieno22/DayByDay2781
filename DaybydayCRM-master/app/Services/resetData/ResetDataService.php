<?php

namespace App\Services\resetData;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Exception;

class ResetDataService
{
    public function resetDatabase()
    {
        try {
            // 1. Sauvegarder les utilisateurs admin
            $admins = User::where('admin', true)->get();
            
            // 2. Réinitialiser la base de données
            Artisan::call('migrate:fresh');
            
            // 3. Exécuter les seeders pour les données initiales
            Artisan::call('db:seed');
            
            // 4. Restaurer les utilisateurs admin
            foreach ($admins as $admin) {
                User::create([
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'password' => $admin->password,
                    'admin' => true,
                    'remember_token' => $admin->remember_token
                ]);
            }

            return [
                'success' => true,
                'message' => 'Base de données réinitialisée avec succès',
                'admins_preserved' => $admins->count()
            ];

        } catch (Exception $e) {
            report($e);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation: ' . $e->getMessage()
            ];
        }
    }
}