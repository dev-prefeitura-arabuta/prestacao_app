<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPdfCoverArtToGeneralSettings extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('pdf_cover_art', 'general_settings')) {
            $this->forge->addColumn('general_settings', [
                'pdf_cover_art' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'pdf_cover_accent_color',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('pdf_cover_art', 'general_settings')) {
            $this->forge->dropColumn('general_settings', 'pdf_cover_art');
        }
    }
}
