<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPdfCoverSettingsToGeneralSettings extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('pdf_cover_enabled', 'general_settings')) {
            $fields['pdf_cover_enabled'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'app_logo',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_index_enabled', 'general_settings')) {
            $fields['pdf_cover_index_enabled'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'pdf_cover_enabled',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_show_logo', 'general_settings')) {
            $fields['pdf_cover_show_logo'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'pdf_cover_index_enabled',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_show_border', 'general_settings')) {
            $fields['pdf_cover_show_border'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'pdf_cover_show_logo',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_label', 'general_settings')) {
            $fields['pdf_cover_label'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => 'PRESTAÇÃO DE CONTAS',
                'after'      => 'pdf_cover_show_border',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_subtitle', 'general_settings')) {
            $fields['pdf_cover_subtitle'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => 'DOSSIÊ DOCUMENTAL CONSOLIDADO',
                'after'      => 'pdf_cover_label',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_footer', 'general_settings')) {
            $fields['pdf_cover_footer'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'pdf_cover_subtitle',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_primary_color', 'general_settings')) {
            $fields['pdf_cover_primary_color'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '#0f172a',
                'after'      => 'pdf_cover_footer',
            ];
        }
        if (! $this->db->fieldExists('pdf_cover_accent_color', 'general_settings')) {
            $fields['pdf_cover_accent_color'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '#dd4814',
                'after'      => 'pdf_cover_primary_color',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('general_settings', $fields);
        }
    }

    public function down()
    {
        $cols = [
            'pdf_cover_enabled',
            'pdf_cover_index_enabled',
            'pdf_cover_show_logo',
            'pdf_cover_show_border',
            'pdf_cover_label',
            'pdf_cover_subtitle',
            'pdf_cover_footer',
            'pdf_cover_primary_color',
            'pdf_cover_accent_color',
        ];
        foreach ($cols as $col) {
            if ($this->db->fieldExists($col, 'general_settings')) {
                $this->forge->dropColumn('general_settings', $col);
            }
        }
    }
}
