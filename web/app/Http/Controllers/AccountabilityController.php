<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityAttachmentAi;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Services\ExtractiveAssistantService;
use App\Services\OllamaService;
use App\Services\SmtpMailService;
use App\Services\SpreadsheetAiExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AccountabilityController extends Controller
{
    private array $allowedExtensions = ['zip', 'jpg', 'jpeg', 'png', 'pdf'];

    public function index()
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        $list                = [];
        $attachmentCounts    = [];
        $filledItemsCounts   = [];
        $progressPercentages = [];
        $totalItemsPerAcc    = [];
        $allowedItems        = [];
        $rejectedCounts      = [];
        $totalItemsCount     = count(Accountability::getItems());

        try {
            $rawList = Accountability::orderBy('id', 'desc')->get()->toArray();
            $allAtts = AccountabilityAttachment::select('accountability_id', 'item_number')->get()->toArray();

            if ($role === 'admin') {
                $allowedItems = array_keys(Accountability::getItems());
            } else {
                $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            }

            $attsByAccAndItem = [];
            foreach ($allAtts as $att) {
                $accId   = (int) $att['accountability_id'];
                $itemNum = (int) $att['item_number'];
                if ($role === 'admin' || in_array($itemNum, $allowedItems, true)) {
                    $attachmentCounts[$accId]            = ($attachmentCounts[$accId] ?? 0) + 1;
                    $attsByAccAndItem[$accId][$itemNum] = ($attsByAccAndItem[$accId][$itemNum] ?? 0) + 1;
                }
            }

            $allStatusRows = AccountabilityItemStatus::all()->toArray();
            $statusByAcc   = [];
            foreach ($allStatusRows as $sRow) {
                $accId                       = (int) $sRow['accountability_id'];
                $itemNum                     = (int) $sRow['item_number'];
                $statusByAcc[$accId][$itemNum] = (string) $sRow['status'];
            }

            foreach ($rawList as $acc) {
                $accId = (int) $acc['id'];

                // Prestações finalizadas ficam ocultas na lista ativa e vão apenas para o Histórico Anual
                if (Accountability::isFinalized($acc, $statusByAcc[$accId] ?? [])) {
                    continue;
                }

                $enabledForAcc            = Accountability::getEnabledItemsForAccountability($acc);
                $enabledItemNums          = array_keys($enabledForAcc);
                $accTotalItems            = count($enabledItemNums);
                $totalItemsPerAcc[$accId] = $accTotalItems;

                $filledItems = 0;
                foreach ($enabledItemNums as $eNum) {
                    if (! empty($attsByAccAndItem[$accId][$eNum])) {
                        $filledItems++;
                    }
                }

                $userAllowedInAcc            = array_values(array_intersect($allowedItems, $enabledItemNums));
                $denom                       = $role === 'admin' ? max(1, $accTotalItems) : max(1, count($userAllowedInAcc));
                $filledItemsCounts[$accId]   = $filledItems;
                $progressPercentages[$accId] = (int) round(($filledItems / $denom) * 100);

                if ($role === 'admin') {
                    $list[] = $acc;
                    continue;
                }

                // Se a prestação estiver pausada/oculta para manutenção pelo admin, não aparece para usuários comuns
                if (! empty($acc['is_paused'])) {
                    continue;
                }

                if (empty($userAllowedInAcc)) {
                    continue;
                }

                $hasSentAny     = false;
                $hasRejectedAny = false;
                $hasUnsentFiles = false;

                foreach ($userAllowedInAcc as $itemNum) {
                    $st       = $statusByAcc[$accId][$itemNum] ?? 'pendente';
                    $hasFiles = ! empty($attsByAccAndItem[$accId][$itemNum]);

                    if (in_array($st, ['enviado', 'confirmado'], true)) {
                        $hasSentAny = true;
                    } elseif ($st === 'rejeitado') {
                        $hasRejectedAny         = true;
                        $rejectedCounts[$accId] = ($rejectedCounts[$accId] ?? 0) + 1;
                    } elseif ($hasFiles && in_array($st, ['pendente', 'prazo_vencido'], true)) {
                        $hasUnsentFiles = true;
                    }
                }

                if ($hasSentAny && ! $hasRejectedAny && ! $hasUnsentFiles) {
                    continue;
                }

                $list[] = $acc;
            }
        } catch (Throwable) {
            $list = [];
        }

        return view('accountability.index', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => $role,
            'accountabilities'    => $list,
            'attachmentCounts'    => $attachmentCounts,
            'filledItemsCounts'   => $filledItemsCounts,
            'progressPercentages' => $progressPercentages,
            'totalItemsCount'     => $totalItemsCount,
            'totalItemsPerAcc'    => $totalItemsPerAcc,
            'allowedItems'        => $allowedItems,
            'rejectedCounts'      => $rejectedCounts,
        ]);
    }

    public function create()
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Apenas administradores podem criar novas prestações de contas.');
        }

        return view('accountability.create', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => $role,
            'accountabilityItems' => Accountability::getItems(),
        ]);
    }

    public function store(Request $request)
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Apenas administradores podem criar novas prestações de contas.');
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'required|min:3|max:100',
            'description' => 'nullable',
            'deadline'    => 'nullable|date',
            'status'      => 'required|in:aberta,em_analise,concluida',
        ], [
            'title.required' => 'Informe o título da prestação de contas.',
            'title.min'      => 'O título deve ter pelo menos 3 caracteres.',
            'deadline.date'  => 'Informe uma data limite válida.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('errors_list', $validator->errors()->all());
        }

        $rawEnabledItems = (array) ($request->input('enabled_items') ?? []);
        $enabledItems    = [];
        foreach ($rawEnabledItems as $num) {
            $n = (int) $num;
            if ($n >= 1 && $n <= 24) {
                $enabledItems[$n] = $n;
            }
        }
        $enabledItems = array_values($enabledItems);

        if (empty($enabledItems)) {
            return back()->withInput()->with('error', 'Selecione pelo menos um item de anexo para esta prestação de contas.');
        }

        try {
            $deadline = trim((string) $request->input('deadline'));

            $acc = Accountability::create([
                'title'         => trim((string) $request->input('title')),
                'description'   => trim((string) $request->input('description')),
                'deadline'      => $deadline !== '' ? $deadline : null,
                'status'        => (string) $request->input('status'),
                'enabled_items' => json_encode($enabledItems),
            ]);

            $this->logHistory((int) $acc->id, 'Prestação de contas criada com ' . count($enabledItems) . ' item(ns) habilitado(s)');

            return redirect('/accountability/' . $acc->id)->with('success', 'Prestação de contas criada! Agora é possível indexar os anexos.');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao criar prestação de contas: ' . $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Apenas administradores podem editar prestações de contas.');
        }

        $accObj = Accountability::find($id);
        if ($accObj === null) {
            return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
        }

        $accountability = $accObj->toArray();
        $enabledItems   = array_keys(Accountability::getEnabledItemsForAccountability($accountability));

        return view('accountability.create', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => $role,
            'isEdit'              => true,
            'accountability'      => $accountability,
            'enabledItems'        => $enabledItems,
            'accountabilityItems' => Accountability::getItems(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Apenas administradores podem editar prestações de contas.');
        }

        $acc = Accountability::find($id);
        if ($acc === null) {
            return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'required|min:3|max:100',
            'description' => 'nullable',
            'deadline'    => 'nullable|date',
            'status'      => 'required|in:aberta,em_analise,concluida',
        ], [
            'title.required' => 'Informe o título da prestação de contas.',
            'title.min'      => 'O título deve ter pelo menos 3 caracteres.',
            'deadline.date'  => 'Informe uma data limite válida.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('errors_list', $validator->errors()->all());
        }

        $rawEnabledItems = (array) ($request->input('enabled_items') ?? []);
        $enabledItems    = [];
        foreach ($rawEnabledItems as $num) {
            $n = (int) $num;
            if ($n >= 1 && $n <= 24) {
                $enabledItems[$n] = $n;
            }
        }
        $enabledItems = array_values($enabledItems);

        if (empty($enabledItems)) {
            return back()->withInput()->with('error', 'Selecione pelo menos um item de anexo para esta prestação de contas.');
        }

        try {
            $deadline = trim((string) $request->input('deadline'));

            $acc->update([
                'title'         => trim((string) $request->input('title')),
                'description'   => trim((string) $request->input('description')),
                'deadline'      => $deadline !== '' ? $deadline : null,
                'status'        => (string) $request->input('status'),
                'enabled_items' => json_encode($enabledItems),
            ]);

            $this->logHistory($id, 'Atualizou dados e campos da prestação de contas (' . count($enabledItems) . ' item(ns) habilitado(s))');

            return redirect('/accountability')->with('success', 'Prestação de contas atualizada com sucesso!');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao atualizar prestação de contas: ' . $e->getMessage());
        }
    }

    public function togglePause(int $id)
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Apenas administradores podem pausar ou reativar prestações de contas.');
        }

        try {
            $acc = Accountability::find($id);
            if ($acc === null) {
                return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
            }

            $newPaused = empty($acc->is_paused) ? 1 : 0;
            $acc->update(['is_paused' => $newPaused]);

            $this->logHistory($id, $newPaused ? 'Pausou e ocultou a prestação de contas para ajustes' : 'Reativou e tornou visível a prestação de contas');

            $msg = $newPaused
                ? 'Prestação de contas pausada e ocultada para os usuários enquanto você realiza alterações.'
                : 'Prestação de contas reativada e visível novamente para os usuários!';

            return redirect('/accountability')->with('success', $msg);
        } catch (Throwable $e) {
            return redirect('/accountability')->with('error', 'Erro ao alterar visibilidade: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        try {
            $accObj = Accountability::find($id);
            if ($accObj === null) {
                return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
            }
            $accountability = $accObj->toArray();

            if ($role !== 'admin' && ! empty($accountability['is_paused'])) {
                return redirect('/accountability')->with('error', 'Esta prestação de contas está temporariamente pausada para ajustes pelo administrador.');
            }

            $allItems = Accountability::getEnabledItemsForAccountability($accountability);

            if ($role === 'admin') {
                $allowedItemNumbers = array_keys($allItems);
            } else {
                $userPerms          = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
                $allowedItemNumbers = array_values(array_intersect($userPerms, array_keys($allItems)));
            }

            $rawAtts = AccountabilityAttachment::where('accountability_id', $id)
                ->orderBy('id', 'desc')
                ->get()
                ->toArray();

            $usersMap = [];
            foreach (User::all() as $u) {
                $usersMap[(int) $u->id] = $u->name;
            }

            $attachmentsByItem = [];
            foreach ($rawAtts as $att) {
                $itemNum = (int) $att['item_number'];
                if ($role === 'admin' || in_array($itemNum, $allowedItemNumbers, true)) {
                    $att['uploader_name']          = $usersMap[(int) $att['user_id']] ?? ('Usuário #' . $att['user_id']);
                    $attachmentsByItem[$itemNum][] = $att;
                }
            }

            $savedStatuses = [];
            $savedReasons  = [];
            try {
                $rows = AccountabilityItemStatus::where('accountability_id', $id)->get()->toArray();
                foreach ($rows as $r) {
                    $savedStatuses[(int) $r['item_number']] = (string) $r['status'];
                    $savedReasons[(int) $r['item_number']]  = (string) ($r['rejection_reason'] ?? '');
                }
            } catch (Throwable) {
                $savedStatuses = [];
                $savedReasons  = [];
            }

            $isOverdue = ! empty($accountability['deadline'])
                && strtotime((string) $accountability['deadline']) < strtotime(date('Y-m-d'));

            $visibleItems       = [];
            $itemStatuses       = [];
            $itemReasons        = [];
            $readyToSubmitCount = 0;

            foreach ($allItems as $num => $label) {
                $num = (int) $num;
                if (! in_array($num, $allowedItemNumbers, true)) {
                    continue;
                }

                if (isset($savedStatuses[$num])) {
                    $st = $savedStatuses[$num];
                } elseif ($isOverdue) {
                    $st = 'prazo_vencido';
                } else {
                    $st = 'pendente';
                }

                if ($role !== 'admin' && in_array($st, ['enviado', 'confirmado'], true)) {
                    continue;
                }

                if (! empty($attachmentsByItem[$num]) && in_array($st, ['pendente', 'rejeitado', 'prazo_vencido'], true)) {
                    $readyToSubmitCount++;
                }

                $visibleItems[$num] = $label;
                $itemStatuses[$num] = $st;
                $itemReasons[$num]  = $savedReasons[$num] ?? '';
            }

            return view('accountability.show', [
                'userName'           => session('user_name'),
                'username'           => session('username'),
                'userEmail'          => session('user_email'),
                'userRole'           => $role,
                'accountability'     => $accountability,
                'visibleItems'       => $visibleItems,
                'allowedItemsCount'  => count($allowedItemNumbers),
                'totalItemsCount'    => count($allItems),
                'attachmentsByItem'  => $attachmentsByItem,
                'itemStatuses'       => $itemStatuses,
                'itemReasons'        => $itemReasons,
                'readyToSubmitCount' => $readyToSubmitCount,
            ]);
        } catch (Throwable $e) {
            return redirect('/accountability')->with('error', 'Erro ao carregar prestação de contas: ' . $e->getMessage());
        }
    }

    public function submitAccountability(int $id)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        if ($role === 'admin') {
            return redirect('/accountability/' . $id)
                ->with('error', 'Administradores não utilizam o envio interno de prestação. Use o botão "Enviar por E-mail".');
        }

        $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);

        if (empty($allowedItems)) {
            return redirect('/accountability')->with('error', 'Você não possui itens liberados nesta prestação.');
        }

        $atts = AccountabilityAttachment::where('accountability_id', $id)
            ->whereIn('item_number', $allowedItems)
            ->get()
            ->toArray();

        if (empty($atts)) {
            return redirect('/accountability/' . $id)->with('error', 'Anexe pelo menos um documento antes de clicar em Enviar.');
        }

        $itemsWithFiles = array_values(array_unique(array_map(static fn ($a) => (int) $a['item_number'], $atts)));

        try {
            $sentCount = 0;

            foreach ($itemsWithFiles as $itemNum) {
                $existing = AccountabilityItemStatus::where('accountability_id', $id)
                    ->where('item_number', $itemNum)
                    ->first();

                if ($existing === null || in_array($existing->status, ['pendente', 'rejeitado', 'prazo_vencido'], true)) {
                    AccountabilityItemStatus::setStatus($id, $itemNum, 'enviado', $currentUserId);
                    $sentCount++;
                }
            }

            $this->logHistory($id, "Enviou documentos da prestação de contas ({$sentCount} item(ns)) para análise");

            return redirect('/accountability')
                ->with('success', 'Prestação de contas enviada com sucesso para avaliação do Administrador!');
        } catch (Throwable $e) {
            return redirect('/accountability/' . $id)->with('error', 'Erro ao enviar: ' . $e->getMessage());
        }
    }

    public function sendEmail(Request $request, int $id)
    {
        $role = session('user_role') ?? session('role') ?? 'user';
        if ($role !== 'admin') {
            return redirect('/accountability/' . $id)
                ->with('error', 'Apenas administradores podem enviar a prestação de contas por e-mail.');
        }

        $accRow = Accountability::find($id);
        if (! $accRow) {
            return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
        }

        $toEmailRaw         = trim((string) $request->input('to_email', ''));
        $subject            = trim((string) $request->input('subject', ''));
        $customMessage      = trim((string) $request->input('message', ''));
        $includeAttachments = $request->boolean('include_attachments', true);

        if ($toEmailRaw === '') {
            return redirect('/accountability/' . $id)->with('error', 'Informe o endereço de e-mail do destinatário.');
        }

        $recipients = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $toEmailRaw) ?: [])));
        if (empty($recipients)) {
            return redirect('/accountability/' . $id)->with('error', 'Informe pelo menos um e-mail válido.');
        }
        foreach ($recipients as $addr) {
            if (! filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                return redirect('/accountability/' . $id)->with('error', "O e-mail informado é inválido: {$addr}");
            }
        }

        $title = (string) ($accRow->title ?? "Prestação #{$id}");
        if ($subject === '') {
            $subject = 'Prestação de Contas — ' . $title;
        }

        $allItems = Accountability::getItems();
        $rawAtts  = AccountabilityAttachment::where('accountability_id', $id)
            ->orderBy('item_number')
            ->orderBy('id')
            ->get()
            ->toArray();

        $mailAttachments  = [];
        $totalBytes       = 0;
        $maxTotalBytes    = 20 * 1024 * 1024; // 20 MB limite seguro para SMTP
        $skippedBySize    = 0;

        if ($includeAttachments) {
            foreach ($rawAtts as $att) {
                $relPath  = (string) ($att['file_path'] ?? '');
                $fullPath = $relPath !== '' ? storage_path('app/' . $relPath) : '';
                if ($fullPath === '' || ! is_file($fullPath)) {
                    continue;
                }
                $fsize = (int) (@filesize($fullPath) ?: 0);
                if ($totalBytes + $fsize > $maxTotalBytes) {
                    $skippedBySize++;
                    continue;
                }
                $totalBytes += $fsize;
                $mailAttachments[] = [
                    'path' => $fullPath,
                    'name' => (string) ($att['original_name'] ?? basename($fullPath)),
                ];
            }
        }

        $plainText = $customMessage !== ''
            ? $customMessage
            : 'Segue em anexo a prestação de contas "' . $title . '" para conferência.';

        $htmlBody = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1e293b;line-height:1.6;">'
            . nl2br(e($plainText))
            . '</div>';

        $mailer = new SmtpMailService();
        $result = $mailer->send(implode(', ', $recipients), '', $subject, $htmlBody, $plainText, $mailAttachments);

        if (! $result['success']) {
            return redirect('/accountability/' . $id)
                ->with('error', $result['error'] ?? 'Não foi possível enviar o e-mail.');
        }

        $this->logHistory(
            $id,
            'Enviou a prestação de contas por e-mail para ' . implode(', ', $recipients) . ' (' . count($mailAttachments) . ' anexo(s))'
        );

        $extraNote = $skippedBySize > 0
            ? " ({$skippedBySize} anexo(s) não foram anexados por excederem o limite de 20 MB do SMTP)."
            : '.';

        return redirect('/accountability/' . $id)
            ->with('success', 'E-mail enviado com sucesso para ' . implode(', ', $recipients) . $extraNote);
    }

    public function submitItem(int $id, int $itemNumber)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');
        $allItems      = Accountability::getItems();

        if (! isset($allItems[$itemNumber])) {
            return redirect('/accountability/' . $id)->with('error', 'Item inválido.');
        }

        if ($role !== 'admin') {
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return redirect('/accountability/' . $id)->with('error', 'Você não tem permissão para enviar este item.');
            }
        }

        $attCount = AccountabilityAttachment::where('accountability_id', $id)
            ->where('item_number', $itemNumber)
            ->count();

        if ($attCount === 0) {
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)
                ->with('error', 'Anexe pelo menos um arquivo neste item antes de clicar em Enviar.');
        }

        try {
            AccountabilityItemStatus::setStatus($id, $itemNumber, 'enviado', $currentUserId);
            $this->logHistory($id, "Enviou o Item {$itemNumber} com {$attCount} anexo(s) para análise");

            return redirect('/accountability')->with('success', 'Documentos enviados com sucesso para avaliação!');
        } catch (Throwable $e) {
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Erro ao enviar item: ' . $e->getMessage());
        }
    }

    public function updateItemStatus(Request $request, int $id, int $itemNumber)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        if ($role !== 'admin') {
            if ($request->ajax()) {
                return response()->json(['error' => 'Apenas administradores podem alterar a situação do documento.'], 403);
            }
            return redirect('/accountability/' . $id)->with('error', 'Apenas administradores podem alterar a situação do documento.');
        }

        $validStatuses   = AccountabilityItemStatus::getValidStatuses();
        $newStatus       = (string) $request->input('status');
        $rejectionReason = trim((string) $request->input('rejection_reason'));

        if (! isset($validStatuses[$newStatus])) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Status inválido.'], 422);
            }
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Status inválido.');
        }

        if (in_array($newStatus, ['rejeitado', 'confirmado'], true)) {
            $attCount = AccountabilityAttachment::where('accountability_id', $id)
                ->where('item_number', $itemNumber)
                ->count();

            if ($attCount === 0) {
                $errMsg = 'Não é possível rejeitar ou confirmar um item que ainda não possui documentos anexados.';
                if ($request->ajax()) {
                    return response()->json(['error' => $errMsg, 'csrf_hash' => csrf_token()], 422);
                }
                return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', $errMsg);
            }
        }

        if ($newStatus === 'rejeitado' && $rejectionReason === '') {
            $errMsg = 'Por favor, informe o motivo da rejeição para que o usuário saiba o que corrigir.';
            if ($request->ajax()) {
                return response()->json(['error' => $errMsg, 'csrf_hash' => csrf_token()], 422);
            }
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', $errMsg);
        }

        try {
            AccountabilityItemStatus::setStatus(
                $id,
                $itemNumber,
                $newStatus,
                $currentUserId,
                $newStatus === 'rejeitado' ? $rejectionReason : null
            );

            // Arquivo/item rejeitado não deve constar na planilha
            if ($newStatus === 'rejeitado') {
                AccountabilitySpreadsheetEntry::where('accountability_id', $id)
                    ->where('item_number', $itemNumber)
                    ->delete();
            }

            $label   = $validStatuses[$newStatus];
            $logText = $newStatus === 'rejeitado'
                ? "Rejeitou o Item {$itemNumber} — Motivo: {$rejectionReason}"
                : "Alterou situação do Item {$itemNumber} para '{$label}'";
            $this->logHistory($id, $logText);

            if ($request->ajax()) {
                return response()->json([
                    'success'          => true,
                    'item_number'      => $itemNumber,
                    'status'           => $newStatus,
                    'label'            => $label,
                    'rejection_reason' => $newStatus === 'rejeitado' ? $rejectionReason : '',
                    'csrf_hash'        => csrf_token(),
                ]);
            }

            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('success', "Situação do Item {$itemNumber} atualizada para {$label}!");
        } catch (Throwable $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    public function upload(Request $request, int $id, int $itemNumber)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');
        $allItems      = Accountability::getItems();

        if (! isset($allItems[$itemNumber])) {
            return redirect('/accountability/' . $id)->with('error', 'Campo de anexo inválido.');
        }

        if ($role !== 'admin') {
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return redirect('/accountability/' . $id)->with('error', 'Você não possui permissão para anexar arquivos no Item ' . $itemNumber . '.');
            }
        }

        $files = $request->file('attachments');
        if (empty($files)) {
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Selecione pelo menos um arquivo para anexar.');
        }

        $targetDir = storage_path('app/uploads/accountability/' . $id . '/item_' . $itemNumber);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $uploadedCount      = 0;
        $invalidFiles       = [];
        $createdAttachments = [];
        $now                = now()->format('Y-m-d H:i:s');

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $ext          = strtolower((string) $file->getClientOriginalExtension());

            if (! in_array($ext, $this->allowedExtensions, true)) {
                $invalidFiles[] = $originalName;
                continue;
            }

            $size    = (int) $file->getSize();
            $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($targetDir, $newName);

            $attModel = AccountabilityAttachment::create([
                'accountability_id' => $id,
                'item_number'       => $itemNumber,
                'user_id'           => $currentUserId,
                'original_name'     => $originalName,
                'file_name'         => $newName,
                'file_path'         => 'uploads/accountability/' . $id . '/item_' . $itemNumber . '/' . $newName,
                'file_ext'          => $ext,
                'file_size'         => $size,
                'created_at'        => $now,
            ]);

            $createdAttachments[] = $attModel->toArray();
            $uploadedCount++;
        }

        $autoSheetInserted = 0;
        if (! empty($createdAttachments)) {
            try {
                $accRow    = Accountability::find($id);
                $extractor = new SpreadsheetAiExtractorService();
                $extRes    = $extractor->extractEntries($accRow ? $accRow->toArray() : ['id' => $id, 'title' => "Prestação #{$id}"], $createdAttachments);

                if (! empty($extRes['success']) && ! empty($extRes['entries'])) {
                    foreach ($extRes['entries'] as $entry) {
                        AccountabilitySpreadsheetEntry::create([
                            'accountability_id' => $id,
                            'item_number'       => $entry['item_number'] ?: $itemNumber,
                            'attachment_id'     => $entry['attachment_id'] ?? (count($createdAttachments) === 1 ? (int) ($createdAttachments[0]['id'] ?? 0) : null),
                            'entry_date'        => $entry['entry_date'],
                            'document_number'   => $entry['document_number'],
                            'supplier_name'     => $entry['supplier_name'],
                            'cnpj_cpf'          => $entry['cnpj_cpf'],
                            'description'       => $entry['description'],
                            'entry_type'        => $entry['entry_type'],
                            'amount'            => $entry['amount'],
                            'notes'             => $entry['notes'],
                            'created_by'        => $currentUserId,
                        ]);
                        $autoSheetInserted++;
                    }
                }
            } catch (Throwable) {
                // Não bloqueia o upload caso a IA esteja indisponível
            }
        }

        if ($uploadedCount > 0) {
            $this->logHistory($id, "Anexou {$uploadedCount} arquivo(s) no Item {$itemNumber}");
        }

        if (! empty($invalidFiles)) {
            $msg = 'Alguns arquivos foram recusados (apenas ZIP, JPG, JPEG, PNG e PDF são permitidos): ' . implode(', ', $invalidFiles);
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', $msg);
        }

        if ($uploadedCount === 0) {
            return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Nenhum arquivo válido selecionado.');
        }

        $extraAiMsg = $autoSheetInserted > 0
            ? " ✨ {$autoSheetInserted} lançamento(s) extraído(s) automaticamente pela IA para a Planilha!"
            : '';

        return redirect('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('success', "{$uploadedCount} arquivo(s) anexado(s) no Item {$itemNumber}!{$extraAiMsg}");
    }

    public function previewAttachment(int $attachmentId)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        $attachment = AccountabilityAttachment::find($attachmentId);
        if ($attachment === null) {
            abort(404, 'Anexo não encontrado.');
        }

        $itemNumber = (int) $attachment->item_number;
        if ($role !== 'admin') {
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                abort(403, 'Sem permissão.');
            }
        }

        $fullPath = storage_path('app/' . $attachment->file_path);
        if (! is_file($fullPath)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $ext      = strtolower((string) ($attachment->file_ext ?? ''));
        $mimeMap  = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'zip'  => 'application/zip',
        ];
        $mimeType = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');
        $safeName = str_replace('"', '', (string) $attachment->original_name);

        return response()->file($fullPath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
        ]);
    }

    public function downloadAttachment(int $attachmentId)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        $attachment = AccountabilityAttachment::find($attachmentId);
        if ($attachment === null) {
            return back()->with('error', 'Anexo não encontrado.');
        }

        $itemNumber = (int) $attachment->item_number;
        if ($role !== 'admin') {
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return back()->with('error', 'Você não tem permissão para acessar este anexo.');
            }
        }

        $fullPath = storage_path('app/' . $attachment->file_path);
        if (! is_file($fullPath)) {
            return back()->with('error', 'Arquivo físico não encontrado no servidor.');
        }

        return response()->download($fullPath, $attachment->original_name);
    }

    public function deleteAttachment(int $attachmentId)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        $attachment = AccountabilityAttachment::find($attachmentId);
        if ($attachment === null) {
            return back()->with('error', 'Anexo não encontrado.');
        }

        $accId      = (int) $attachment->accountability_id;
        $itemNumber = (int) $attachment->item_number;

        if ($role !== 'admin' && (int) $attachment->user_id !== $currentUserId) {
            return redirect('/accountability/' . $accId . '#modal-item-' . $itemNumber)->with('error', 'Você só pode excluir anexos enviados por você.');
        }

        $fullPath = storage_path('app/' . $attachment->file_path);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        $origName = $attachment->original_name;
        $attachment->delete();

        // Se não restar nenhum anexo neste item, volta o status automaticamente para 'pendente'
        $remainingCount = AccountabilityAttachment::where('accountability_id', $accId)
            ->where('item_number', $itemNumber)
            ->count();
        if ($remainingCount === 0) {
            AccountabilityItemStatus::setStatus($accId, $itemNumber, 'pendente', $currentUserId, null);
        }

        // Remove da planilha qualquer lançamento vinculado a este anexo excluído
        try {
            AccountabilitySpreadsheetEntry::where('accountability_id', $accId)
                ->where(static function ($q) use ($attachmentId, $itemNumber, $origName) {
                    $q->where('attachment_id', $attachmentId);
                    if ($origName !== '') {
                        $q->orWhere(static function ($q2) use ($itemNumber, $origName) {
                            $q2->where('item_number', $itemNumber)
                               ->where('notes', 'like', '%' . $origName . '%');
                        });
                    }
                })
                ->delete();
        } catch (Throwable) {
            // Não impede exclusão do anexo
        }

        $this->logHistory($accId, "Removeu anexo '{$origName}' do Item {$itemNumber}");

        return redirect('/accountability/' . $accId . '#modal-item-' . $itemNumber)->with('success', 'Anexo removido com sucesso!');
    }

    public function analyzeAttachment(int $attachmentId)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        $attachment = AccountabilityAttachment::find($attachmentId);
        if ($attachment === null) {
            return response()->json([
                'success'   => false,
                'error'     => 'Anexo não encontrado.',
                'csrf_hash' => csrf_token(),
            ], 404);
        }

        $itemNumber = (int) $attachment->item_number;
        if ($role !== 'admin') {
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return response()->json([
                    'success'   => false,
                    'error'     => 'Você não tem permissão para analisar este anexo.',
                    'csrf_hash' => csrf_token(),
                ], 403);
            }
        }

        $allItems  = Accountability::getItems();
        $itemTitle = $allItems[$itemNumber] ?? ("Item {$itemNumber}");
        $fullPath  = storage_path('app/' . $attachment->file_path);
        $ext       = strtolower((string) ($attachment->file_ext ?? ''));

        $ollama = new OllamaService();
        $result = $ollama->analyzeAttachment($fullPath, $itemTitle, (string) $attachment->original_name, $ext);

        if (! empty($result['success']) && ! empty($result['structured']) && is_array($result['structured'])) {
            try {
                $st = $result['structured'];
                AccountabilityAttachmentAi::upsertByAttachment($attachmentId, [
                    'accountability_id' => (int) $attachment->accountability_id,
                    'item_number'       => $itemNumber,
                    'document_type'     => $st['document_type'] ?? 'Documento',
                    'document_number'   => $st['document_number'] ?? '',
                    'emitter_name'      => $st['emitter_name'] ?? '',
                    'cnpj_cpf'          => $st['cnpj_cpf'] ?? '',
                    'issue_date'        => $st['issue_date'] ?? '',
                    'total_value'       => (float) ($st['total_value'] ?? 0),
                    'summary'           => $st['summary'] ?? '',
                    'compatibility'     => $st['compatibility'] ?? 'Compatível',
                    'raw_response'      => $st['raw_response'] ?? '',
                    'model_used'        => $result['model'] ?? 'gemini',
                    'analyzed_at'       => now()->format('Y-m-d H:i:s'),
                ]);
            } catch (Throwable) {
                // Ignora erro de cache
            }
        }

        $result['csrf_hash'] = csrf_token();

        return response()->json($result);
    }

    public function report(int $id)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        try {
            $accObj = Accountability::find($id);
            if ($accObj === null) {
                return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
            }
            $accountability = $accObj->toArray();

            $enabledItems = Accountability::getEnabledItemsForAccountability($accountability);
            $shortItems   = Accountability::getShortItems();

            if ($role === 'admin') {
                $allowedItemNumbers = array_keys($enabledItems);
            } else {
                $userPerms          = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
                $allowedItemNumbers = array_values(array_intersect($userPerms, array_keys($enabledItems)));
            }

            $rawAtts = AccountabilityAttachment::where('accountability_id', $id)
                ->orderBy('item_number', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->toArray();

            $attachments = [];
            foreach ($rawAtts as $att) {
                $itemNum = (int) $att['item_number'];
                if ($role === 'admin' || in_array($itemNum, $allowedItemNumbers, true)) {
                    $attachments[] = $att;
                }
            }

            $extractionsByAttId = [];
            try {
                foreach (AccountabilityAttachmentAi::where('accountability_id', $id)->get()->toArray() as $row) {
                    $extractionsByAttId[(int) $row['attachment_id']] = $row;
                }
            } catch (Throwable) {
                $extractionsByAttId = [];
            }

            $totalValue       = 0.0;
            $analyzedCount    = 0;
            $pendingAiCount   = 0;
            $totalsByItem     = [];
            $documentsEntries = [];

            foreach ($attachments as $att) {
                $attId     = (int) $att['id'];
                $itemNum   = (int) $att['item_number'];
                $itemTitle = rtrim($enabledItems[$itemNum] ?? ("Item {$itemNum}"), ';.');
                $extRow    = $extractionsByAttId[$attId] ?? null;

                if ($extRow !== null) {
                    $analyzedCount++;
                    $val = (float) ($extRow['total_value'] ?? 0);
                    $totalValue += $val;
                    $totalsByItem[$itemNum] = ($totalsByItem[$itemNum] ?? 0.0) + $val;
                } else {
                    $pendingAiCount++;
                }

                $documentsEntries[] = [
                    'attachment' => $att,
                    'item_num'   => $itemNum,
                    'item_title' => $itemTitle,
                    'item_short' => $shortItems[$itemNum] ?? ("Item {$itemNum}"),
                    'ai'         => $extRow,
                ];
            }

            $ollama       = new OllamaService();
            $ollamaStatus = $ollama->getStatus();

            return view('accountability.report', [
                'userName'         => session('user_name'),
                'username'         => session('username'),
                'userEmail'        => session('user_email'),
                'userRole'         => $role,
                'accountability'   => $accountability,
                'enabledItems'     => $enabledItems,
                'documentsEntries' => $documentsEntries,
                'totalValue'       => $totalValue,
                'totalsByItem'     => $totalsByItem,
                'totalAttachments' => count($attachments),
                'analyzedCount'    => $analyzedCount,
                'pendingAiCount'   => $pendingAiCount,
                'ollamaStatus'     => $ollamaStatus,
            ]);
        } catch (Throwable $e) {
            return redirect('/accountability/' . $id)->with('error', 'Erro ao gerar relatório: ' . $e->getMessage());
        }
    }

    public function analyzeAll(Request $request, int $id)
    {
        $role           = session('user_role') ?? session('role') ?? 'user';
        $currentUserId  = (int) session('user_id');
        $forceReanalyze = (bool) $request->input('force');

        $accObj = Accountability::find($id);
        if ($accObj === null) {
            return response()->json([
                'success'   => false,
                'error'     => 'Prestação de contas não encontrada.',
                'csrf_hash' => csrf_token(),
            ], 404);
        }

        $allItems = Accountability::getItems();
        $rawAtts  = AccountabilityAttachment::where('accountability_id', $id)
            ->orderBy('item_number', 'asc')
            ->get()
            ->toArray();

        if ($role !== 'admin') {
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
            $rawAtts      = array_values(array_filter(
                $rawAtts,
                static fn ($a) => in_array((int) $a['item_number'], $allowedItems, true)
            ));
        }

        $existing = [];
        foreach (AccountabilityAttachmentAi::where('accountability_id', $id)->get()->toArray() as $row) {
            $existing[(int) $row['attachment_id']] = $row;
        }

        $ollama    = new OllamaService();
        $processed = 0;
        $errors    = [];

        foreach ($rawAtts as $att) {
            $attId = (int) $att['id'];
            if (! $forceReanalyze && isset($existing[$attId])) {
                continue;
            }

            $itemNum   = (int) $att['item_number'];
            $itemTitle = $allItems[$itemNum] ?? ("Item {$itemNum}");
            $fullPath  = storage_path('app/' . $att['file_path']);
            $ext       = strtolower((string) ($att['file_ext'] ?? ''));

            $res = $ollama->analyzeAttachment($fullPath, $itemTitle, (string) $att['original_name'], $ext);
            if (! empty($res['success']) && ! empty($res['structured']) && is_array($res['structured'])) {
                $st = $res['structured'];
                AccountabilityAttachmentAi::upsertByAttachment($attId, [
                    'accountability_id' => $id,
                    'item_number'       => $itemNum,
                    'document_type'     => $st['document_type'] ?? 'Documento',
                    'document_number'   => $st['document_number'] ?? '',
                    'emitter_name'      => $st['emitter_name'] ?? '',
                    'cnpj_cpf'          => $st['cnpj_cpf'] ?? '',
                    'issue_date'        => $st['issue_date'] ?? '',
                    'total_value'       => (float) ($st['total_value'] ?? 0),
                    'summary'           => $st['summary'] ?? '',
                    'compatibility'     => $st['compatibility'] ?? 'Compatível',
                    'raw_response'      => $st['raw_response'] ?? '',
                    'model_used'        => $res['model'] ?? 'gemini',
                    'analyzed_at'       => now()->format('Y-m-d H:i:s'),
                ]);
                $processed++;
            } else {
                $errors[] = ($att['original_name'] ?? "Anexo #{$attId}") . ': ' . ($res['error'] ?? 'Falha ao analisar');
            }
        }

        $this->logHistory($id, "Executou análise inteligente por IA em {$processed} documento(s) indexado(s)");

        return response()->json([
            'success'   => true,
            'processed' => $processed,
            'errors'    => $errors,
            'csrf_hash' => csrf_token(),
        ]);
    }

    public function localAssistant(Request $request, int $id)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        try {
            $accObj = Accountability::find($id);
            if ($accObj === null) {
                return response()->json([
                    'success'   => false,
                    'error'     => 'Prestação de contas não encontrada.',
                    'csrf_hash' => csrf_token(),
                ], 404);
            }

            $accountability = $accObj->toArray();

            if ($role !== 'admin' && ! empty($accountability['is_paused'])) {
                return response()->json([
                    'success'   => false,
                    'error'     => 'Esta prestação de contas está temporariamente pausada.',
                    'csrf_hash' => csrf_token(),
                ], 403);
            }

            $enabledItems = Accountability::getEnabledItemsForAccountability($accountability);

            if ($role === 'admin') {
                $visibleItems = $enabledItems;
            } else {
                $userPerms    = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
                $visibleItems = [];
                foreach ($enabledItems as $num => $label) {
                    if (in_array((int) $num, $userPerms, true)) {
                        $visibleItems[(int) $num] = $label;
                    }
                }
            }

            $allowedItemNumbers = array_keys($visibleItems);

            $itemStatuses = [];
            $itemReasons  = [];
            foreach (AccountabilityItemStatus::where('accountability_id', $id)->get() as $stRow) {
                $itemNum                = (int) $stRow->item_number;
                $itemStatuses[$itemNum] = (string) $stRow->status;
                $itemReasons[$itemNum]  = (string) ($stRow->rejection_reason ?? '');
            }

            $rawAtts = AccountabilityAttachment::where('accountability_id', $id)
                ->orderBy('item_number', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->toArray();

            $attachments = [];
            foreach ($rawAtts as $att) {
                $itemNum = (int) $att['item_number'];
                if (in_array($itemNum, $allowedItemNumbers, true)) {
                    $attachments[] = $att;
                }
            }

            $question = trim((string) $request->input('question', 'resumo geral'));
            $service  = new ExtractiveAssistantService();
            $result   = $service->answer(
                $question,
                $accountability,
                $visibleItems,
                $itemStatuses,
                $itemReasons,
                $attachments
            );

            $result['csrf_hash'] = csrf_token();

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success'   => false,
                'error'     => 'Erro no assistente local: ' . $e->getMessage(),
                'csrf_hash' => csrf_token(),
            ], 500);
        }
    }

    public function printPdf(int $id)
    {
        $role          = session('user_role') ?? session('role') ?? 'user';
        $currentUserId = (int) session('user_id');

        try {
            $accObj = Accountability::find($id);
            if ($accObj === null) {
                return redirect('/accountability')->with('error', 'Prestação de contas não encontrada.');
            }
            $accountability = $accObj->toArray();

            $enabledItems = Accountability::getEnabledItemsForAccountability($accountability);

            if ($role === 'admin') {
                $allowedItemNumbers = array_keys($enabledItems);
            } else {
                $userPerms          = UserAccountabilityPermission::getAllowedItemsForUser($currentUserId);
                $allowedItemNumbers = array_values(array_intersect($userPerms, array_keys($enabledItems)));
            }

            $rawAtts = AccountabilityAttachment::where('accountability_id', $id)
                ->orderBy('item_number', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->toArray();

            $usersMap = [];
            foreach (User::all() as $u) {
                $usersMap[(int) $u->id] = $u->name;
            }

            $attachments = [];
            foreach ($rawAtts as $att) {
                $itemNum = (int) $att['item_number'];
                if ($role !== 'admin' && ! in_array($itemNum, $allowedItemNumbers, true)) {
                    continue;
                }

                $fullPath = storage_path('app/' . $att['file_path']);
                if (! is_file($fullPath)) {
                    continue;
                }

                $att['item_title']    = rtrim($enabledItems[$itemNum] ?? ("Item {$itemNum}"), ';.');
                $att['uploader_name'] = $usersMap[(int) $att['user_id']] ?? ('Usuário #' . $att['user_id']);
                $att['preview_url']   = url('accountability/attachment/' . $att['id'] . '/preview');
                $attachments[]        = $att;
            }

            $generalSettings = GeneralSettings::getSettings();

            return view('accountability.print_pdf', [
                'accountability'  => $accountability,
                'attachments'     => $attachments,
                'generalSettings' => $generalSettings,
                'userName'        => session('user_name'),
                'userEmail'       => session('user_email'),
                'userRole'        => $role,
            ]);
        } catch (Throwable $e) {
            return redirect('/accountability/' . $id)->with('error', 'Erro ao preparar PDF consolidado: ' . $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Apenas administradores podem excluir prestações de contas.');
        }

        try {
            $acc = Accountability::find($id);
            if ($acc !== null) {
                $acc->delete();
            }
            $this->logHistory($id, 'Prestação de contas excluída');

            return redirect('/accountability')->with('success', 'Prestação de contas excluída com sucesso.');
        } catch (Throwable $e) {
            return redirect('/accountability')->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }

    private function logHistory(int $accountabilityId, string $statusText): void
    {
        try {
            AccountabilityHistory::create([
                'accountability_id' => $accountabilityId,
                'user_id'           => (int) session('user_id'),
                'status'            => mb_substr($statusText, 0, 250),
            ]);
        } catch (Throwable) {
            // Ignora falha de log
        }
    }
}
