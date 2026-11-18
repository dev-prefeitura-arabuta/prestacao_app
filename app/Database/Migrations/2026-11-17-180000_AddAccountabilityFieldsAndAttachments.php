<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccountabilityFieldsAndAttachments extends Migration
{
    public function up()
    {
        // 1. Garante a coluna `deadline` em `public_accountability`
        if (! $this->db->fieldExists('deadline', 'public_accountability')) {
            $this->forge->addColumn('public_accountability', [
                'deadline' => [
                    'type'  => 'DATE',
                    'null'  => true,
                    'after' => 'status',
                ],
            ]);
        }

        // Remove restrição unique de description se existir e amplia para TEXT
        try {
            $this->db->query('ALTER TABLE `public_accountability` DROP INDEX `description`');
        } catch (\Throwable) {
            // Índice não existe, ignora
        }

        $this->forge->modifyColumn('public_accountability', [
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        // 2. Tabela de permissões de campos (1..24) por usuário
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'item_number' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'item_number']);
        $this->forge->createTable('user_accountability_permissions', true);

        // 3. Tabela de anexos múltiplos por item (1..24) de cada prestação de contas
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'accountability_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'item_number' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'file_ext' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'file_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['accountability_id', 'item_number']);
        $this->forge->createTable('public_accountability_attachments', true);

        // 4. Amplia coluna status em public_accountability_history para comportar descrição de auditoria
        $this->forge->modifyColumn('public_accountability_history', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('public_accountability_attachments', true);
        $this->forge->dropTable('user_accountability_permissions', true);
    }
}
