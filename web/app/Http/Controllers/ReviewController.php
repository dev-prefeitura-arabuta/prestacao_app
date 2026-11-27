<?php

namespace App\Http\Controllers;

use App\Services\ItemReviewService;
use App\Services\PendingByUserService;
use App\Support\Phone;

/**
 * Telas de apoio ao administrador: fila "Para avaliar" e painel de pendências por pessoa.
 */
class ReviewController extends Controller
{
    public function queue(ItemReviewService $review)
    {
        return view('review.queue', $this->layoutData() + [
            'queue' => $review->reviewQueue(),
        ]);
    }

    public function pendingByUser(PendingByUserService $pending)
    {
        $rows = $pending->build();

        // Link de cobrança no WhatsApp (abre o app/WhatsApp Web com a mensagem pronta)
        foreach ($rows as &$row) {
            $row['wa_url'] = null;
            $digits        = Phone::toInternational($row['user']['whatsapp']);
            if ($digits !== null) {
                $row['wa_url'] = 'https://wa.me/' . $digits . '?text=' . rawurlencode(self::reminderText($row));
            }
        }
        unset($row);

        return view('review.pending_by_user', $this->layoutData() + [
            'rows' => $rows,
        ]);
    }

    /** Mensagem de cobrança agrupando todas as pendências da pessoa. */
    public static function reminderText(array $row): string
    {
        $lines = [];
        foreach ($row['items'] as $it) {
            $lines[] = '• ' . $it['accountability_title'] . ' — Item ' . str_pad((string) $it['item_number'], 2, '0', STR_PAD_LEFT)
                . ($it['status'] === 'rejeitado' ? ' (rejeitado, precisa corrigir)' : '')
                . ($it['overdue'] ? ' (prazo vencido)' : '');
        }

        return "Olá, {$row['user']['name']}! 👋\n\n"
            . "Você possui itens pendentes nas prestações de contas:\n"
            . implode("\n", $lines)
            . "\n\nPor favor, acesse o sistema para anexar os documentos e confirmar o envio. Obrigado!";
    }

    private function layoutData(): array
    {
        return [
            'userName'  => session('user_name'),
            'username'  => session('username'),
            'userEmail' => session('user_email'),
            'userRole'  => session('user_role') ?? 'admin',
        ];
    }
}
