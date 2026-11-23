<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class GeneralSettings extends Model
{
    protected $table = 'general_settings';

    protected $fillable = [
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
            $row = self::first();
            if ($row !== null) {
                $defaults = array_merge($defaults, $row->toArray());
            }
        } catch (Throwable) {
            // Fallback seguro
        }

        self::$cached = $defaults;
        return self::$cached;
    }

    public static function clearCache(): void
    {
        self::$cached = null;
    }
}
