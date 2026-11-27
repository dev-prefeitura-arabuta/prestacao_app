<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ActionException;
use App\Http\Controllers\AnnualHistoryController;
use App\Http\Controllers\Controller;
use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Models\AdminNotification;
use App\Models\SmtpConfig;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Models\WhatsappConfig;
use App\Services\AccountabilityListingService;
use App\Services\AccountabilitySubmissionService;
use App\Services\AttachmentService;
use App\Services\GeminiSummaryService;
use App\Services\MobileTokenService;
use App\Services\OpenWaService;
use App\Services\PasswordResetService;
use App\Services\ProfileService;
use App\Support\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileApiController extends Controller
{
    public function ping()
    {
        return response()->json([
            'ok'      => true,
            'app'     => 'Prestação de Contas API',
            'version' => '1.0.0',
        ]);
    }

    public function quickLoginUsers()
    {
        // Lista de usuários só é exposta no modo de demonstração (QUICK_LOGIN=true)
        if (! config('app.quick_login')) {
            return response()->json(['success' => false, 'error' => 'Login rápido desativado.'], 404);
        }

        $users = User::orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->get()
            ->map(static fn ($u) => [
                'id'    => (int) $u->id,
                'name'  => (string) $u->name,
                'email' => (string) $u->email,
                'role'  => (string) ($u->role ?? 'user'),
            ])
            ->all();

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }

    public function login(Request $request, MobileTokenService $tokens)
    {
        $email    = PasswordResetService::normalizeEmail((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return response()->json(['success' => false, 'error' => 'Informe e-mail e senha.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (! $user || ! password_verify($password, (string) $user->password_hashed)) {
            return response()->json(['success' => false, 'error' => 'E-mail ou senha inválidos.'], 401);
        }

        return response()->json([
            'success' => true,
            'token'   => $tokens->issue($user),
            'user'    => $this->formatUser($user),
        ]);
    }

    public function forgotPassword(Request $request, PasswordResetService $passwordReset)
    {
        $email = PasswordResetService::normalizeEmail((string) $request->input('email', ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'error' => 'Informe um e-mail válido.'], 422);
        }

        $result = $passwordReset->issueCode($email);
        if (! $result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 500);
        }

        return response()->json([
            'success' => true,
            'message' => PasswordResetService::GENERIC_SENT_MESSAGE,
        ]);
    }

    public function verifyResetCode(Request $request, PasswordResetService $passwordReset)
    {
        $email = (string) $request->input('email', '');
        $code  = preg_replace('/\D/', '', (string) $request->input('code', '')) ?? '';

        if (trim($email) === '' || strlen($code) !== 6) {
            return response()->json(['success' => false, 'error' => 'Informe o e-mail e o código de 6 dígitos.'], 422);
        }

        $result = $passwordReset->verifyCode($email, $code);
        if ($result['token'] === null) {
            return response()->json(['success' => false, 'error' => $result['error']], 422);
        }

        return response()->json(['success' => true, 'message' => 'Código validado! Agora defina sua nova senha.']);
    }

    public function resetPassword(Request $request, PasswordResetService $passwordReset)
    {
        $email    = (string) $request->input('email', '');
        $code     = preg_replace('/\D/', '', (string) $request->input('code', '')) ?? '';
        $password = (string) $request->input('password', '');
        $confirm  = (string) $request->input('password_confirmation', '');

        if (trim($email) === '' || strlen($code) !== 6) {
            return response()->json(['success' => false, 'error' => 'E-mail e código de 6 dígitos são obrigatórios.'], 422);
        }
        if (strlen($password) < 6) {
            return response()->json(['success' => false, 'error' => 'A nova senha deve ter pelo menos 6 caracteres.'], 422);
        }
        if ($password !== $confirm) {
            return response()->json(['success' => false, 'error' => 'A confirmação de senha não confere.'], 422);
        }

        $result = $passwordReset->verifyCode($email, $code);
        if ($result['token'] === null) {
            return response()->json(['success' => false, 'error' => $result['error']], 422);
        }

        if (! $passwordReset->resetPassword($result['token'], $password)) {
            return response()->json(['success' => false, 'error' => 'Não foi possível redefinir a senha. Solicite um novo código.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Senha redefinida com sucesso! Faça login com sua nova senha.']);
    }

    public function logout(Request $request, MobileTokenService $tokens)
    {
        $tokens->revoke(MobileTokenService::extractToken($request));

        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($user),
        ]);
    }

    public function updateProfile(Request $request, ProfileService $profiles)
    {
        try {
            $user = $profiles->update(
                $request->user(),
                $request->only(['name', 'email', 'whatsapp', 'new_password', 'confirm_password']),
                $request->file('avatar')
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->validator->errors()->first()], 422);
        } catch (ActionException $e) {
            return $this->actionError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'Erro ao atualizar perfil.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!',
            'user'    => $this->formatUser($user),
        ]);
    }

    public function removeAvatar(Request $request, ProfileService $profiles)
    {
        try {
            $user = $profiles->removeAvatar($request->user());
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'Erro ao remover foto.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil removida com sucesso!',
            'user'    => $this->formatUser($user),
        ]);
    }

    public function avatar(int $id, ProfileService $profiles)
    {
        try {
            return $profiles->avatarResponse($id);
        } catch (ActionException) {
            abort(404);
        }
    }

    private function actionError(ActionException $e)
    {
        return response()->json(['success' => false, 'error' => $e->getMessage()], $e->status());
    }

    private function formatUser(User $user): array
    {
        return [
            'id'         => (int) $user->id,
            'name'       => (string) $user->name,
            'email'      => (string) $user->email,
            'whatsapp'   => (string) ($user->whatsapp ?? ''),
            'role'       => (string) ($user->role ?? 'user'),
            'avatar_url' => ! empty($user->avatar)
                ? '/api/mobile/avatar/' . (int) $user->id . '?v=' . time()
                : null,
        ];
    }

    public function accountabilities(Request $request, AccountabilityListingService $listing)
    {
        $items = array_map(static function (array $row) {
            $acc = $row['accountability'];

            return [
                'id'                => (int) $acc['id'],
                'title'             => (string) ($acc['title'] ?? ''),
                'description'       => (string) ($acc['description'] ?? ''),
                'deadline'          => ! empty($acc['deadline']) ? date('d/m/Y', strtotime((string) $acc['deadline'])) : 'Não definido',
                'is_paused'         => ! empty($acc['is_paused']),
                'attachments_count' => $row['attachments_count'],
                'filled_items'      => $row['filled_items'],
                'total_items'       => $row['visible_total'],
                'progress'          => $row['progress'],
            ];
        }, $listing->activeFor(Actor::fromUser($request->user())));

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    public function createAccountability(Request $request)
    {
        $user = $request->user();

        $title       = trim((string) $request->input('title', ''));
        $description = trim((string) $request->input('description', ''));
        $rawDeadline = trim((string) $request->input('deadline', ''));
        $status      = (string) $request->input('status', 'aberta');
        if (! in_array($status, ['aberta', 'em_analise', 'concluida'], true)) {
            $status = 'aberta';
        }

        // Aceita DD/MM/AAAA ou AAAA-MM-DD
        $deadline = null;
        if ($rawDeadline !== '') {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawDeadline, $m)) {
                if (checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                    $deadline = "{$m[3]}-{$m[2]}-{$m[1]}";
                } else {
                    return response()->json(['success' => false, 'error' => 'Data limite inválida. Use o formato DD/MM/AAAA.'], 422);
                }
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDeadline)) {
                $deadline = $rawDeadline;
            } else {
                return response()->json(['success' => false, 'error' => 'Data limite inválida. Use o formato DD/MM/AAAA.'], 422);
            }
        }

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100) {
            return response()->json(['success' => false, 'error' => 'O título deve ter entre 3 e 100 caracteres.'], 422);
        }

        $rawEnabledItems = $request->input('enabled_items');
        $enabledItems    = [];
        if (is_array($rawEnabledItems) && ! empty($rawEnabledItems)) {
            foreach ($rawEnabledItems as $num) {
                $n = (int) $num;
                if ($n >= 1 && $n <= 24) {
                    $enabledItems[$n] = $n;
                }
            }
            $enabledItems = array_values($enabledItems);
        } else {
            $enabledItems = array_keys(Accountability::getItems());
        }

        try {
            $acc = Accountability::create([
                'title'         => $title,
                'description'   => $description !== '' ? $description : null,
                'deadline'      => $deadline,
                'status'        => $status,
                'enabled_items' => json_encode($enabledItems),
            ]);

            AccountabilityHistory::log((int) $acc->id, (int) $user->id, 'Prestação de contas criada via Mobile com ' . count($enabledItems) . ' item(ns) habilitado(s)');

            return response()->json([
                'success' => true,
                'message' => 'Prestação de contas criada com sucesso!',
                'data'    => [
                    'id'    => (int) $acc->id,
                    'title' => (string) $acc->title,
                ],
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao criar prestação: ' . $e->getMessage()], 500);
        }
    }

    public function showAccountability(Request $request, int $id)
    {
        $user = $request->user();

        $accObj = Accountability::find($id);
        if (! $accObj) {
            return response()->json(['success' => false, 'error' => 'Prestação não encontrada.'], 404);
        }

        $acc          = $accObj->toArray();
        $isAdmin      = ($user->role === 'admin');
        $enabledItems = Accountability::getEnabledItemsForAccountability($acc);
        $allowedItems = $isAdmin
            ? array_keys($enabledItems)
            : array_values(array_intersect(UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id), array_keys($enabledItems)));

        $attsByItem = [];
        foreach (AccountabilityAttachment::where('accountability_id', $id)->orderBy('id', 'desc')->get() as $att) {
            $attsByItem[(int) $att->item_number][] = [
                'id'            => (int) $att->id,
                'original_name' => (string) $att->original_name,
                'file_ext'      => (string) $att->file_ext,
                'file_size'     => (int) $att->file_size,
                'created_at'    => (string) ($att->created_at ?? ''),
            ];
        }

        $statuses = [];
        $reasons  = [];
        foreach (AccountabilityItemStatus::where('accountability_id', $id)->get() as $st) {
            $statuses[(int) $st->item_number] = (string) $st->status;
            $reasons[(int) $st->item_number]  = (string) ($st->rejection_reason ?? '');
        }

        $isOverdue = ! empty($acc['deadline']) && strtotime((string) $acc['deadline']) < strtotime(date('Y-m-d'));

        if (! $isAdmin) {
            if (! empty($acc['is_paused']) || empty($allowedItems)) {
                return response()->json(['success' => false, 'error' => 'Você não tem acesso a esta prestação de contas.'], 403);
            }
            if (Accountability::isFinalized($acc, $statuses)) {
                return response()->json(['success' => false, 'error' => 'Esta prestação de contas já foi finalizada.'], 403);
            }

            $hasSentAny     = false;
            $hasRejectedAny = false;
            $hasUnsentFiles = false;
            foreach ($allowedItems as $n) {
                $st       = $statuses[$n] ?? 'pendente';
                $hasFiles = ! empty($attsByItem[$n]);
                if (in_array($st, ['enviado', 'confirmado'], true)) {
                    $hasSentAny = true;
                } elseif ($st === 'rejeitado') {
                    $hasRejectedAny = true;
                } elseif ($hasFiles && in_array($st, ['pendente', 'prazo_vencido'], true)) {
                    $hasUnsentFiles = true;
                }
            }
            if ($hasSentAny && ! $hasRejectedAny && ! $hasUnsentFiles) {
                return response()->json(['success' => false, 'error' => 'Você já confirmou o envio desta prestação de contas.'], 403);
            }
        }

        $usersByItem = [];
        if ($isAdmin) {
            $nonAdminUsers = [];
            foreach (User::where('role', '!=', 'admin')->orderBy('name', 'asc')->get() as $u) {
                $nonAdminUsers[(int) $u->id] = [
                    'id'       => (int) $u->id,
                    'name'     => (string) $u->name,
                    'whatsapp' => (string) ($u->whatsapp ?? ''),
                ];
            }
            foreach (UserAccountabilityPermission::all() as $perm) {
                $uid = (int) $perm->user_id;
                $in  = (int) $perm->item_number;
                if (isset($nonAdminUsers[$uid])) {
                    $usersByItem[$in][$uid] = $nonAdminUsers[$uid];
                }
            }
        }

        $checklist          = [];
        $allItemsFilled     = true;
        $hasSubmittableItem = false;
        foreach ($enabledItems as $num => $label) {
            $num = (int) $num;
            if (! in_array($num, $allowedItems, true)) {
                continue;
            }
            $st   = $statuses[$num] ?? ($isOverdue ? 'prazo_vencido' : 'pendente');
            $atts = $attsByItem[$num] ?? [];
            if (empty($atts)) {
                $allItemsFilled = false;
            }
            if (in_array($st, ['pendente', 'rejeitado', 'prazo_vencido'], true)) {
                $hasSubmittableItem = true;
            }
            $checklist[] = [
                'item_number'      => $num,
                'title'            => rtrim((string) $label, ';.'),
                'status'           => $st,
                'rejection_reason' => $reasons[$num] ?? '',
                'attachments'      => $atts,
                'assigned_users'   => array_values($usersByItem[$num] ?? []),
            ];
        }

        $adminContacts = [];
        if (! $isAdmin) {
            foreach (User::where('role', 'admin')->orderBy('id', 'asc')->get() as $adm) {
                $admDigits = preg_replace('/\D/', '', (string) ($adm->whatsapp ?? '')) ?? '';
                if (strlen($admDigits) >= 10) {
                    $adminContacts[] = [
                        'id'       => (int) $adm->id,
                        'name'     => (string) $adm->name,
                        'whatsapp' => (string) $adm->whatsapp,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => (int) $acc['id'],
                'title'            => (string) ($acc['title'] ?? ''),
                'description'      => (string) ($acc['description'] ?? ''),
                'deadline'         => ! empty($acc['deadline']) ? date('d/m/Y', strtotime((string) $acc['deadline'])) : 'Não definido',
                'all_items_filled' => $allItemsFilled && ! empty($checklist),
                'can_submit'       => ! $isAdmin && $allItemsFilled && ! empty($checklist) && $hasSubmittableItem,
                'admin_contacts'   => $adminContacts,
                'items'            => $checklist,
            ],
        ]);
    }

    public function submitAccountability(Request $request, int $id, AccountabilitySubmissionService $submission)
    {
        $user  = $request->user();
        $actor = Actor::fromUser($user);

        try {
            $ctx = $submission->context($actor, $id);
        } catch (ActionException $e) {
            return $this->actionError($e);
        }

        // No app, o envio só é liberado quando todos os itens do usuário possuem anexo
        if (count(array_diff($ctx['allowed'], $ctx['with_files'])) > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'Adicione pelo menos um anexo em todos os seus itens antes de confirmar o envio.',
            ], 422);
        }

        try {
            $result    = $submission->submit($actor, $ctx['accountability'], $ctx['allowed'], $ctx['enabled'], ' via Mobile');
            $sentCount = $result['sent_count'];

            $allAccountabilityFilled = $result['all_filled'];
            $notifyAdmin             = (bool) $request->input('notify_admin', true);
            $accTitle                = (string) ($ctx['accountability']->title ?: "Prestação #{$id}");

            $waMessage   = $allAccountabilityFilled
                ? "Olá! ✅ Todos os anexos foram enviados na prestação de contas *{$accTitle}* (envio finalizado por {$user->name}). Já está pronta para conferência!"
                : "Olá! ✅ Todos os anexos de *{$user->name}* foram enviados na prestação de contas *{$accTitle}* ({$sentCount} item(ns)).";

            $adminWaLinks = [];
            $sentViaApi   = false;
            $waCfg        = null;
            try {
                $waCfg = WhatsappConfig::first();
            } catch (Throwable) {
            }
            $waApiActive = $notifyAdmin && $waCfg && (int) ($waCfg->status ?? 0) === 1;

            foreach (User::where('role', 'admin')->orderBy('id', 'asc')->get() as $adm) {
                $digits = preg_replace('/\D/', '', (string) ($adm->whatsapp ?? '')) ?? '';
                if (strlen($digits) < 10) {
                    continue;
                }
                if (strlen($digits) <= 11 && ! str_starts_with($digits, '55')) {
                    $digits = '55' . $digits;
                }

                $adminWaLinks[] = [
                    'id'    => (int) $adm->id,
                    'name'  => (string) $adm->name,
                    'phone' => (string) $adm->whatsapp,
                    'url'   => 'https://wa.me/' . $digits . '?text=' . rawurlencode($waMessage),
                ];

                if ($waApiActive) {
                    try {
                        $waService = new OpenWaService();
                        $res       = $waService->sendText($digits, $waMessage);
                        if (! empty($res['success'])) {
                            $sentViaApi = true;
                        }
                    } catch (Throwable) {
                    }
                }
            }

            return response()->json([
                'success'                   => true,
                'message'                   => 'Envio confirmado! Sua prestação de contas foi encaminhada para avaliação do Administrador.',
                'all_accountability_filled' => $allAccountabilityFilled,
                'notified_via_api'          => $sentViaApi,
                'admin_wa_links'            => $adminWaLinks,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao confirmar envio: ' . $e->getMessage()], 500);
        }
    }

    public function remindWhatsapp(Request $request, int $id)
    {
        $admin = $request->user();

        $acc = Accountability::find($id);
        if (! $acc) {
            return response()->json(['success' => false, 'error' => 'Prestação de contas não encontrada.'], 404);
        }

        $targetUserId = (int) $request->input('user_id', 0);
        $targetUser   = User::find($targetUserId);
        if (! $targetUser) {
            return response()->json(['success' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $digits = preg_replace('/\D/', '', (string) ($targetUser->whatsapp ?? '')) ?? '';
        if (strlen($digits) < 10) {
            return response()->json(['success' => false, 'error' => 'Este usuário não possui número de WhatsApp cadastrado.'], 422);
        }
        if (strlen($digits) <= 11 && ! str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        $itemNumber = (int) $request->input('item_number', 0);
        $customText = trim((string) $request->input('custom_message', ''));
        $deadline   = ! empty($acc->deadline) ? date('d/m/Y', strtotime((string) $acc->deadline)) : 'Não definido';

        if ($customText !== '') {
            $text = $customText;
        } else {
            $allItems = Accountability::getItems();
            if ($itemNumber > 0 && isset($allItems[$itemNumber])) {
                $itemLabel = rtrim((string) $allItems[$itemNumber], ';.');
                $numPad    = str_pad((string) $itemNumber, 2, '0', STR_PAD_LEFT);
                $text      = "Olá, {$targetUser->name}! 👋\n\n"
                    . "Lembrete da *Prestação de Contas: {$acc->title}* (Prazo: {$deadline}).\n\n"
                    . "Ainda aguardamos o envio dos anexos do *Item {$numPad} — {$itemLabel}*.\n\n"
                    . "Por favor, acesse o aplicativo Prestação de Contas para anexar os documentos e confirmar o envio. Obrigado!";
            } else {
                $text = "Olá, {$targetUser->name}! 👋\n\n"
                    . "Lembrete da *Prestação de Contas: {$acc->title}* (Prazo: {$deadline}).\n\n"
                    . "Você possui itens pendentes de envio nesta prestação de contas.\n\n"
                    . "Por favor, acesse o aplicativo Prestação de Contas para anexar os documentos e confirmar o envio. Obrigado!";
            }
        }

        $waUrl      = 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
        $sentViaApi = false;
        $apiNote    = '';

        try {
            $waCfg = WhatsappConfig::first();
            if ($waCfg && (int) ($waCfg->status ?? 0) === 1) {
                $waService = new OpenWaService();
                $res       = $waService->sendText($digits, $text);
                if (! empty($res['success'])) {
                    $sentViaApi = true;
                } else {
                    $apiNote = (string) ($res['error'] ?? 'API WhatsApp indisponível no servidor.');
                }
            } else {
                $apiNote = 'API OpenWA não ativa no servidor; abrindo WhatsApp no celular.';
            }
        } catch (Throwable $e) {
            $apiNote = $e->getMessage();
        }

        $itemInfo = $itemNumber > 0 ? " (Item {$itemNumber})" : '';
        AccountabilityHistory::log($id, (int) $admin->id, "Enviou cobrança/lembrete via WhatsApp para {$targetUser->name}{$itemInfo}");

        return response()->json([
            'success'      => true,
            'sent_via_api' => $sentViaApi,
            'message'      => $sentViaApi
                ? "Cobrança enviada automaticamente para {$targetUser->name} via WhatsApp!"
                : $apiNote,
            'wa_url'       => $waUrl,
            'text'         => $text,
        ]);
    }

    public function uploadAttachment(Request $request, int $id, int $itemNumber, AttachmentService $attachments)
    {
        $actor = Actor::fromUser($request->user());

        $file = $request->file('file');
        if (! $file) {
            return response()->json(['success' => false, 'error' => 'Nenhum arquivo recebido. Se a foto for muito grande, tente novamente com menor resolução.'], 422);
        }

        try {
            $attachments->assertCanUpload($actor, $id, $itemNumber);
            $att = $attachments->store($actor, $id, $itemNumber, $file);
        } catch (ActionException $e) {
            return $this->actionError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'Erro ao salvar anexo no servidor.'], 500);
        }

        AccountabilityHistory::log($id, $actor->id, "Anexou 1 arquivo(s) no Item {$itemNumber} via Mobile");

        return response()->json([
            'success'    => true,
            'attachment' => [
                'id'            => (int) $att->id,
                'original_name' => (string) $att->original_name,
                'file_ext'      => (string) $att->file_ext,
                'file_size'     => (int) $att->file_size,
            ],
        ]);
    }

    public function previewAttachment(Request $request, int $attachmentId, AttachmentService $attachments)
    {
        try {
            $att = $attachments->findViewable(Actor::fromUser($request->user()), $attachmentId);

            return $attachments->inlineResponse($att);
        } catch (ActionException $e) {
            return $this->actionError($e);
        }
    }

    public function deleteAttachment(Request $request, int $attachmentId, AttachmentService $attachments)
    {
        try {
            $attachments->delete(Actor::fromUser($request->user()), $attachmentId);
        } catch (ActionException $e) {
            return $this->actionError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'Erro ao remover anexo.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Anexo removido com sucesso!',
        ]);
    }

    public function annualHistory(Request $request)
    {
        $user = $request->user();

        $statusByAcc = [];
        foreach (AccountabilityItemStatus::all() as $st) {
            $statusByAcc[(int) $st->accountability_id][(int) $st->item_number] = (string) $st->status;
        }

        $byYear = [];
        foreach (Accountability::orderBy('created_at', 'desc')->get()->toArray() as $acc) {
            $aid = (int) $acc['id'];
            if (! Accountability::isFinalized($acc, $statusByAcc[$aid] ?? [])) {
                continue;
            }
            $yr = AnnualHistoryController::resolveRecordYear($acc);
            $byYear[$yr][] = [
                'id'       => $aid,
                'year'     => $yr,
                'title'    => (string) ($acc['title'] ?? ''),
                'deadline' => ! empty($acc['deadline']) ? date('d/m/Y', strtotime((string) $acc['deadline'])) : 'Não definido',
            ];
        }

        krsort($byYear);

        return response()->json([
            'success' => true,
            'years'   => $byYear,
        ]);
    }

    public function spreadsheetSummary(Request $request)
    {
        $user = $request->user();

        $attCounts = [];
        foreach (AccountabilityAttachment::select('accountability_id')->get() as $att) {
            $aid             = (int) $att->accountability_id;
            $attCounts[$aid] = ($attCounts[$aid] ?? 0) + 1;
        }

        $rejectedByAcc = [];
        foreach (AccountabilityItemStatus::where('status', 'rejeitado')->get() as $st) {
            $rejectedByAcc[(int) $st->accountability_id][] = (int) $st->item_number;
        }

        $sumByAcc = [];
        foreach (AccountabilitySpreadsheetEntry::all() as $e) {
            $aid  = (int) $e->accountability_id;
            $iNum = $e->item_number !== null ? (int) $e->item_number : null;
            if ($iNum !== null && in_array($iNum, $rejectedByAcc[$aid] ?? [], true)) {
                continue;
            }
            if (! isset($sumByAcc[$aid])) {
                $sumByAcc[$aid] = ['entries' => 0, 'receitas' => 0.0, 'aportes' => 0.0, 'despesas' => 0.0];
            }
            $sumByAcc[$aid]['entries']++;
            $amt  = (float) ($e->amount ?? 0);
            $type = (string) ($e->entry_type ?? 'despesa');
            if ($type === 'receita') {
                $sumByAcc[$aid]['receitas'] += $amt;
            } elseif ($type === 'aporte') {
                $sumByAcc[$aid]['aportes'] += $amt;
            } else {
                $sumByAcc[$aid]['despesas'] += $amt;
            }
        }

        $list = [];
        foreach (Accountability::orderBy('id', 'desc')->get()->toArray() as $acc) {
            $aid = (int) $acc['id'];
            if (($attCounts[$aid] ?? 0) <= 0) {
                continue;
            }
            $s      = $sumByAcc[$aid] ?? ['entries' => 0, 'receitas' => 0.0, 'aportes' => 0.0, 'despesas' => 0.0];
            $list[] = [
                'id'                => $aid,
                'title'             => (string) ($acc['title'] ?? ''),
                'attachments_count' => $attCounts[$aid],
                'entries_count'     => $s['entries'],
                'receitas'          => round($s['receitas'], 2),
                'aportes'           => round($s['aportes'], 2),
                'despesas'          => round($s['despesas'], 2),
                'saldo'             => round(($s['receitas'] + $s['aportes']) - $s['despesas'], 2),
            ];
        }

        return response()->json(['success' => true, 'data' => $list]);
    }

    public function auditHistory(Request $request)
    {
        $user = $request->user();

        $rows = [];
        try {
            $usersMap = [];
            foreach (User::select('id', 'name')->get() as $u) {
                $usersMap[(int) $u->id] = (string) $u->name;
            }

            foreach (AccountabilityHistory::orderBy('id', 'desc')->limit(40)->get() as $h) {
                $rows[] = [
                    'id'                => (int) $h->id,
                    'accountability_id' => (int) ($h->accountability_id ?? 0),
                    'user_name'         => $usersMap[(int) ($h->user_id ?? 0)] ?? 'Sistema',
                    'action'            => (string) ($h->status ?? ''),
                    'created_at'        => ! empty($h->created_at) ? date('d/m/Y H:i', strtotime((string) $h->created_at)) : '',
                ];
            }
        } catch (Throwable) {
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function usersList(Request $request)
    {
        $user = $request->user();

        $list = User::orderBy('id', 'asc')->get()->map(static fn ($u) => [
            'id'       => (int) $u->id,
            'name'     => (string) $u->name,
            'email'    => (string) $u->email,
            'whatsapp' => (string) ($u->whatsapp ?? ''),
            'role'     => (string) ($u->role ?? 'user'),
        ])->all();

        return response()->json(['success' => true, 'data' => $list]);
    }

    public function showUser(Request $request, int $id)
    {
        $auth = $request->user();

        $target = User::find($id);
        if (! $target) {
            return response()->json(['success' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $allowed  = UserAccountabilityPermission::getAllowedItemsForUser($id);
        $allItems = [];
        foreach (Accountability::getItems() as $num => $label) {
            $allItems[] = [
                'item_number' => (int) $num,
                'title'       => rtrim((string) $label, ';.'),
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => (int) $target->id,
                'name'          => (string) $target->name,
                'email'         => (string) $target->email,
                'whatsapp'      => (string) ($target->whatsapp ?? ''),
                'role'          => (string) ($target->role ?? 'user'),
                'allowed_items' => array_map('intval', $allowed),
                'all_items'     => $allItems,
            ],
        ]);
    }

    public function updateUser(Request $request, int $id)
    {
        $auth = $request->user();

        $target = User::find($id);
        if (! $target) {
            return response()->json(['success' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|min:3|max:100',
            'email'    => "required|email|max:100|unique:users,email,{$id}",
            'whatsapp' => 'nullable|max:30',
            'role'     => 'required|in:admin,user',
            'password' => 'nullable|min:6',
        ], [
            'name.required'  => 'O nome é obrigatório.',
            'name.min'       => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
            'email.unique'   => 'Este e-mail já está em uso por outro usuário.',
            'whatsapp.max'   => 'O WhatsApp deve ter no máximo 30 caracteres.',
            'role.required'  => 'Selecione o perfil do usuário.',
            'password.min'   => 'A nova senha deve ter pelo menos 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        try {
            $whatsapp = trim((string) $request->input('whatsapp', ''));
            $data     = [
                'name'     => trim((string) $request->input('name')),
                'email'    => trim((string) $request->input('email')),
                'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
                'role'     => (string) $request->input('role'),
            ];

            $newPassword = (string) $request->input('password', '');
            if ($newPassword !== '') {
                $data['password_hashed'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $target->update($data);

            $allowedItems = (array) ($request->input('allowed_items') ?? []);
            UserAccountabilityPermission::syncUserPermissions($id, $allowedItems);

            return response()->json([
                'success' => true,
                'message' => 'Usuário e permissões atualizados com sucesso!',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao atualizar usuário: ' . $e->getMessage()], 500);
        }
    }

    public function settingsSummary(Request $request)
    {
        $user = $request->user();

        $smtp = null;
        $wa   = null;
        try {
            $smtp = SmtpConfig::first();
        } catch (Throwable) {
        }
        try {
            $wa = WhatsappConfig::first();
        } catch (Throwable) {
        }

        $ai = new GeminiSummaryService();

        return response()->json([
            'success' => true,
            'data'    => [
                'smtp_active'     => $smtp !== null && (int) ($smtp->status ?? 0) === 1,
                'smtp_host'       => (string) ($smtp?->smtp_host ?? 'Não configurado'),
                'whatsapp_active' => $wa !== null && (int) ($wa->status ?? 0) === 1,
                'whatsapp_url'    => (string) ($wa?->api_url ?? 'http://localhost:2785'),
                'ai_provider'     => strtoupper($ai->getProvider()),
                'ai_model'        => $ai->getModel(),
                'ai_enabled'      => $ai->isEnabled(),
            ],
        ]);
    }

    public function notifications(Request $request)
    {
        $user = $request->user();

        try {
            $enabled = $user->notifications_enabled === null ? true : (bool) $user->notifications_enabled;
            $rows    = AdminNotification::where('user_id', (int) $user->id)
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get()
                ->map(static fn ($n) => [
                    'id'                => (int) $n->id,
                    'accountability_id' => $n->accountability_id ? (int) $n->accountability_id : null,
                    'actor_name'        => (string) ($n->actor_name ?? ''),
                    'title'             => (string) $n->title,
                    'message'           => (string) $n->message,
                    'is_read'           => (bool) $n->is_read,
                    'created_at'        => $n->created_at ? date('d/m/Y H:i', strtotime((string) $n->created_at)) : '',
                ])
                ->all();

            $unread = AdminNotification::where('user_id', (int) $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success'      => true,
                'enabled'      => $enabled,
                'unread_count' => $unread,
                'items'        => $rows,
            ]);
        } catch (Throwable) {
            return response()->json([
                'success'      => true,
                'enabled'      => true,
                'unread_count' => 0,
                'items'        => [],
            ]);
        }
    }

    public function toggleNotifications(Request $request)
    {
        $user = $request->user();

        $newValue = $request->has('enabled')
            ? (bool) $request->input('enabled')
            : ! ((bool) ($user->notifications_enabled ?? true));

        $user->update(['notifications_enabled' => $newValue]);

        return response()->json([
            'success' => true,
            'enabled' => $newValue,
            'message' => $newValue ? 'Notificações ativadas.' : 'Notificações pausadas.',
        ]);
    }

    public function markNotificationRead(Request $request, int $id)
    {
        $user = $request->user();

        AdminNotification::where('id', $id)->where('user_id', (int) $user->id)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $user = $request->user();

        AdminNotification::where('user_id', (int) $user->id)->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
