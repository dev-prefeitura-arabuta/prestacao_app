<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AnnualHistoryController;
use App\Http\Controllers\Controller;
use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Models\GeneralSettings;
use App\Models\PasswordResetToken;
use App\Models\SmtpConfig;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Models\WhatsappConfig;
use App\Services\GeminiSummaryService;
use App\Services\OpenWaService;
use App\Services\SmtpMailService;
use App\Services\SpreadsheetAiExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MobileApiController extends Controller
{
    private array $allowedExtensions = ['zip', 'jpg', 'jpeg', 'png', 'webp', 'pdf'];
    private array $allowedAvatarExtensions = ['jpg', 'jpeg', 'png', 'webp'];

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

    public function login(Request $request)
    {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return response()->json(['success' => false, 'error' => 'Informe e-mail e senha.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (! $user || ! password_verify($password, (string) $user->password_hashed)) {
            return response()->json(['success' => false, 'error' => 'E-mail ou senha inválidos.'], 401);
        }

        $token = bin2hex(random_bytes(32));
        Cache::put('mobile_token_' . hash('sha256', $token), (int) $user->id, now()->addDays(7));

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'error' => 'Informe um e-mail válido.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não encontramos nenhuma conta cadastrada com este e-mail.'], 404);
        }

        try {
            $code      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addSeconds(90);

            PasswordResetToken::where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()->format('Y-m-d H:i:s')]);

            PasswordResetToken::create([
                'user_id'    => (int) $user->id,
                'email'      => $user->email,
                'token_hash' => hash('sha256', $code),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'used_at'    => null,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]);

            $genSettings = GeneralSettings::getSettings();
            $appName     = $genSettings['app_name'] ?? 'Sistema de Prestação de Contas';

            $htmlBody = '<div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;padding:20px;border:1px solid #e2e8f0;border-radius:10px;">'
                . '<h2 style="margin:0 0 12px;color:#072c5e;">' . e($appName) . '</h2>'
                . '<p style="color:#334155;">Olá, <strong>' . e($user->name) . '</strong>! Use o código de 6 dígitos abaixo para redefinir sua senha:</p>'
                . '<div style="text-align:center;margin:20px 0;">'
                . '<span style="display:inline-block;font-size:30px;font-weight:800;letter-spacing:8px;color:#dd4814;background:#fff7ed;border:1px solid #fed7aa;padding:12px 24px;border-radius:8px;">'
                . $code . '</span></div>'
                . '<p style="font-size:12px;color:#64748b;">Código válido por 1 minuto e 30 segundos.</p></div>';

            $mailer = new SmtpMailService();
            $sent   = $mailer->send(
                $user->email,
                (string) $user->name,
                "Código de Recuperação de Senha ({$code}) - {$appName}",
                $htmlBody,
                "Olá {$user->name}, seu código de 6 dígitos para redefinir a senha é: {$code} (válido por 1 minuto e 30 segundos)."
            );

            if (! $sent['success']) {
                return response()->json(['success' => false, 'error' => $sent['error'] ?: 'Falha ao enviar e-mail via SMTP.'], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Código de 6 dígitos enviado para ' . $user->email . '!',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao enviar código: ' . $e->getMessage()], 500);
        }
    }

    public function verifyResetCode(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $code  = preg_replace('/\D/', '', (string) $request->input('code', '')) ?? '';

        if ($email === '' || strlen($code) !== 6) {
            return response()->json(['success' => false, 'error' => 'Informe o e-mail e o código de 6 dígitos.'], 422);
        }

        $record = PasswordResetToken::where('email', $email)
            ->where('token_hash', hash('sha256', $code))
            ->whereNull('used_at')
            ->orderBy('id', 'desc')
            ->first();

        if (! $record) {
            return response()->json(['success' => false, 'error' => 'Código inválido. Verifique os 6 dígitos enviados ao seu e-mail.'], 422);
        }

        if (strtotime((string) $record->expires_at) < time()) {
            return response()->json(['success' => false, 'error' => 'Código expirado. Solicite um novo código.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Código validado! Agora defina sua nova senha.']);
    }

    public function resetPassword(Request $request)
    {
        $email    = strtolower(trim((string) $request->input('email', '')));
        $code     = preg_replace('/\D/', '', (string) $request->input('code', '')) ?? '';
        $password = (string) $request->input('password', '');
        $confirm  = (string) $request->input('password_confirmation', '');

        if ($email === '' || strlen($code) !== 6) {
            return response()->json(['success' => false, 'error' => 'E-mail e código de 6 dígitos são obrigatórios.'], 422);
        }
        if (strlen($password) < 6) {
            return response()->json(['success' => false, 'error' => 'A nova senha deve ter pelo menos 6 caracteres.'], 422);
        }
        if ($password !== $confirm) {
            return response()->json(['success' => false, 'error' => 'A confirmação de senha não confere.'], 422);
        }

        $record = PasswordResetToken::where('email', $email)
            ->where('token_hash', hash('sha256', $code))
            ->whereNull('used_at')
            ->orderBy('id', 'desc')
            ->first();

        if (! $record) {
            return response()->json(['success' => false, 'error' => 'Código inválido ou já utilizado.'], 422);
        }

        $user = User::find((int) $record->user_id);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $user->update(['password_hashed' => password_hash($password, PASSWORD_DEFAULT)]);
        $record->update(['used_at' => now()->format('Y-m-d H:i:s')]);

        return response()->json(['success' => true, 'message' => 'Senha redefinida com sucesso! Faça login com sua nova senha.']);
    }

    public function logout(Request $request)
    {
        $token = $this->extractBearerToken($request);
        if ($token !== '') {
            Cache::forget('mobile_token_' . hash('sha256', $token));
        }

        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        $userId = (int) $user->id;
        $validator = Validator::make($request->all(), [
            'name'             => 'required|min:3|max:100',
            'email'            => "required|email|max:100|unique:users,email,{$userId}",
            'whatsapp'         => 'nullable|max:30',
            'new_password'     => 'nullable|min:6',
            'confirm_password' => 'same:new_password',
        ], [
            'name.required'         => 'O nome é obrigatório.',
            'name.min'              => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required'        => 'O e-mail é obrigatório.',
            'email.email'           => 'Informe um e-mail válido.',
            'email.unique'          => 'Este e-mail já está sendo utilizado por outro usuário.',
            'whatsapp.max'          => 'O número de WhatsApp deve ter no máximo 30 caracteres.',
            'new_password.min'      => 'A nova senha deve ter pelo menos 6 caracteres.',
            'confirm_password.same' => 'A confirmação de senha não confere com a nova senha.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => $validator->errors()->first(),
            ], 422);
        }

        try {
            $name        = trim((string) $request->input('name'));
            $email       = trim((string) $request->input('email'));
            $whatsapp    = trim((string) $request->input('whatsapp', ''));
            $newPassword = (string) $request->input('new_password', '');
            $avatarPath  = $user->avatar;

            if ($request->hasFile('avatar')) {
                $avatarFile = $request->file('avatar');
                if (! $avatarFile->isValid()) {
                    $errCode = $avatarFile->getError();
                    if (in_array($errCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                        return response()->json(['success' => false, 'error' => 'A foto excede o limite de tamanho permitido pelo servidor.'], 413);
                    }
                    return response()->json(['success' => false, 'error' => 'Não foi possível processar a imagem enviada.'], 422);
                }

                $ext = strtolower((string) $avatarFile->getClientOriginalExtension());
                if ($ext === '' || ! in_array($ext, $this->allowedAvatarExtensions, true)) {
                    $mime = strtolower((string) $avatarFile->getMimeType());
                    $ext  = match ($mime) {
                        'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
                        'image/png'                              => 'png',
                        'image/webp'                             => 'webp',
                        default                                  => $ext,
                    };
                }

                if (! in_array($ext, $this->allowedAvatarExtensions, true)) {
                    return response()->json(['success' => false, 'error' => 'Formato de imagem inválido. Utilize JPG, JPEG, PNG ou WEBP.'], 422);
                }

                if ($avatarFile->getSize() > 5 * 1024 * 1024) {
                    return response()->json(['success' => false, 'error' => 'A foto de perfil deve ter no máximo 5 MB.'], 422);
                }

                $targetDir = storage_path('app/uploads/avatars');
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (! empty($avatarPath)) {
                    $oldFullPath = storage_path('app/' . $avatarPath);
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'user_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $avatarFile->move($targetDir, $newName);
                $avatarPath = 'uploads/avatars/' . $newName;
            }

            $data = [
                'name'     => $name,
                'email'    => $email,
                'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
                'avatar'   => $avatarPath,
            ];

            if ($newPassword !== '') {
                $data['password_hashed'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $user->update($data);
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'user'    => $this->formatUser($user),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao atualizar perfil: ' . $e->getMessage()], 500);
        }
    }

    public function removeAvatar(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        try {
            if (! empty($user->avatar)) {
                $fullPath = storage_path('app/' . $user->avatar);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $user->update(['avatar' => null]);
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil removida com sucesso!',
                'user'    => $this->formatUser($user),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao remover foto: ' . $e->getMessage()], 500);
        }
    }

    public function avatar(int $id)
    {
        $user = User::find($id);
        if (! $user || empty($user->avatar)) {
            abort(404);
        }

        $fullPath = storage_path('app/' . $user->avatar);
        if (! is_file($fullPath)) {
            abort(404);
        }

        $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };

        return response()->file($fullPath, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
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

    public function accountabilities(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        $isAdmin      = ($user->role === 'admin');
        $allowedItems = $isAdmin
            ? array_keys(Accountability::getItems())
            : UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id);

        $rawList     = Accountability::orderBy('id', 'desc')->get()->toArray();
        $allAtts     = AccountabilityAttachment::select('accountability_id', 'item_number')->get()->toArray();
        $statusRows  = AccountabilityItemStatus::all()->toArray();
        $statusByAcc = [];
        foreach ($statusRows as $sr) {
            $statusByAcc[(int) $sr['accountability_id']][(int) $sr['item_number']] = (string) $sr['status'];
        }

        $attsByAccItem = [];
        $attCounts     = [];
        foreach ($allAtts as $a) {
            $aid = (int) $a['accountability_id'];
            $in  = (int) $a['item_number'];
            if ($isAdmin || in_array($in, $allowedItems, true)) {
                $attCounts[$aid]          = ($attCounts[$aid] ?? 0) + 1;
                $attsByAccItem[$aid][$in] = ($attsByAccItem[$aid][$in] ?? 0) + 1;
            }
        }

        $items = [];
        foreach ($rawList as $acc) {
            $aid = (int) $acc['id'];
            if (Accountability::isFinalized($acc, $statusByAcc[$aid] ?? [])) {
                continue;
            }
            if (! $isAdmin && ! empty($acc['is_paused'])) {
                continue;
            }

            $enabled = array_keys(Accountability::getEnabledItemsForAccountability($acc));
            $visible = $isAdmin ? $enabled : array_values(array_intersect($allowedItems, $enabled));
            if (empty($visible)) {
                continue;
            }

            // Para usuários não-admin: oculta prestações cujo envio já foi confirmado (sem itens rejeitados ou novos anexos pendentes)
            if (! $isAdmin) {
                $hasSentAny     = false;
                $hasRejectedAny = false;
                $hasUnsentFiles = false;

                foreach ($visible as $itemNum) {
                    $st       = $statusByAcc[$aid][$itemNum] ?? 'pendente';
                    $hasFiles = ! empty($attsByAccItem[$aid][$itemNum]);

                    if (in_array($st, ['enviado', 'confirmado'], true)) {
                        $hasSentAny = true;
                    } elseif ($st === 'rejeitado') {
                        $hasRejectedAny = true;
                    } elseif ($hasFiles && in_array($st, ['pendente', 'prazo_vencido'], true)) {
                        $hasUnsentFiles = true;
                    }
                }

                if ($hasSentAny && ! $hasRejectedAny && ! $hasUnsentFiles) {
                    continue;
                }
            }

            $filled = 0;
            foreach ($visible as $n) {
                if (! empty($attsByAccItem[$aid][$n])) {
                    $filled++;
                }
            }

            $items[] = [
                'id'                => $aid,
                'title'             => (string) ($acc['title'] ?? ''),
                'description'       => (string) ($acc['description'] ?? ''),
                'deadline'          => ! empty($acc['deadline']) ? date('d/m/Y', strtotime((string) $acc['deadline'])) : 'Não definido',
                'is_paused'         => ! empty($acc['is_paused']),
                'attachments_count' => $attCounts[$aid] ?? 0,
                'filled_items'      => $filled,
                'total_items'       => count($visible),
                'progress'          => (int) round(($filled / max(1, count($visible))) * 100),
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    public function createAccountability(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Apenas administradores podem criar novas prestações de contas.'], 403);
        }

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

            try {
                AccountabilityHistory::create([
                    'accountability_id' => (int) $acc->id,
                    'user_id'           => (int) $user->id,
                    'user_name'         => (string) $user->name,
                    'action'            => 'Prestação de contas criada via Mobile com ' . count($enabledItems) . ' item(ns) habilitado(s)',
                    'created_at'        => now()->format('Y-m-d H:i:s'),
                ]);
            } catch (Throwable) {
            }

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
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

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

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => (int) $acc['id'],
                'title'            => (string) ($acc['title'] ?? ''),
                'description'      => (string) ($acc['description'] ?? ''),
                'deadline'         => ! empty($acc['deadline']) ? date('d/m/Y', strtotime((string) $acc['deadline'])) : 'Não definido',
                'all_items_filled' => $allItemsFilled && ! empty($checklist),
                'can_submit'       => ! $isAdmin && $allItemsFilled && ! empty($checklist) && $hasSubmittableItem,
                'items'            => $checklist,
            ],
        ]);
    }

    public function submitAccountability(Request $request, int $id)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }
        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'error' => 'Administradores não utilizam o envio interno de prestação.'], 403);
        }

        $accObj = Accountability::find($id);
        if (! $accObj) {
            return response()->json(['success' => false, 'error' => 'Prestação não encontrada.'], 404);
        }

        $acc          = $accObj->toArray();
        $enabledItems = array_keys(Accountability::getEnabledItemsForAccountability($acc));
        $allowedItems = array_values(array_intersect(
            UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id),
            $enabledItems
        ));

        if (empty($allowedItems)) {
            return response()->json(['success' => false, 'error' => 'Você não possui itens liberados nesta prestação.'], 403);
        }

        $attsByItem = [];
        foreach (AccountabilityAttachment::where('accountability_id', $id)->whereIn('item_number', $allowedItems)->get() as $att) {
            $attsByItem[(int) $att->item_number] = true;
        }

        foreach ($allowedItems as $itemNum) {
            if (empty($attsByItem[$itemNum])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Adicione pelo menos um anexo em todos os seus itens antes de confirmar o envio.',
                ], 422);
            }
        }

        try {
            $sentCount = 0;
            foreach ($allowedItems as $itemNum) {
                $existing = AccountabilityItemStatus::where('accountability_id', $id)
                    ->where('item_number', $itemNum)
                    ->first();

                if ($existing === null || in_array($existing->status, ['pendente', 'rejeitado', 'prazo_vencido'], true)) {
                    AccountabilityItemStatus::setStatus($id, $itemNum, 'enviado', (int) $user->id);
                    $sentCount++;
                }
            }

            try {
                AccountabilityHistory::create([
                    'accountability_id' => $id,
                    'user_id'           => (int) $user->id,
                    'user_name'         => (string) $user->name,
                    'action'            => "Enviou documentos da prestação de contas ({$sentCount} item(ns)) para análise via Mobile",
                    'created_at'        => now()->format('Y-m-d H:i:s'),
                ]);
            } catch (Throwable) {
            }

            return response()->json([
                'success' => true,
                'message' => 'Envio confirmado! Sua prestação de contas foi encaminhada para avaliação do Administrador.',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao confirmar envio: ' . $e->getMessage()], 500);
        }
    }

    public function remindWhatsapp(Request $request, int $id)
    {
        $admin = $this->authenticate($request);
        if (! $admin || $admin->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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

        try {
            $itemInfo = $itemNumber > 0 ? " (Item {$itemNumber})" : '';
            AccountabilityHistory::create([
                'accountability_id' => $id,
                'user_id'           => (int) $admin->id,
                'user_name'         => (string) $admin->name,
                'action'            => "Enviou cobrança/lembrete via WhatsApp para {$targetUser->name}{$itemInfo}",
                'created_at'        => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }

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

    public function uploadAttachment(Request $request, int $id, int $itemNumber)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        if ($user->role !== 'admin') {
            $allowed = UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id);
            if (! in_array($itemNumber, $allowed, true)) {
                return response()->json(['success' => false, 'error' => 'Você não tem permissão para anexar arquivos neste item.'], 403);
            }
            $st = AccountabilityItemStatus::where('accountability_id', $id)
                ->where('item_number', $itemNumber)
                ->first();
            if ($st && $st->status === 'confirmado') {
                return response()->json(['success' => false, 'error' => 'Este item já foi confirmado pelo administrador.'], 403);
            }
        }

        $file = $request->file('file');
        if (! $file) {
            return response()->json(['success' => false, 'error' => 'Nenhum arquivo recebido. Se a foto for muito grande, tente novamente com menor resolução.'], 422);
        }
        if (! $file->isValid()) {
            $errCode = $file->getError();
            if (in_array($errCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return response()->json(['success' => false, 'error' => 'O arquivo excede o tamanho máximo permitido pelo servidor.'], 413);
            }
            return response()->json(['success' => false, 'error' => 'Arquivo inválido ou corrompido durante o envio.'], 422);
        }

        if ($file->getSize() > 15 * 1024 * 1024) {
            return response()->json(['success' => false, 'error' => 'O arquivo deve ter no máximo 15 MB.'], 413);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '' || ! in_array($ext, $this->allowedExtensions, true)) {
            $mime = strtolower((string) $file->getMimeType());
            $ext  = match ($mime) {
                'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
                'image/png'                              => 'png',
                'image/webp'                             => 'webp',
                'application/pdf'                        => 'pdf',
                'application/zip', 'application/x-zip-compressed' => 'zip',
                default                                  => $ext,
            };
        }

        if (! in_array($ext, $this->allowedExtensions, true)) {
            return response()->json(['success' => false, 'error' => 'Formato inválido. Use PDF, JPG, PNG, WEBP ou ZIP.'], 422);
        }

        try {
            $targetDir = storage_path('app/uploads/accountability/' . $id . '/item_' . $itemNumber);
            if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
                return response()->json(['success' => false, 'error' => 'Não foi possível criar o diretório de uploads no servidor.'], 500);
            }

            $origName = $file->getClientOriginalName() ?: ('anexo_' . time() . '.' . $ext);
            $size     = (int) $file->getSize();
            $newName  = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($targetDir, $newName);

            $attModel = AccountabilityAttachment::create([
                'accountability_id' => $id,
                'item_number'       => $itemNumber,
                'user_id'           => (int) $user->id,
                'original_name'     => $origName,
                'file_name'         => $newName,
                'file_path'         => 'uploads/accountability/' . $id . '/item_' . $itemNumber . '/' . $newName,
                'file_ext'          => $ext,
                'file_size'         => $size,
                'created_at'        => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao salvar anexo no servidor: ' . $e->getMessage()], 500);
        }

        // Extração automática para a planilha via IA/OCR
        try {
            $accRow    = Accountability::find($id);
            $extractor = new SpreadsheetAiExtractorService();
            $extRes    = $extractor->extractEntries(
                $accRow ? $accRow->toArray() : ['id' => $id, 'title' => "Prestação #{$id}"],
                [$attModel->toArray()]
            );
            if (! empty($extRes['success']) && ! empty($extRes['entries'])) {
                foreach ($extRes['entries'] as $entry) {
                    AccountabilitySpreadsheetEntry::create([
                        'accountability_id' => $id,
                        'item_number'       => $entry['item_number'] ?: $itemNumber,
                        'attachment_id'     => (int) $attModel->id,
                        'entry_date'        => $entry['entry_date'],
                        'document_number'   => $entry['document_number'],
                        'supplier_name'     => $entry['supplier_name'],
                        'cnpj_cpf'          => $entry['cnpj_cpf'],
                        'description'       => $entry['description'],
                        'entry_type'        => $entry['entry_type'],
                        'amount'            => $entry['amount'],
                        'notes'             => $entry['notes'],
                        'created_by'        => (int) $user->id,
                    ]);
                }
            }
        } catch (Throwable) {
        }

        return response()->json([
            'success'    => true,
            'attachment' => [
                'id'            => (int) $attModel->id,
                'original_name' => $origName,
                'file_ext'      => $ext,
                'file_size'     => $size,
            ],
        ]);
    }

    public function previewAttachment(Request $request, int $attachmentId)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        $att = AccountabilityAttachment::find($attachmentId);
        if (! $att) {
            return response()->json(['success' => false, 'error' => 'Anexo não encontrado.'], 404);
        }

        if ($user->role !== 'admin') {
            $allowed = UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id);
            if (! in_array((int) $att->item_number, $allowed, true)) {
                return response()->json(['success' => false, 'error' => 'Sem permissão para visualizar este anexo.'], 403);
            }
        }

        $fullPath = storage_path('app/' . $att->file_path);
        if (! is_file($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Arquivo físico não encontrado.'], 404);
        }

        $ext  = strtolower((string) $att->file_ext);
        $mime = match ($ext) {
            'pdf'        => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'webp'       => 'image/webp',
            'zip'        => 'application/zip',
            default      => 'application/octet-stream',
        };

        $origName  = (string) ($att->original_name ?: ('anexo.' . $ext));
        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName) ?: ('anexo.' . $ext);

        return response()->file($fullPath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($origName),
            'Cache-Control'       => 'no-cache, must-revalidate',
        ]);
    }

    public function deleteAttachment(Request $request, int $attachmentId)
    {
        $user = $this->authenticate($request);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        $att = AccountabilityAttachment::find($attachmentId);
        if (! $att) {
            return response()->json(['success' => false, 'error' => 'Anexo não encontrado.'], 404);
        }

        $accId   = (int) $att->accountability_id;
        $itemNum = (int) $att->item_number;

        if ($user->role !== 'admin') {
            $allowed = UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id);
            if (! in_array($itemNum, $allowed, true)) {
                return response()->json(['success' => false, 'error' => 'Sem permissão para remover este anexo.'], 403);
            }
            $st = AccountabilityItemStatus::where('accountability_id', $accId)
                ->where('item_number', $itemNum)
                ->first();
            if ($st && $st->status === 'confirmado') {
                return response()->json(['success' => false, 'error' => 'Este item já foi confirmado pelo administrador.'], 403);
            }
        }

        try {
            $fullPath = storage_path('app/' . $att->file_path);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }

            try {
                AccountabilitySpreadsheetEntry::where('attachment_id', $attachmentId)->delete();
            } catch (Throwable) {
            }

            $att->delete();

            $remaining = AccountabilityAttachment::where('accountability_id', $accId)
                ->where('item_number', $itemNum)
                ->count();
            if ($remaining === 0) {
                AccountabilityItemStatus::setStatus($accId, $itemNum, 'pendente', (int) $user->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Anexo removido com sucesso!',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao remover anexo: ' . $e->getMessage()], 500);
        }
    }

    public function annualHistory(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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
        $user = $this->authenticate($request);
        if (! $user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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
        $user = $this->authenticate($request);
        if (! $user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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
                    'action'            => (string) ($h->action ?? $h->description ?? ''),
                    'created_at'        => ! empty($h->created_at) ? date('d/m/Y H:i', strtotime((string) $h->created_at)) : '',
                ];
            }
        } catch (Throwable) {
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function usersList(Request $request)
    {
        $user = $this->authenticate($request);
        if (! $user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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
        $auth = $this->authenticate($request);
        if (! $auth || $auth->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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
        $auth = $this->authenticate($request);
        if (! $auth || $auth->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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
        $user = $this->authenticate($request);
        if (! $user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

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

    private function extractBearerToken(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            return trim($m[1]);
        }
        return trim((string) $request->query('token', ''));
    }

    private function authenticate(Request $request): ?User
    {
        $token = $this->extractBearerToken($request);
        if ($token === '') {
            return null;
        }
        $userId = Cache::get('mobile_token_' . hash('sha256', $token));
        if (! $userId) {
            return null;
        }
        return User::find((int) $userId);
    }
}
