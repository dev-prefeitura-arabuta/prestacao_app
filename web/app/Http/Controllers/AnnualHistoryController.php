<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use Illuminate\Http\Request;
use Throwable;

class AnnualHistoryController extends Controller
{
    /**
     * Resolve o ano de registro de uma prestação de contas (created_at -> deadline -> ano atual).
     *
     * @param array<string, mixed> $acc
     */
    public static function resolveRecordYear(array $acc): int
    {
        $source = ! empty($acc['created_at'])
            ? (string) $acc['created_at']
            : (string) ($acc['deadline'] ?? '');

        $ts = $source !== '' ? strtotime($source) : false;
        return $ts !== false ? (int) date('Y', $ts) : (int) date('Y');
    }

    /**
     * Retorna os anos existentes com registros FINALIZADOS (ordenados do mais recente ao mais antigo).
     *
     * @return array<int, int> Mapa [ano => quantidade_de_prestacoes]
     */
    public static function getAvailableYearsWithCounts(): array
    {
        $counts = [];
        try {
            $rows        = Accountability::select('id', 'status', 'enabled_items', 'created_at', 'deadline')->get()->toArray();
            $statusByAcc = [];
            foreach (AccountabilityItemStatus::all() as $st) {
                $statusByAcc[(int) $st->accountability_id][(int) $st->item_number] = (string) $st->status;
            }

            foreach ($rows as $row) {
                $aid = (int) $row['id'];
                if (! Accountability::isFinalized($row, $statusByAcc[$aid] ?? [])) {
                    continue;
                }
                $yr          = self::resolveRecordYear($row);
                $counts[$yr] = ($counts[$yr] ?? 0) + 1;
            }
        } catch (Throwable) {
            return [];
        }

        krsort($counts);
        return $counts;
    }

    /**
     * Página principal (/historico-anual): lista os cards/subpáginas de cada ano que possui registros.
     */
    public function index(Request $request)
    {
        $yearParam = trim((string) $request->query('year', ''));
        if ($yearParam !== '' && ctype_digit($yearParam)) {
            return redirect('/historico-anual/' . (int) $yearParam);
        }

        return $this->renderAnnualView(null);
    }

    /**
     * Subpágina de um ano específico (/historico-anual/{year}): exibe todos os registros daquele ano.
     */
    public function showYear(int $year)
    {
        return $this->renderAnnualView($year);
    }

    private function renderAnnualView(?int $selectedYear)
    {
        $role = session('user_role') ?? session('role') ?? 'user';
        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        $allAccs = [];
        try {
            $allAccs = Accountability::orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->get()
                ->toArray();
        } catch (Throwable) {
            $allAccs = [];
        }

        $attCounts   = [];
        $statusByAcc = [];
        $sheetTotals = [];

        try {
            foreach (AccountabilityAttachment::select('accountability_id')->get() as $att) {
                $aid             = (int) $att->accountability_id;
                $attCounts[$aid] = ($attCounts[$aid] ?? 0) + 1;
            }
        } catch (Throwable) {
        }

        try {
            foreach (AccountabilityItemStatus::all() as $st) {
                $aid                                      = (int) $st->accountability_id;
                $statusByAcc[$aid][(int) $st->item_number] = (string) $st->status;
            }
        } catch (Throwable) {
        }

        try {
            foreach (AccountabilitySpreadsheetEntry::all() as $entry) {
                $aid  = (int) $entry->accountability_id;
                $amt  = (float) ($entry->amount ?? 0);
                $type = (string) ($entry->entry_type ?? 'despesa');
                if (! isset($sheetTotals[$aid])) {
                    $sheetTotals[$aid] = ['receitas' => 0.0, 'despesas' => 0.0];
                }
                if (in_array($type, ['receita', 'aporte'], true)) {
                    $sheetTotals[$aid]['receitas'] += $amt;
                } else {
                    $sheetTotals[$aid]['despesas'] += $amt;
                }
            }
        } catch (Throwable) {
        }

        $todayTs     = strtotime(date('Y-m-d'));
        $yearCards   = [];
        $enrichedAll = [];

        foreach ($allAccs as $acc) {
            $aid          = (int) $acc['id'];
            $itemStatuses = $statusByAcc[$aid] ?? [];

            // Histórico Anual exibe somente prestações finalizadas/concluídas
            if (! Accountability::isFinalized($acc, $itemStatuses)) {
                continue;
            }

            $yr           = self::resolveRecordYear($acc);
            $enabledItems = Accountability::getEnabledItemsForAccountability($acc);
            $totalItems   = max(1, count($enabledItems));

            $confirmed = 0;
            $sent      = 0;
            $rejected  = 0;
            foreach (array_keys($enabledItems) as $num) {
                $st = $itemStatuses[(int) $num] ?? 'pendente';
                if ($st === 'confirmado') {
                    $confirmed++;
                } elseif ($st === 'enviado') {
                    $sent++;
                } elseif ($st === 'rejeitado') {
                    $rejected++;
                }
            }

            $progress   = (int) round((($confirmed + $sent) / $totalItems) * 100);
            if ($progress < 100 && Accountability::isFinalized($acc, $itemStatuses)) {
                $progress = 100;
            }
            $filesCount = $attCounts[$aid] ?? 0;
            $receitas   = (float) ($sheetTotals[$aid]['receitas'] ?? 0.0);
            $despesas   = (float) ($sheetTotals[$aid]['despesas'] ?? 0.0);
            $isOverdue  = ! empty($acc['deadline']) && strtotime((string) $acc['deadline']) < $todayTs && $confirmed < $totalItems;

            $row = [
                'id'            => $aid,
                'year'          => $yr,
                'registered_at' => ! empty($acc['created_at']) ? date('d/m/Y', strtotime((string) $acc['created_at'])) : '—',
                'title'         => (string) ($acc['title'] ?? "Prestação #{$aid}"),
                'description'   => (string) ($acc['description'] ?? ''),
                'deadline'      => ! empty($acc['deadline']) ? date('d/m/Y', strtotime((string) $acc['deadline'])) : 'Não definido',
                'is_paused'     => ! empty($acc['is_paused']),
                'is_overdue'    => $isOverdue,
                'total_items'   => $totalItems,
                'confirmed'     => $confirmed,
                'sent'          => $sent,
                'rejected'      => $rejected,
                'progress'      => $progress,
                'files_count'   => $filesCount,
                'receitas'      => $receitas,
                'despesas'      => $despesas,
            ];

            $enrichedAll[] = $row;

            if (! isset($yearCards[$yr])) {
                $yearCards[$yr] = [
                    'year'          => $yr,
                    'count'         => 0,
                    'completed'     => 0,
                    'files_count'   => 0,
                    'receitas'      => 0.0,
                    'despesas'      => 0.0,
                    'progress_sum'  => 0,
                    'avg_progress'  => 0,
                ];
            }

            $yearCards[$yr]['count']++;
            $yearCards[$yr]['files_count']  += $filesCount;
            $yearCards[$yr]['receitas']     += $receitas;
            $yearCards[$yr]['despesas']     += $despesas;
            $yearCards[$yr]['progress_sum'] += $progress;
            if ($confirmed === $totalItems) {
                $yearCards[$yr]['completed']++;
            }
        }

        krsort($yearCards);
        foreach ($yearCards as &$yc) {
            $yc['avg_progress'] = $yc['count'] > 0 ? (int) round($yc['progress_sum'] / $yc['count']) : 0;
        }
        unset($yc);

        $availableYears = array_keys($yearCards);

        $rows = $selectedYear !== null
            ? array_values(array_filter($enrichedAll, static fn ($r) => (int) $r['year'] === $selectedYear))
            : $enrichedAll;

        $totalFiles     = 0;
        $totalReceitas  = 0.0;
        $totalDespesas  = 0.0;
        $progressSum    = 0;
        $completedCount = 0;

        foreach ($rows as $r) {
            $totalFiles    += $r['files_count'];
            $totalReceitas += $r['receitas'];
            $totalDespesas += $r['despesas'];
            $progressSum   += $r['progress'];
            if ($r['confirmed'] >= $r['total_items']) {
                $completedCount++;
            }
        }

        $avgProgress = ! empty($rows) ? (int) round($progressSum / count($rows)) : 0;

        return view('history.annual', [
            'userName'       => session('user_name'),
            'username'       => session('username'),
            'userEmail'      => session('user_email'),
            'userRole'       => $role,
            'availableYears' => $availableYears,
            'yearCards'      => array_values($yearCards),
            'selectedYear'   => $selectedYear,
            'rows'           => $rows,
            'kpis'           => [
                'total_accountabilities' => count($rows),
                'completed_count'        => $completedCount,
                'total_files'            => $totalFiles,
                'avg_progress'           => $avgProgress,
                'total_receitas'         => $totalReceitas,
                'total_despesas'         => $totalDespesas,
            ],
        ]);
    }
}
