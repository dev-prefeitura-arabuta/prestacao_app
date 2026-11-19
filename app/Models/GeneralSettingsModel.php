<?php

namespace App\Models;

use CodeIgniter\Model;
use Throwable;

class GeneralSettingsModel extends Model
{
    protected $table            = 'general_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'app_name',
        'company_name',
        'cnpj',
        'app_logo',
        'pdf_cover_enabled',
        'pdf_cover_index_enabled',
        'pdf_cover_show_logo',
        'pdf_cover_show_border',
        'pdf_cover_label',
        'pdf_cover_subtitle',
        'pdf_cover_footer',
        'pdf_cover_primary_color',
        'pdf_cover_accent_color',
        'pdf_cover_art',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    private static ?array $cached = null;

    public static function getSettings(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $defaults = [
            'id'                      => null,
            'app_name'                => 'Sistema Patrimonial',
            'company_name'            => 'Município de Arabutã',
            'cnpj'                    => '',
            'app_logo'                => null,
            'pdf_cover_enabled'       => 1,
            'pdf_cover_index_enabled' => 1,
            'pdf_cover_show_logo'     => 1,
            'pdf_cover_show_border'   => 1,
            'pdf_cover_label'         => 'PRESTAÇÃO DE CONTAS',
            'pdf_cover_subtitle'      => 'DOSSIÊ DOCUMENTAL CONSOLIDADO',
            'pdf_cover_footer'        => '',
            'pdf_cover_primary_color' => '#0f172a',
            'pdf_cover_accent_color'  => '#dd4814',
            'pdf_cover_art'           => null,
        ];

        try {
            $model = new self();
            $row   = $model->first();
            if (is_array($row)) {
                $defaults = array_merge($defaults, $row);
            }
        } catch (Throwable) {
            // Fallback seguro caso a tabela ainda não exista
        }

        self::$cached = $defaults;
        return self::$cached;
    }

    public static function clearCache(): void
    {
        self::$cached = null;
    }
}
