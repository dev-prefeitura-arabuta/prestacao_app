<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilitySpreadsheetEntry extends Model
{
    protected $table = 'accountability_spreadsheet_entries';

    protected $fillable = [
        'accountability_id',
        'item_number',
        'entry_date',
        'document_number',
        'supplier_name',
        'cnpj_cpf',
        'description',
        'entry_type',
        'amount',
        'notes',
        'created_by',
    ];
}
