<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\AccountabilitySpreadsheetEntry;
use Illuminate\Http\Request;
use Throwable;

class SpreadsheetController extends Controller
{
    public function index(Request $request)
    {
        $role             = session('user_role') ?? session('role') ?? 'user';
        $accountabilities = Accountability::orderBy('id', 'desc')->get()->toArray();
        $selectedAccId    = (int) ($request->query('accountability_id') ?: ($accountabilities[0]['id'] ?? 0));

        $entries = [];
        if ($selectedAccId > 0) {
            try {
                $entries = AccountabilitySpreadsheetEntry::where('accountability_id', $selectedAccId)
                    ->orderBy('id', 'asc')
                    ->get()
                    ->toArray();
            } catch (Throwable) {
                $entries = [];
            }
        }

        return view('spreadsheet.index', [
            'userName'         => session('user_name'),
            'username'         => session('username'),
            'userEmail'        => session('user_email'),
            'userRole'         => $role,
            'accountabilities' => $accountabilities,
            'selectedAccId'    => $selectedAccId,
            'entries'          => $entries,
            'shortItems'       => Accountability::getShortItems(),
        ]);
    }

    public function save(Request $request)
    {
        $accId = (int) $request->input('accountability_id');
        if ($accId <= 0) {
            return redirect('/planilha')->with('error', 'Selecione uma prestação de contas válida.');
        }

        $rows   = (array) $request->input('rows', []);
        $userId = (int) session('user_id');

        try {
            AccountabilitySpreadsheetEntry::where('accountability_id', $accId)->delete();

            $savedCount = 0;
            foreach ($rows as $row) {
                $docNum   = trim((string) ($row['document_number'] ?? ''));
                $supplier = trim((string) ($row['supplier_name'] ?? ''));
                $desc     = trim((string) ($row['description'] ?? ''));
                $rawAmt   = trim((string) ($row['amount'] ?? ''));
                $date     = trim((string) ($row['entry_date'] ?? ''));
                $cnpj     = trim((string) ($row['cnpj_cpf'] ?? ''));
                $notes    = trim((string) ($row['notes'] ?? ''));
                $itemNum  = ! empty($row['item_number']) ? (int) $row['item_number'] : null;
                $type     = in_array(($row['entry_type'] ?? 'despesa'), ['receita', 'despesa'], true)
                    ? (string) $row['entry_type']
                    : 'despesa';

                // Ignora linhas totalmente em branco
                if ($docNum === '' && $supplier === '' && $desc === '' && $rawAmt === '' && $date === '') {
                    continue;
                }

                // Normaliza formato monetário brasileiro (ex: 1.250,50 -> 1250.50)
                $cleanAmt = str_replace(['R$', ' '], '', $rawAmt);
                if (str_contains($cleanAmt, ',') && str_contains($cleanAmt, '.')) {
                    $cleanAmt = str_replace('.', '', $cleanAmt);
                    $cleanAmt = str_replace(',', '.', $cleanAmt);
                } elseif (str_contains($cleanAmt, ',')) {
                    $cleanAmt = str_replace(',', '.', $cleanAmt);
                }
                $amount = is_numeric($cleanAmt) ? round((float) $cleanAmt, 2) : 0.00;

                AccountabilitySpreadsheetEntry::create([
                    'accountability_id' => $accId,
                    'item_number'       => $itemNum,
                    'entry_date'        => $date !== '' ? $date : null,
                    'document_number'   => $docNum !== '' ? $docNum : null,
                    'supplier_name'     => $supplier !== '' ? $supplier : null,
                    'cnpj_cpf'          => $cnpj !== '' ? $cnpj : null,
                    'description'       => $desc !== '' ? $desc : null,
                    'entry_type'        => $type,
                    'amount'            => $amount,
                    'notes'             => $notes !== '' ? $notes : null,
                    'created_by'        => $userId,
                ]);
                $savedCount++;
            }

            return redirect('/planilha?accountability_id=' . $accId)
                ->with('success', "Planilha salva com sucesso! ({$savedCount} lançamento(s) gravado(s))");
        } catch (Throwable $e) {
            return redirect('/planilha?accountability_id=' . $accId)
                ->with('error', 'Erro ao salvar planilha: ' . $e->getMessage());
        }
    }

    public function blankPage(int $pageNumber)
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        return view('pages.blank', [
            'userName'   => session('user_name'),
            'username'   => session('username'),
            'userEmail'  => session('user_email'),
            'userRole'   => $role,
            'pageNumber' => $pageNumber,
            'pageTitle'  => 'Nova Página ' . $pageNumber,
        ]);
    }
}
