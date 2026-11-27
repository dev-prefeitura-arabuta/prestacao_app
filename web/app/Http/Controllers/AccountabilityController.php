<?php

namespace App\Http\Controllers;

use App\Exceptions\ActionException;
use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityAttachmentAi;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Models\AdminNotification;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Services\AccountabilityListingService;
use App\Services\AccountabilitySubmissionService;
use App\Services\AttachmentService;
use App\Services\ExtractiveAssistantService;
use App\Services\OllamaService;
use App\Services\SmtpMailService;
use App\Support\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AccountabilityController extends Controller
{
    public function index(AccountabilityListingService $listing)
    {
        $actor = Actor::fromSession();

        $list                = [];
        $attachmentCounts    = [];
        $filledItemsCounts   = [];
        $progressPercentages = [];
        $totalItemsPerAcc    = [];
        $rejectedCounts      = [];
        $allowedItems        = [];

        try {
            $allowedItems = $actor->allowedItems();

            foreach ($listing->activeFor($actor) as $row) {
                $accId = (int) $row['accountability']['id'];

                $list[]                      = $row['accountability'];
                $attachmentCounts[$accId]    = $row['attachments_count'];
                $filledItemsCounts[$accId]   = $row['filled_items'];
                $progressPercentages[$accId] = $row['progress'];
                $totalItemsPerAcc[$accId]    = $row['enabled_total'];
                if ($row['rejected_count'] > 0) {
                    $rejectedCounts[$accId] = $row['rejected_count'];
                }
            }
        } catch (Throwable $e) {
            report($e);
            $list = [];
        }

        return view('accountability.index', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => $actor->isAdmin ? 'admin' : 'user',
            'accountabilities'    => $list,
            'attachmentCounts'    => $attachmentCounts,
            'filledItemsCounts'   => $filledItemsCounts,
            'progressPercentages' => $progressPercentages,
            'totalItemsCount'     => count(Accountability::getItems()),
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

    public function submitAccountability(int $id, AccountabilitySubmissionService $submission)
    {
        $actor = Actor::fromSession();

        try {
            $ctx = $submission->context($actor, $id);
        } catch (ActionException $e) {
            return redirect($e->status() === 404 ? '/accountability' : '/accountability/' . $id)->with('error', $e->getMessage());
        }

        // Na Web, envia os itens que já possuem anexos
        if (empty($ctx['with_files'])) {
            return redirect('/accountability/' . $id)->with('error', 'Anexe pelo menos um documento antes de clicar em Enviar.');
        }

        try {
            $submission->submit($actor, $ctx['accountability'], $ctx['with_files'], $ctx['enabled']);
        } catch (Throwable $e) {
            report($e);
            return redirect('/accountability/' . $id)->with('error', 'Erro ao enviar a prestação. Tente novamente.');
        }

        return redirect('/accountability')
            ->with('success', 'Prestação de contas enviada com sucesso para avaliação do Administrador!');
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

            if ($role !== 'admin') {
                $accObj    = Accountability::find($id);
                $accTitle  = (string) ($accObj?->title ?? "Prestação #{$id}");
                $actorName = (string) (session('user_name') ?? 'Usuário');
                $numPad    = str_pad((string) $itemNumber, 2, '0', STR_PAD_LEFT);
                AdminNotification::notifyAdmins(
                    "Item {$numPad} enviado",
                    "{$actorName} enviou o Item {$numPad} ({$attCount} anexo(s)) na prestação \"{$accTitle}\".",
                    $id,
                    $actorName
                );
            }

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

    public function upload(Request $request, int $id, int $itemNumber, AttachmentService $attachments)
    {
        $actor    = Actor::fromSession();
        $backUrl  = '/accountability/' . $id . '#modal-item-' . $itemNumber;

        try {
            $attachments->assertCanUpload($actor, $id, $itemNumber);
        } catch (ActionException $e) {
            return redirect($e->status() === 404 ? '/accountability' : '/accountability/' . $id)->with('error', $e->getMessage());
        }

        $files = $request->file('attachments');
        if (empty($files)) {
            return redirect($backUrl)->with('error', 'Selecione pelo menos um arquivo para anexar.');
        }

        $uploadedCount = 0;
        $rejected      = [];

        foreach ((array) $files as $file) {
            try {
                $attachments->store($actor, $id, $itemNumber, $file);
                $uploadedCount++;
            } catch (ActionException $e) {
                $rejected[] = $file->getClientOriginalName() . ' (' . $e->getMessage() . ')';
            }
        }

        if ($uploadedCount > 0) {
            AccountabilityHistory::log($id, $actor->id, "Anexou {$uploadedCount} arquivo(s) no Item {$itemNumber}");
        }

        if (! empty($rejected)) {
            return redirect($backUrl)->with('error', 'Alguns arquivos foram recusados: ' . implode(', ', $rejected));
        }

        if ($uploadedCount === 0) {
            return redirect($backUrl)->with('error', 'Nenhum arquivo válido selecionado.');
        }

        return redirect($backUrl)->with('success', "{$uploadedCount} arquivo(s) anexado(s) no Item {$itemNumber}!");
    }

    public function previewAttachment(int $attachmentId, AttachmentService $attachments)
    {
        try {
            return $attachments->inlineResponse($attachments->findViewable(Actor::fromSession(), $attachmentId));
        } catch (ActionException $e) {
            abort($e->status(), $e->getMessage());
        }
    }

    public function downloadAttachment(int $attachmentId, AttachmentService $attachments)
    {
        try {
            $att = $attachments->findViewable(Actor::fromSession(), $attachmentId);
        } catch (ActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fullPath = storage_path('app/' . $att->file_path);
        if (! is_file($fullPath)) {
            return back()->with('error', 'Arquivo físico não encontrado no servidor.');
        }

        return response()->download($fullPath, $att->original_name);
    }

    public function deleteAttachment(int $attachmentId, AttachmentService $attachments)
    {
        $att = AccountabilityAttachment::find($attachmentId);
        if ($att === null) {
            return back()->with('error', 'Anexo não encontrado.');
        }
        $backUrl = '/accountability/' . (int) $att->accountability_id . '#modal-item-' . (int) $att->item_number;

        try {
            $attachments->delete(Actor::fromSession(), $attachmentId);
        } catch (ActionException $e) {
            return redirect($backUrl)->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return redirect($backUrl)->with('error', 'Erro ao remover o anexo.');
        }

        return redirect($backUrl)->with('success', 'Anexo removido com sucesso!');
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

        /* Recurso de IA temporariamente comentado
        $ollama = new OllamaService();
        $result = $ollama->analyzeAttachment($fullPath, $itemTitle, (string) $attachment->original_name, $ext);
        */
        return response()->json([
            'success'   => false,
            'error'     => 'Recurso de análise por IA temporariamente desativado.',
            'csrf_hash' => csrf_token(),
        ], 503);

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

        /* Recurso de IA temporariamente comentado
        $ollama    = new OllamaService();
        */
        return response()->json([
            'success'   => false,
            'error'     => 'Recurso de análise em lote por IA temporariamente desativado.',
            'csrf_hash' => csrf_token(),
        ], 503);
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
        AccountabilityHistory::log($accountabilityId, (int) session('user_id'), $statusText);
    }
}
