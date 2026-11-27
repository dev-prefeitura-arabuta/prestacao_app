<?php

namespace Database\Seeders;

use App\Models\Accountability;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Database\Seeder;

class AccountabilitySeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = password_hash('123456', PASSWORD_DEFAULT);

        // 1. Garante usuários necessários e suas permissões (cobrindo todos os 24 itens)
        $seedUsers = [
            [
                'name'          => 'Administrador',
                'email'         => 'admin@arabuta.sc.gov.br',
                'whatsapp'      => '(49) 99900-0000',
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

        $adminUser = null;
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

            if ($u['role'] === 'admin' && $adminUser === null) {
                $adminUser = $user;
            }

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

        $adminId = (int) ($adminUser?->id ?? 1);
        $all24   = range(1, 24);

        // 2. Prestações de Contas (abertas + finalizadas para Histórico Anual)
        $seedAccountabilities = [
            [
                'title'         => 'Pavimentação Asfáltica - Av. Central (Convênio 2026/01)',
                'description'   => 'Prestação de contas completa da obra de pavimentação asfáltica e drenagem pluvial da Avenida Central.',
                'status'        => 'aberta',
                'is_paused'     => false,
                'deadline'      => now()->addDays(30)->format('Y-m-d'),
                'enabled_items' => json_encode($all24),
                'created_at'    => now()->format('Y-m-d H:i:s'),
                'entries'       => [],
            ],
            [
                'title'         => 'Reforma da Escola Municipal Arabutã - 2026',
                'description'   => 'Adequação de salas de aula, cobertura e acessibilidade com recursos estaduais.',
                'status'        => 'aberta',
                'is_paused'     => false,
                'deadline'      => now()->addDays(15)->format('Y-m-d'),
                'enabled_items' => json_encode([1, 2, 3, 5, 6, 7, 8, 10, 13, 18, 22, 23]),
                'created_at'    => now()->format('Y-m-d H:i:s'),
                'entries'       => [],
            ],
            [
                'title'         => 'Aquisição de Equipamentos de Saúde - Emenda 2026',
                'description'   => 'Compra de equipamentos hospitalares e mobiliário para a Unidade Básica de Saúde (prazo vencido para testes de alerta).',
                'status'        => 'aberta',
                'is_paused'     => false,
                'deadline'      => now()->subDays(5)->format('Y-m-d'),
                'enabled_items' => json_encode([1, 2, 3, 9, 13, 18, 22]),
                'created_at'    => now()->subDays(25)->format('Y-m-d H:i:s'),
                'entries'       => [],
            ],
        ];

        $createdCount = 0;
        foreach ($seedAccountabilities as $item) {
            $entries = $item['entries'];
            unset($item['entries']);

            $acc = Accountability::where('title', $item['title'])->first();
            if (! $acc) {
                $acc = Accountability::create($item);
                $createdCount++;

                AccountabilityHistory::create([
                    'accountability_id' => (int) $acc->id,
                    'user_id'           => $adminId,
                    'status'            => 'Prestação de contas criada via Seeder',
                    'created_at'        => $item['created_at'],
                    'updated_at'        => $item['created_at'],
                ]);

                foreach ($entries as $e) {
                    AccountabilitySpreadsheetEntry::create([
                        'accountability_id' => (int) $acc->id,
                        'item_number'       => $e['item_number'],
                        'entry_date'        => $e['entry_date'],
                        'document_number'   => $e['document_number'],
                        'supplier_name'     => $e['supplier_name'],
                        'cnpj_cpf'          => $e['cnpj_cpf'],
                        'description'       => $e['description'],
                        'entry_type'        => $e['entry_type'],
                        'amount'            => $e['amount'],
                        'created_by'        => $adminId,
                    ]);
                }
            }
        }

        $this->command?->info('AccountabilitySeeder concluído: 5 usuários verificados/criados e ' . $createdCount . ' nova(s) prestação(ões) de contas inserida(s).');
    }
}
