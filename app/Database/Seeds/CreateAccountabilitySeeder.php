<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CreateAccountabilitySeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $accountabilities = [
            [
                'title'       => 'Pavimentação Asfáltica - Rua Principal (Convênio 2026/001)',
                'description' => 'Execução de obras de pavimentação asfáltica, drenagem pluvial e sinalização viária no perímetro urbano de Arabutã.',
                'status'      => 'aberta',
                'deadline'    => date('Y-m-d', strtotime('+30 days')),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Reforma e Ampliação da Unidade Básica de Saúde (Convênio 2026/002)',
                'description' => 'Obras de modernização, acessibilidade e aquisição de equipamentos para a Unidade Básica de Saúde Central.',
                'status'      => 'aberta',
                'deadline'    => date('Y-m-d', strtotime('+45 days')),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Aquisição de Patrulha Mecanizada Agrícola (Termo de Compromisso 2026/003)',
                'description' => 'Aquisição de maquinário e implementos agrícolas para atendimento aos produtores rurais do Município de Arabutã.',
                'status'      => 'em_analise',
                'deadline'    => date('Y-m-d', strtotime('+60 days')),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($accountabilities as $item) {
            $exists = $this->db->table('public_accountability')
                ->where('title', $item['title'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('public_accountability')->insert($item);
                $accId = (int) $this->db->insertID();

                $this->db->table('public_accountability_history')->insert([
                    'accountability_id' => $accId,
                    'user_id'           => 1,
                    'status'            => 'Prestação de contas cadastrada para lançamento',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }
    }
}
