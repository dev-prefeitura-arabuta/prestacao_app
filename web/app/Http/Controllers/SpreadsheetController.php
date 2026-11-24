<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Services\SpreadsheetAiExtractorService;
use Illuminate\Http\Request;
use Throwable;

class SpreadsheetController extends Controller
{
    public function index(Request $request)
    {
        $role = session('user_role') ?? session('role') ?? 'user';
        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        $attCounts = [];
        try {
            foreach (AccountabilityAttachment::select('accountability_id')->get() as $att) {
                $aid             = (int) $att->accountability_id;
                $attCounts[$aid] = ($attCounts[$aid] ?? 0) + 1;
            }
        } catch (Throwable) {
        }

        $rejectedByAcc = [];
        try {
            foreach (AccountabilityItemStatus::where('status', 'rejeitado')->get() as $st) {
                $rejectedByAcc[(int) $st->accountability_id][] = (int) $st->item_number;
            }
        } catch (Throwable) {
        }

        $sheetSummaryByAcc = [];
        try {
            foreach (AccountabilitySpreadsheetEntry::all() as $e) {
                $aid  = (int) $e->accountability_id;
                $iNum = $e->item_number !== null ? (int) $e->item_number : null;
                if ($iNum !== null && in_array($iNum, $rejectedByAcc[$aid] ?? [], true)) {
                    continue;
                }
                if (! isset($sheetSummaryByAcc[$aid])) {
                    $sheetSummaryByAcc[$aid] = [
                        'entries_count' => 0,
                        'receitas'      => 0.0,
                        'aportes'       => 0.0,
                        'despesas'      => 0.0,
                    ];
                }
                $sheetSummaryByAcc[$aid]['entries_count']++;
                $amt  = (float) ($e->amount ?? 0);
                $type = (string) ($e->entry_type ?? 'despesa');
                if ($type === 'receita') {
                    $sheetSummaryByAcc[$aid]['receitas'] += $amt;
                } elseif ($type === 'aporte') {
                    $sheetSummaryByAcc[$aid]['aportes'] += $amt;
                } else {
                    $sheetSummaryByAcc[$aid]['despesas'] += $amt;
                }
            }
        } catch (Throwable) {
        }

        // Lista resumida: somente prestações que já possuem pelo menos 1 anexo
        $allAccs          = Accountability::orderBy('id', 'desc')->get()->toArray();
        $accountabilities = [];
        foreach ($allAccs as $acc) {
            $aid = (int) $acc['id'];
            if (($attCounts[$aid] ?? 0) <= 0) {
                continue;
            }
            $sum                       = $sheetSummaryByAcc[$aid] ?? ['entries_count' => 0, 'receitas' => 0.0, 'aportes' => 0.0, 'despesas' => 0.0];
            $acc['attachments_count']  = $attCounts[$aid];
            $acc['entries_count']      = $sum['entries_count'];
            $acc['receitas']           = $sum['receitas'];
            $acc['aportes']            = $sum['aportes'];
            $acc['despesas']           = $sum['despesas'];
            $acc['saldo']              = ($sum['receitas'] + $sum['aportes']) - $sum['despesas'];
            $accountabilities[]        = $acc;
        }

        // Só abre a tabela detalhada quando o usuário clicar em "Abrir Detalhado" (?accountability_id=X)
        $requestedAccId = (int) $request->query('accountability_id', 0);
        $selectedAccId  = $requestedAccId > 0 ? $requestedAccId : 0;
        $selectedAcc    = null;

        if ($selectedAccId > 0) {
            foreach ($accountabilities as $acc) {
                if ((int) $acc['id'] === $selectedAccId) {
                    $selectedAcc = $acc;
                    break;
                }
            }
            // Permite abrir detalhado mesmo se vier de um link direto (ex: Histórico Anual)
            if ($selectedAcc === null) {
                $direct = Accountability::find($selectedAccId);
                if ($direct) {
                    $selectedAcc = $direct->toArray();
                } else {
                    $selectedAccId = 0;
                }
            }
        }

        $entries = [];
        if ($selectedAccId > 0) {
            try {
                $rejectedItems = $rejectedByAcc[$selectedAccId] ?? [];

                $query = AccountabilitySpreadsheetEntry::where('accountability_id', $selectedAccId)
                    ->orderBy('id', 'asc');

                if (! empty($rejectedItems)) {
                    $query->where(static function ($q) use ($rejectedItems) {
                        $q->whereNull('item_number')
                          ->orWhereNotIn('item_number', $rejectedItems);
                    });
                }

                $entries = $query->get()->toArray();
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
            'selectedAcc'      => $selectedAcc,
            'entries'          => $entries,
            'shortItems'       => Accountability::getShortItems(),
        ]);
    }

    public function save(Request $request)
    {
        if ((session('user_role') ?? session('role') ?? 'user') !== 'admin') {
            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

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
                $type     = in_array(($row['entry_type'] ?? 'despesa'), ['receita', 'aporte', 'despesa'], true)
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

                $attId = ! empty($row['attachment_id']) ? (int) $row['attachment_id'] : null;

                AccountabilitySpreadsheetEntry::create([
                    'accountability_id' => $accId,
                    'item_number'       => $itemNum,
                    'attachment_id'     => $attId,
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

    public function extractFromAttachments(Request $request)
    {
        if ((session('user_role') ?? session('role') ?? 'user') !== 'admin') {
            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        $accId = (int) $request->input('accountability_id');
        if ($accId <= 0) {
            return redirect('/planilha')->with('error', 'Selecione uma prestação de contas válida.');
        }

        $acc = Accountability::find($accId);
        if (! $acc) {
            return redirect('/planilha')->with('error', 'Prestação de contas não encontrada.');
        }

        $rejectedItems = AccountabilityItemStatus::where('accountability_id', $accId)
            ->where('status', 'rejeitado')
            ->pluck('item_number')
            ->map(static fn ($v) => (int) $v)
            ->all();

        $attQuery = AccountabilityAttachment::where('accountability_id', $accId)
            ->orderBy('item_number', 'asc')
            ->orderBy('id', 'asc');

        if (! empty($rejectedItems)) {
            $attQuery->whereNotIn('item_number', $rejectedItems);
        }

        $attachments = $attQuery->get()->toArray();

        if (empty($attachments)) {
            return redirect('/planilha?accountability_id=' . $accId)
                ->with('error', 'Nenhum documento válido (não rejeitado) anexado nesta prestação para extrair dados.');
        }

        try {
            $extractor = new SpreadsheetAiExtractorService();
            $res       = $extractor->extractEntries($acc->toArray(), $attachments);

            if (empty($res['success']) || empty($res['entries'])) {
                return redirect('/planilha?accountability_id=' . $accId)
                    ->with('error', $res['error'] ?? 'Não foi possível extrair dados dos documentos anexados.');
            }

            $userId   = (int) session('user_id');
            $inserted = 0;

            foreach ($res['entries'] as $entry) {
                AccountabilitySpreadsheetEntry::create([
                    'accountability_id' => $accId,
                    'item_number'       => $entry['item_number'],
                    'attachment_id'     => $entry['attachment_id'] ?? null,
                    'entry_date'        => $entry['entry_date'],
                    'document_number'   => $entry['document_number'],
                    'supplier_name'     => $entry['supplier_name'],
                    'cnpj_cpf'          => $entry['cnpj_cpf'],
                    'description'       => $entry['description'],
                    'entry_type'        => $entry['entry_type'],
                    'amount'            => $entry['amount'],
                    'notes'             => $entry['notes'],
                    'created_by'        => $userId,
                ]);
                $inserted++;
            }

            return redirect('/planilha?accountability_id=' . $accId)
                ->with('success', "✨ {$inserted} lançamento(s) extraído(s) dos documentos anexados via {$res['engine']}! Revise os valores e clique em Salvar Planilha se fizer ajustes.");
        } catch (Throwable $e) {
            return redirect('/planilha?accountability_id=' . $accId)
                ->with('error', 'Erro ao extrair dados com IA: ' . $e->getMessage());
        }
    }
}
