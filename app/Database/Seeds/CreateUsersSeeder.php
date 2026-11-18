<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CreateUsersSeeder extends Seeder
{
    public function run()
    {
        $now  = date('Y-m-d H:i:s');
        $hash = password_hash('123456', PASSWORD_DEFAULT);

        $users = [
            [
                'name'            => 'Administrador Geral',
                'email'           => 'admin@admin.com',
                'password_hashed' => $hash,
                'role'            => 'admin',
                'allowed_item'    => null,
            ],
            [
                'name'            => 'Carlos Eduardo (Controladoria)',
                'email'           => 'controladoria@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'admin',
                'allowed_item'    => null,
            ],
            [
                'name'            => 'Usuário Fiscal (Balancete)',
                'email'           => 'user@user.com',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 1, // Balancete
            ],
            [
                'name'            => 'Fernanda Souza (Contabilidade)',
                'email'           => 'contabilidade@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 2, // Empenho
            ],
            [
                'name'            => 'Marcos Oliveira (Sec. Educação)',
                'email'           => 'educacao@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 3, // Notas
            ],
            [
                'name'            => 'Juliana Lima (Sec. Saúde)',
                'email'           => 'saude@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 4, // Encargos
            ],
            [
                'name'            => 'Roberto Santos (Sec. Obras)',
                'email'           => 'obras@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 5, // Ordem
            ],
            [
                'name'            => 'Patrícia Costa (Sec. Agricultura)',
                'email'           => 'agricultura@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 6, // Medição
            ],
            [
                'name'            => 'Lucas Almeida (Engenharia)',
                'email'           => 'licitacoes@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 7, // Laudo
            ],
            [
                'name'            => 'Camila Rocha (Assistência Social)',
                'email'           => 'social@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 8, // Fotos
            ],
            [
                'name'            => 'Rafael Mendes (Administração)',
                'email'           => 'administracao@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 9, // Bens
            ],
            [
                'name'            => 'Andréia Martins (Fiscalização ART)',
                'email'           => 'art@arabuta.sc.gov.br',
                'password_hashed' => $hash,
                'role'            => 'user',
                'allowed_item'    => 10, // ART
            ],
        ];

        $hasPermissionsTable = $this->db->tableExists('user_accountability_permissions');

        foreach ($users as $user) {
            $allowedItem = $user['allowed_item'];
            unset($user['allowed_item']);

            $existing = $this->db->table('users')
                ->where('email', $user['email'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $user['created_at'] = $now;
                $user['updated_at'] = $now;
                $this->db->table('users')->insert($user);
                $userId = (int) $this->db->insertID();
            } else {
                $userId = (int) $existing['id'];
                $this->db->table('users')
                    ->where('id', $userId)
                    ->update([
                        'name'       => $user['name'],
                        'role'       => $user['role'],
                        'updated_at' => $now,
                    ]);
            }

            if ($hasPermissionsTable && $user['role'] === 'user' && $allowedItem !== null) {
                $this->db->table('user_accountability_permissions')
                    ->where('user_id', $userId)
                    ->delete();

                $this->db->table('user_accountability_permissions')->insert([
                    'user_id'     => $userId,
                    'item_number' => (int) $allowedItem,
                    'created_at'  => $now,
                ]);
            }
        }
    }
}
