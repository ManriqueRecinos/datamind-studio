<?php

namespace Database\Seeders;

use App\Models\Ecosystem;
use App\Models\EcosystemModule;
use App\Models\EcosystemInterface;
use Illuminate\Database\Seeder;

class RolesModuleEcosystemSeeder extends Seeder
{
    public function run(): void
    {
        $eco = Ecosystem::where('key', 'settings')->first();
        if (!$eco) return;

        $mod = EcosystemModule::firstOrCreate(
            ['ecosystem_id' => $eco->id, 'key' => 'settings_roles'],
            [
                'name' => 'Control de Acceso (RBAC)',
                'icon' => 'fa-shield-halved',
                'order_num' => 4,
                'is_active' => true,
            ]
        );

        EcosystemInterface::firstOrCreate(
            ['module_id' => $mod->id, 'name' => 'Roles & Permisos'],
            [
                'icon' => 'fa-user-lock',
                'route_or_action' => 'settings.roles.index',
                'action_type' => 'route',
                'badge_label' => 'Admin',
                'order_num' => 1,
                'is_active' => true,
            ]
        );
    }
}
