<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRejectionReasonToItemStatuses extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('rejection_reason', 'public_accountability_item_statuses')) {
            $this->forge->addColumn('public_accountability_item_statuses', [
                'rejection_reason' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'status',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('rejection_reason', 'public_accountability_item_statuses')) {
            $this->forge->dropColumn('public_accountability_item_statuses', 'rejection_reason');
        }
    }
}
