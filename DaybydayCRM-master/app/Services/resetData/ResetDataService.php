<?php

namespace App\Services\ResetData;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class ResetDataService
{
    /**
     * Reset all data except admin users
     *
     * @return void
     */
    public function reset()
    {
        DB::beginTransaction();

        try {
            // 1. Sauvegarder les admins et leurs relations
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', [Role::ADMIN_ROLE, Role::OWNER_ROLE]);
            })->with(['roles', 'department'])->get();

            // 2. Exécuter migrate:fresh
            Artisan::call('migrate:fresh');

            // 3. Exécuter les seeders de base
            Artisan::call('db:seed', [
                '--class' => 'RolesTablesSeeder'
            ]);
            
            Artisan::call('db:seed', [
                '--class' => 'PermissionsTableSeeder'
            ]);

            // Exécuter le seeder des paramètres pour avoir les valeurs par défaut
            Artisan::call('db:seed', [
                '--class' => 'SettingsTableSeeder'
            ]);

            Artisan::call('db:seed', [
                '--class' => 'DepartmentsTableSeeder'
            ]);

            // 4. Restaurer les utilisateurs admin avec leurs rôles et départements
            foreach ($adminUsers as $admin) {
                // Recréer l'utilisateur
                $newAdmin = $admin->replicate();
                $newAdmin->save();

                // Réattribuer les rôles
                foreach ($admin->roles as $role) {
                    $newAdmin->attachRole($role);
                }

                // Réattribuer le département si existant
                if ($admin->department && $admin->department->first()) {
                    $newAdmin->department()->attach($admin->department->first()->id);
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 