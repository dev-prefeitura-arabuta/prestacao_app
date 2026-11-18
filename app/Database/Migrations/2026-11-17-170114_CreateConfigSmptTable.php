<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConfigSmptTable extends Migration
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
            'smtp_host' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'smtp_port' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => '587',
            ],
            'smtp_encryption' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'STARTTLS',
            ],
            'smtp_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'smtp_password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'email_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'email_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'reply_to_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'smtp_auth' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'smtp_timeout' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 30,
            ],
            'smtp_verify_ssl' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'status' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
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
        $this->forge->createTable('config_smtp', true);
    }

    public function down()
    {
        $this->forge->dropTable('config_smtp', true);
    }
}
