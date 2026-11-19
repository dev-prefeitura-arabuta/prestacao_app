<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneralSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'app_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => 'Sistema Patrimonial',
            ],
            'company_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'default'    => 'Município de Arabutã',
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'app_logo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('general_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('general_settings', true);
    }
}
