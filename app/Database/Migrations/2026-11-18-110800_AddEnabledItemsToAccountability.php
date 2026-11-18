<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEnabledItemsToAccountability extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('enabled_items', 'public_accountability')) {
            $this->forge->addColumn('public_accountability', [
                'enabled_items' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'deadline',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('enabled_items', 'public_accountability')) {
            $this->forge->dropColumn('public_accountability', 'enabled_items');
        }
    }
}
