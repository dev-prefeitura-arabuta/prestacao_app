<?php

namespace App\Services;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Avisa quem enviou os documentos de um item que ele foi rejeitado, com o motivo.
 * Usa e-mail (SMTP) e WhatsApp (OpenWA) quando estiverem ativos; falhas nunca interrompem a avaliação.
 */
class RejectionNotifier
{
    public function __construct(
        private readonly SmtpMailService $mailer,
        private readonly OpenWaService $whatsapp,
    ) {
    }

    /**
     * @return int Quantidade de pessoas avisadas por pelo menos um canal
     */
    public function notify(Accountability $acc, int $itemNumber, string $reason): int
    {
        $uploaderIds = AccountabilityAttachment::where('accountability_id', (int) $acc->id)
            ->where('item_number', $itemNumber)
            ->distinct()
            ->pluck('user_id');

        $users = User::whereIn('id', $uploaderIds)->where('role', '!=', 'admin')->get();

        $itemLabel = rtrim((string) (Accountability::getItems()[$itemNumber] ?? "Item {$itemNumber}"), ';.');
        $numPad    = str_pad((string) $itemNumber, 2, '0', STR_PAD_LEFT);
        $title     = (string) ($acc->title ?: "Prestação #{$acc->id}");

        $notified = 0;
        foreach ($users as $user) {
            $text = "Olá, {$user->name}!\n\n"
                . "O Item {$numPad} ({$itemLabel}) da prestação de contas \"{$title}\" foi rejeitado.\n\n"
                . "Motivo: {$reason}\n\n"
                . 'Acesse o sistema para corrigir e reenviar o documento.';

            $sent = false;

            if (! empty($user->email)) {
                try {
                    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1e293b;line-height:1.6;">'
                        . nl2br(e($text)) . '</div>';
                    $res  = $this->mailer->send((string) $user->email, (string) $user->name, "Item {$numPad} rejeitado — {$title}", $html, $text);
                    $sent = $sent || ! empty($res['success']);
                    if (empty($res['success'])) {
                        Log::warning('Aviso de rejeição não enviado por e-mail', ['user_id' => (int) $user->id, 'erro' => $res['error'] ?? null]);
                    }
                } catch (Throwable $e) {
                    report($e);
                }
            }

            $digits = Phone::toInternational($user->whatsapp);
            if ($digits !== null) {
                try {
                    $res  = $this->whatsapp->sendText($digits, $text);
                    $sent = $sent || ! empty($res['success']);
                    if (empty($res['success'])) {
                        Log::warning('Aviso de rejeição não enviado por WhatsApp', ['user_id' => (int) $user->id, 'erro' => $res['error'] ?? null]);
                    }
                } catch (Throwable $e) {
                    report($e);
                }
            }

            if ($sent) {
                $notified++;
            }
        }

        return $notified;
    }
}
