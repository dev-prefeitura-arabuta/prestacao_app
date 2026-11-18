<?php

namespace App\Controllers;

use App\Models\AccountabilityAttachmentModel;
use App\Models\AccountabilityHistoryModel;
use App\Models\AccountabilityItemStatusModel;
use App\Models\AccountabilityModel;
use App\Models\UserAccountabilityPermissionModel;
use App\Models\UserModel;
use Throwable;

class Accountability extends BaseController
{
    private array $allowedExtensions = ['zip', 'jpg', 'jpeg', 'png', 'pdf'];

    public function index(): string
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        $list                = [];
        $attachmentCounts    = [];
        $filledItemsCounts   = [];
        $progressPercentages = [];
        $totalItemsPerAcc    = [];
        $allowedItems        = [];
        $rejectedCounts      = [];
        $totalItemsCount     = count(AccountabilityModel::getItems());

        try {
            $model   = new AccountabilityModel();
            $rawList = $model->orderBy('id', 'DESC')->findAll();

            $attModel = new AccountabilityAttachmentModel();
            $allAtts  = $attModel->select('accountability_id, item_number')->findAll();

            if ($role === 'admin') {
                $allowedItems = array_keys(AccountabilityModel::getItems());
            } else {
                $permModel    = new UserAccountabilityPermissionModel();
                $allowedItems = $permModel->getAllowedItemsForUser($currentUserId);
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

            $statusModel   = new AccountabilityItemStatusModel();
            $allStatusRows = $statusModel->findAll();
            $statusByAcc   = [];
            foreach ($allStatusRows as $sRow) {
                $accId                       = (int) $sRow['accountability_id'];
                $itemNum                     = (int) $sRow['item_number'];
                $statusByAcc[$accId][$itemNum] = (string) $sRow['status'];
            }

            foreach ($rawList as $acc) {
                $accId            = (int) $acc['id'];
                $enabledForAcc    = AccountabilityModel::getEnabledItemsForAccountability($acc);
                $enabledItemNums  = array_keys($enabledForAcc);
                $accTotalItems    = count($enabledItemNums);
                $totalItemsPerAcc[$accId] = $accTotalItems;

                // Conta apenas os itens preenchidos que fazem parte desta prestação
                $filledItems = 0;
                foreach ($enabledItemNums as $eNum) {
                    if (! empty($attsByAccAndItem[$accId][$eNum])) {
                        $filledItems++;
                    }
                }

                $userAllowedInAcc = array_values(array_intersect($allowedItems, $enabledItemNums));
                $denom            = $role === 'admin' ? max(1, $accTotalItems) : max(1, count($userAllowedInAcc));

                $filledItemsCounts[$accId]   = $filledItems;
                $progressPercentages[$accId] = (int) round(($filledItems / $denom) * 100);

                if ($role === 'admin') {
                    $list[] = $acc;
                    continue;
                }

                // Se nenhum item permitido do usuário está habilitado nesta prestação, ela não aparece para ele
                if (empty($userAllowedInAcc)) {
                    continue;
                }

                // Para o usuário comum: verifica se ele já enviou esta prestação e se há algum item rejeitado
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

                // Se o usuário já enviou a prestação de contas e não há nenhum item rejeitado (nem arquivos novos pendentes de envio),
                // ela não aparece mais na lista para o usuário!
                if ($hasSentAny && ! $hasRejectedAny && ! $hasUnsentFiles) {
                    continue;
                }

                $list[] = $acc;
            }
        } catch (Throwable) {
            $list = [];
        }

        return view('accountability/index', [
            'userName'            => session()->get('user_name'),
            'username'            => session()->get('username'),
            'userEmail'           => session()->get('user_email'),
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
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect()->to('/accountability')->with('error', 'Apenas administradores podem criar novas prestações de contas.');
        }

        return view('accountability/create', [
            'userName'            => session()->get('user_name'),
            'username'            => session()->get('username'),
            'userEmail'           => session()->get('user_email'),
            'userRole'            => $role,
            'accountabilityItems' => AccountabilityModel::getItems(),
        ]);
    }

    public function store()
    {
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect()->to('/accountability')->with('error', 'Apenas administradores podem criar novas prestações de contas.');
        }

        $rules = [
            'title'       => 'required|min_length[3]|max_length[100]',
            'description' => 'permit_empty',
            'deadline'    => 'permit_empty|valid_date',
            'status'      => 'required|in_list[aberta,em_analise,concluida]',
        ];

        $messages = [
            'title' => [
                'required'   => 'Informe o título da prestação de contas.',
                'min_length' => 'O título deve ter pelo menos 3 caracteres.',
            ],
            'deadline' => [
                'valid_date' => 'Informe uma data limite válida.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $rawEnabledItems = (array) ($this->request->getPost('enabled_items') ?? []);
        $enabledItems    = [];
        foreach ($rawEnabledItems as $num) {
            $n = (int) $num;
            if ($n >= 1 && $n <= 24) {
                $enabledItems[$n] = $n;
            }
        }
        $enabledItems = array_values($enabledItems);

        if (empty($enabledItems)) {
            return redirect()->back()->withInput()->with('error', 'Selecione pelo menos um item de anexo para esta prestação de contas.');
        }

        try {
            $model    = new AccountabilityModel();
            $deadline = trim((string) $this->request->getPost('deadline'));

            $newId = $model->insert([
                'title'         => trim((string) $this->request->getPost('title')),
                'description'   => trim((string) $this->request->getPost('description')),
                'deadline'      => $deadline !== '' ? $deadline : null,
                'status'        => (string) $this->request->getPost('status'),
                'enabled_items' => json_encode($enabledItems),
            ], true);

            $this->logHistory((int) $newId, 'Prestação de contas criada com ' . count($enabledItems) . ' item(ns) habilitado(s)');

            return redirect()->to('/accountability/' . $newId)->with('success', 'Prestação de contas criada! Agora é possível indexar os anexos.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Erro ao criar prestação de contas: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        try {
            $model          = new AccountabilityModel();
            $accountability = $model->find($id);

            if ($accountability === null) {
                return redirect()->to('/accountability')->with('error', 'Prestação de contas não encontrada.');
            }

            // Obtém apenas os itens habilitados para esta Prestação de Contas
            $allItems = AccountabilityModel::getEnabledItemsForAccountability($accountability);

            if ($role === 'admin') {
                $allowedItemNumbers = array_keys($allItems);
            } else {
                $permModel          = new UserAccountabilityPermissionModel();
                $userPerms          = $permModel->getAllowedItemsForUser($currentUserId);
                $allowedItemNumbers = array_values(array_intersect($userPerms, array_keys($allItems)));
            }

            $attModel = new AccountabilityAttachmentModel();
            $rawAtts  = $attModel
                ->where('accountability_id', $id)
                ->orderBy('id', 'DESC')
                ->findAll();

            $userModel = new UserModel();
            $usersMap  = [];
            foreach ($userModel->findAll() as $u) {
                $usersMap[(int) $u['id']] = $u['name'];
            }

            $attachmentsByItem = [];
            foreach ($rawAtts as $att) {
                $itemNum = (int) $att['item_number'];
                if ($role === 'admin' || in_array($itemNum, $allowedItemNumbers, true)) {
                    $att['uploader_name']          = $usersMap[(int) $att['user_id']] ?? ('Usuário #' . $att['user_id']);
                    $attachmentsByItem[$itemNum][] = $att;
                }
            }

            // Carrega status salvos e motivos de rejeição de cada item (1 a 24)
            $savedStatuses = [];
            $savedReasons  = [];
            try {
                $statusModel = new AccountabilityItemStatusModel();
                $rows        = $statusModel->where('accountability_id', $id)->findAll();
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

                // Para o usuário comum, itens já enviados ou confirmados saem da lista;
                // se o Admin rejeitar ('rejeitado'), o item volta a aparecer para o usuário!
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

            return view('accountability/show', [
                'userName'           => session()->get('user_name'),
                'username'           => session()->get('username'),
                'userEmail'          => session()->get('user_email'),
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
            return redirect()->to('/accountability')->with('error', 'Erro ao carregar prestação de contas: ' . $e->getMessage());
        }
    }

    /**
     * Envia todos os itens nos quais o usuário anexou documentos nesta Prestação de Contas,
     * ocultando a Prestação de Contas da lista do usuário até que o Admin rejeite algum item.
     */
    public function submitAccountability(int $id)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        if ($role === 'admin') {
            $allowedItems = array_keys(AccountabilityModel::getItems());
        } else {
            $permModel    = new UserAccountabilityPermissionModel();
            $allowedItems = $permModel->getAllowedItemsForUser($currentUserId);
        }

        if (empty($allowedItems)) {
            return redirect()->to('/accountability')->with('error', 'Você não possui itens liberados nesta prestação.');
        }

        $attModel = new AccountabilityAttachmentModel();
        $atts     = $attModel
            ->where('accountability_id', $id)
            ->whereIn('item_number', $allowedItems)
            ->findAll();

        if (empty($atts)) {
            return redirect()->to('/accountability/' . $id)->with('error', 'Anexe pelo menos um documento antes de clicar em Enviar.');
        }

        $itemsWithFiles = array_values(array_unique(array_map(static fn ($a) => (int) $a['item_number'], $atts)));

        try {
            $statusModel = new AccountabilityItemStatusModel();
            $sentCount   = 0;

            foreach ($itemsWithFiles as $itemNum) {
                $existing = $statusModel
                    ->where('accountability_id', $id)
                    ->where('item_number', $itemNum)
                    ->first();

                if ($existing === null || in_array($existing['status'], ['pendente', 'rejeitado', 'prazo_vencido'], true)) {
                    $statusModel->setStatus($id, $itemNum, 'enviado', $currentUserId);
                    $sentCount++;
                }
            }

            $this->logHistory($id, "Enviou documentos da prestação de contas ({$sentCount} item(ns)) para análise");

            return redirect()->to('/accountability')
                ->with('success', 'Prestação de contas enviada com sucesso para avaliação do Administrador!');
        } catch (Throwable $e) {
            return redirect()->to('/accountability/' . $id)->with('error', 'Erro ao enviar: ' . $e->getMessage());
        }
    }

    /**
     * Envia um item específico (ou conclui o envio da prestação e retorna para a lista).
     */
    public function submitItem(int $id, int $itemNumber)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');
        $allItems      = AccountabilityModel::getItems();

        if (! isset($allItems[$itemNumber])) {
            return redirect()->to('/accountability/' . $id)->with('error', 'Item inválido.');
        }

        if ($role !== 'admin') {
            $permModel    = new UserAccountabilityPermissionModel();
            $allowedItems = $permModel->getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return redirect()->to('/accountability/' . $id)->with('error', 'Você não tem permissão para enviar este item.');
            }
        }

        $attModel = new AccountabilityAttachmentModel();
        $attCount = $attModel
            ->where('accountability_id', $id)
            ->where('item_number', $itemNumber)
            ->countAllResults();

        if ($attCount === 0) {
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)
                ->with('error', 'Anexe pelo menos um arquivo neste item antes de clicar em Enviar.');
        }

        try {
            $statusModel = new AccountabilityItemStatusModel();
            $statusModel->setStatus($id, $itemNumber, 'enviado', $currentUserId);

            $this->logHistory($id, "Enviou o Item {$itemNumber} com {$attCount} anexo(s) para análise");

            return redirect()->to('/accountability')
                ->with('success', "Documentos enviados com sucesso para avaliação!");
        } catch (Throwable $e) {
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)
                ->with('error', 'Erro ao enviar item: ' . $e->getMessage());
        }
    }

    public function updateItemStatus(int $id, int $itemNumber)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        if ($role !== 'admin') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Apenas administradores podem alterar a situação do documento.']);
            }
            return redirect()->to('/accountability/' . $id)->with('error', 'Apenas administradores podem alterar a situação do documento.');
        }

        $validStatuses   = AccountabilityItemStatusModel::getValidStatuses();
        $newStatus       = (string) $this->request->getPost('status');
        $rejectionReason = trim((string) $this->request->getPost('rejection_reason'));

        if (! isset($validStatuses[$newStatus])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['error' => 'Status inválido.']);
            }
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Status inválido.');
        }

        // Regra: Rejeitar (ou Confirmar) somente para itens que já contêm documentos anexados
        if (in_array($newStatus, ['rejeitado', 'confirmado'], true)) {
            $attModel = new AccountabilityAttachmentModel();
            $attCount = $attModel
                ->where('accountability_id', $id)
                ->where('item_number', $itemNumber)
                ->countAllResults();

            if ($attCount === 0) {
                $errMsg = 'Não é possível rejeitar ou confirmar um item que ainda não possui documentos anexados.';
                if ($this->request->isAJAX()) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'error'     => $errMsg,
                        'csrf_hash' => csrf_hash(),
                    ]);
                }
                return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', $errMsg);
            }
        }

        // Regra: Para rejeitar, é obrigatório informar o motivo para orientar o usuário
        if ($newStatus === 'rejeitado' && $rejectionReason === '') {
            $errMsg = 'Por favor, informe o motivo da rejeição para que o usuário saiba o que corrigir.';
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'error'     => $errMsg,
                    'csrf_hash' => csrf_hash(),
                ]);
            }
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', $errMsg);
        }

        try {
            $statusModel = new AccountabilityItemStatusModel();
            $statusModel->setStatus(
                $id,
                $itemNumber,
                $newStatus,
                $currentUserId,
                $newStatus === 'rejeitado' ? $rejectionReason : null
            );

            $label   = $validStatuses[$newStatus];
            $logText = $newStatus === 'rejeitado'
                ? "Rejeitou o Item {$itemNumber} — Motivo: {$rejectionReason}"
                : "Alterou situação do Item {$itemNumber} para '{$label}'";
            $this->logHistory($id, $logText);

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success'          => true,
                    'item_number'      => $itemNumber,
                    'status'           => $newStatus,
                    'label'            => $label,
                    'rejection_reason' => $newStatus === 'rejeitado' ? $rejectionReason : '',
                    'csrf_hash'        => csrf_hash(),
                ]);
            }

            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('success', "Situação do Item {$itemNumber} atualizada para {$label}!");
        } catch (Throwable $e) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
            }
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    public function upload(int $id, int $itemNumber)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');
        $allItems      = AccountabilityModel::getItems();

        if (! isset($allItems[$itemNumber])) {
            return redirect()->to('/accountability/' . $id)->with('error', 'Campo de anexo inválido.');
        }

        if ($role !== 'admin') {
            $permModel    = new UserAccountabilityPermissionModel();
            $allowedItems = $permModel->getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return redirect()->to('/accountability/' . $id)->with('error', 'Você não possui permissão para anexar arquivos no Item ' . $itemNumber . '.');
            }
        }

        $files = $this->request->getFileMultiple('attachments');
        if (empty($files)) {
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Selecione pelo menos um arquivo para anexar.');
        }

        $targetDir = WRITEPATH . 'uploads/accountability/' . $id . '/item_' . $itemNumber;
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $attModel      = new AccountabilityAttachmentModel();
        $uploadedCount = 0;
        $invalidFiles  = [];
        $now           = date('Y-m-d H:i:s');

        foreach ($files as $file) {
            if (! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            $originalName = $file->getClientName();
            $ext          = strtolower((string) $file->getClientExtension());

            if (! in_array($ext, $this->allowedExtensions, true)) {
                $invalidFiles[] = $originalName;
                continue;
            }

            $size    = (int) $file->getSize();
            $newName = $file->getRandomName();
            $file->move($targetDir, $newName);

            $attModel->insert([
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

            $uploadedCount++;
        }

        if ($uploadedCount > 0) {
            $this->logHistory($id, "Anexou {$uploadedCount} arquivo(s) no Item {$itemNumber}");
        }

        if (! empty($invalidFiles)) {
            $msg = 'Alguns arquivos foram recusados (apenas ZIP, JPG, JPEG, PNG e PDF são permitidos): ' . implode(', ', $invalidFiles);
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', $msg);
        }

        if ($uploadedCount === 0) {
            return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('error', 'Nenhum arquivo válido selecionado.');
        }

        return redirect()->to('/accountability/' . $id . '#modal-item-' . $itemNumber)->with('success', "{$uploadedCount} arquivo(s) anexado(s) no Item {$itemNumber}! Quando terminar de anexar o que deseja, clique em 'Enviar'.");
    }

    public function previewAttachment(int $attachmentId)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        $attModel   = new AccountabilityAttachmentModel();
        $attachment = $attModel->find($attachmentId);

        if ($attachment === null) {
            return $this->response->setStatusCode(404, 'Anexo não encontrado.');
        }

        $itemNumber = (int) $attachment['item_number'];
        if ($role !== 'admin') {
            $permModel    = new UserAccountabilityPermissionModel();
            $allowedItems = $permModel->getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return $this->response->setStatusCode(403, 'Sem permissão.');
            }
        }

        $fullPath = WRITEPATH . $attachment['file_path'];
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404, 'Arquivo não encontrado.');
        }

        $ext      = strtolower((string) ($attachment['file_ext'] ?? ''));
        $mimeMap  = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'zip'  => 'application/zip',
        ];
        $mimeType = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');
        $safeName = str_replace('"', '', (string) $attachment['original_name']);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . $safeName . '"')
            ->setHeader('Content-Length', (string) filesize($fullPath))
            ->setBody((string) file_get_contents($fullPath));
    }

    public function downloadAttachment(int $attachmentId)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        $attModel   = new AccountabilityAttachmentModel();
        $attachment = $attModel->find($attachmentId);

        if ($attachment === null) {
            return redirect()->back()->with('error', 'Anexo não encontrado.');
        }

        $itemNumber = (int) $attachment['item_number'];
        if ($role !== 'admin') {
            $permModel    = new UserAccountabilityPermissionModel();
            $allowedItems = $permModel->getAllowedItemsForUser($currentUserId);
            if (! in_array($itemNumber, $allowedItems, true)) {
                return redirect()->back()->with('error', 'Você não tem permissão para acessar este anexo.');
            }
        }

        $fullPath = WRITEPATH . $attachment['file_path'];
        if (! is_file($fullPath)) {
            return redirect()->back()->with('error', 'Arquivo físico não encontrado no servidor.');
        }

        return $this->response->download($fullPath, null)->setFileName($attachment['original_name']);
    }

    public function deleteAttachment(int $attachmentId)
    {
        $role          = session()->get('user_role') ?? session()->get('role') ?? 'user';
        $currentUserId = (int) session()->get('user_id');

        $attModel   = new AccountabilityAttachmentModel();
        $attachment = $attModel->find($attachmentId);

        if ($attachment === null) {
            return redirect()->back()->with('error', 'Anexo não encontrado.');
        }

        $accId      = (int) $attachment['accountability_id'];
        $itemNumber = (int) $attachment['item_number'];

        if ($role !== 'admin' && (int) $attachment['user_id'] !== $currentUserId) {
            return redirect()->to('/accountability/' . $accId . '#modal-item-' . $itemNumber)->with('error', 'Você só pode excluir anexos enviados por você.');
        }

        $fullPath = WRITEPATH . $attachment['file_path'];
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        $attModel->delete($attachmentId);
        $this->logHistory($accId, "Removeu anexo '{$attachment['original_name']}' do Item {$itemNumber}");

        return redirect()->to('/accountability/' . $accId . '#modal-item-' . $itemNumber)->with('success', 'Anexo removido com sucesso!');
    }

    public function delete(int $id)
    {
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect()->to('/accountability')->with('error', 'Apenas administradores podem excluir prestações de contas.');
        }

        try {
            $model = new AccountabilityModel();
            $model->delete($id);
            $this->logHistory($id, 'Prestação de contas excluída');

            return redirect()->to('/accountability')->with('success', 'Prestação de contas excluída com sucesso.');
        } catch (Throwable $e) {
            return redirect()->to('/accountability')->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }

    private function logHistory(int $accountabilityId, string $statusText): void
    {
        try {
            $historyModel = new AccountabilityHistoryModel();
            $historyModel->insert([
                'accountability_id' => $accountabilityId,
                'user_id'           => (int) session()->get('user_id'),
                'status'            => mb_substr($statusText, 0, 250),
            ]);
        } catch (Throwable) {
            // Ignora falha de log
        }
    }
}
