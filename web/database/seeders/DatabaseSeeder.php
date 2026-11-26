<?php

namespace Database\Seeders;

use App\Models\GeneralSettings;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = password_hash('123456', PASSWORD_DEFAULT);

        $seedUsers = [
            [
                'name'          => 'Administrador',
                'email'         => 'admin@arabuta.sc.gov.br',
                'whatsapp'      => null,
                'role'          => 'admin',
                'allowed_items' => [],
            ],
            [
                'name'          => 'Maria Silva (Contabilidade)',
                'email'         => 'contabilidade@arabuta.sc.gov.br',
                'whatsapp'      => '(49) 99901-1001',
                'role'          => 'user',
                'allowed_items' => [1, 2, 3, 4, 11, 13, 14, 15, 20, 21, 22],
            ],
            [
                'name'          => 'Carlos Souza (Engenharia)',
                'email'         => 'engenharia@arabuta.sc.gov.br',
                'whatsapp'      => '(49) 99902-2002',
                'role'          => 'user',
                'allowed_items' => [5, 6, 7, 8, 10, 16, 17, 19, 23, 24],
            ],
            [
                'name'          => 'Ana Paula (Controle Interno)',
                'email'         => 'controle@arabuta.sc.gov.br',
                'whatsapp'      => '(49) 99903-3003',
                'role'          => 'user',
                'allowed_items' => [9, 12, 18],
            ],
            [
                'name'          => 'Leandro (Operador)',
                'email'         => 'leandro@arabuta.sc.gov.br',
                'whatsapp'      => '(49) 98829-6525',
                'role'          => 'user',
                'allowed_items' => [1, 2, 3, 8, 13],
            ],
        ];

        foreach ($seedUsers as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'            => $u['name'],
                    'whatsapp'        => $u['whatsapp'],
                    'password_hashed' => $defaultPassword,
                    'role'            => $u['role'],
                ]
            );

            if ($u['role'] === 'user' && ! empty($u['allowed_items'])) {
                $existingPerms = UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id);
                if (empty($existingPerms)) {
                    UserAccountabilityPermission::syncUserPermissions((int) $user->id, $u['allowed_items']);
                }
            }
        }

        if (GeneralSettings::count() === 0) {
            GeneralSettings::create([
                'app_name'     => 'Sistema de Prestação de Contas',
                'company_name' => 'Município de Arabutã',
            ]);
        }
    }
}
