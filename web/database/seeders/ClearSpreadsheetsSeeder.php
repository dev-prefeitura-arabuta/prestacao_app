<?php

namespace Database\Seeders;

use App\Models\AccountabilitySpreadsheetEntry;
use Illuminate\Database\Seeder;

class ClearSpreadsheetsSeeder extends Seeder
{
    public function run(): void
    {
        $deleted = AccountabilitySpreadsheetEntry::query()->delete();

        $this->command?->info("ClearSpreadsheetsSeeder concluído: {$deleted} lançamento(s) da planilha consolidada removido(s).");
    }
}
