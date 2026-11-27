<?php

namespace App\Services;

use App\Exceptions\ActionException;
use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Models\User;
use App\Support\Actor;

/**
 * Avaliação dos documentos pelo administrador (Web e API mobile):
 * confirmar/rejeitar itens, confirmar em lote e a fila "Para avaliar".
 */
class ItemReviewService
{
    /** Motivos prontos oferecidos ao rejeitar (o texto pode ser editado antes de enviar). */
    public const REJECTION_PRESETS = [
        'Documento ilegível ou com baixa qualidade.',
        'Falta assinatura ou carimbo.',
        'Documento de outro período.',
        'O arquivo não corresponde a este item.',
        'Documento incompleto (faltam páginas).',
        'Valores divergentes do informado.',
    ];

    public function __construct(private readonly RejectionNotifier $notifier)
    {
    }

    /**
     * Altera a situação de um item.
     *
     * @return array{status: string, label: string, rejection_reason: string, notified: int}
     *
     * @throws ActionException
     */
    public function setStatus(Actor $actor, int $accountabilityId, int $itemNumber, string $status, ?string $reason = null): array
    {
        if (! $actor->isAdmin) {
            throw ActionException::forbidden('Apenas administradores podem alterar a situação do documento.');
        }

        $acc = Accountability::find($accountabilityId);
        if ($acc === null) {
            throw ActionException::notFound('Prestação de contas não encontrada.');
        }

        $enabled = Accountability::getEnabledItemsForAccountability($acc->toArray());
        if (! isset($enabled[$itemNumber])) {
            throw new ActionException('Item inválido para esta prestação de contas.');
        }

        $validStatuses = AccountabilityItemStatus::getValidStatuses();
        if (! isset($validStatuses[$status])) {
            throw new ActionException('Status inválido.');
        }

        $reason = trim((string) $reason);

        if (in_array($status, ['rejeitado', 'confirmado'], true) && $this->attachmentCount($accountabilityId, $itemNumber) === 0) {
            throw new ActionException('Não é possível rejeitar ou confirmar um item que ainda não possui documentos anexados.');
        }

        if ($status === 'rejeitado' && $reason === '') {
            throw new ActionException('Por favor, informe o motivo da rejeição para que o usuário saiba o que corrigir.');
        }

        AccountabilityItemStatus::setStatus(
            $accountabilityId,
            $itemNumber,
            $status,
            $actor->id,
            $status === 'rejeitado' ? $reason : null
        );

        // Arquivo/item rejeitado não deve constar na planilha
        if ($status === 'rejeitado') {
            AccountabilitySpreadsheetEntry::where('accountability_id', $accountabilityId)
                ->where('item_number', $itemNumber)
                ->delete();
        }

        $label = $validStatuses[$status];
        AccountabilityHistory::log(
            $accountabilityId,
            $actor->id,
            $status === 'rejeitado'
                ? "Rejeitou o Item {$itemNumber} — Motivo: {$reason}"
                : "Alterou situação do Item {$itemNumber} para '{$label}'"
        );

        $notified = $status === 'rejeitado' ? $this->notifier->notify($acc, $itemNumber, $reason) : 0;

        return [
            'status'           => $status,
            'label'            => $label,
            'rejection_reason' => $status === 'rejeitado' ? $reason : '',
            'notified'         => $notified,
        ];
    }

    /**
     * Confirma de uma vez todos os itens com situação "Enviado" (e com anexos) da prestação.
     *
     * @return int Quantidade de itens confirmados
     *
     * @throws ActionException
     */
    public function confirmAllSent(Actor $actor, int $accountabilityId): int
    {
        if (! $actor->isAdmin) {
            throw ActionException::forbidden('Apenas administradores podem confirmar documentos.');
        }

        $acc = Accountability::find($accountabilityId);
        if ($acc === null) {
            throw ActionException::notFound('Prestação de contas não encontrada.');
        }

        $enabled = Accountability::getEnabledItemsForAccountability($acc->toArray());
        $sent    = AccountabilityItemStatus::where('accountability_id', $accountabilityId)
            ->where('status', 'enviado')
            ->pluck('item_number')
            ->map(fn ($n) => (int) $n)
            ->filter(fn ($n) => isset($enabled[$n]) && $this->attachmentCount($accountabilityId, $n) > 0)
            ->values();

        foreach ($sent as $itemNumber) {
            AccountabilityItemStatus::setStatus($accountabilityId, $itemNumber, 'confirmado', $actor->id);
        }

        if ($sent->isNotEmpty()) {
            AccountabilityHistory::log(
                $accountabilityId,
                $actor->id,
                'Confirmou em lote ' . $sent->count() . ' item(ns) enviado(s): ' . $sent->implode(', ')
            );
        }

        return $sent->count();
    }

    /**
     * Itens enviados aguardando avaliação, de todas as prestações (mais antigos primeiro).
     *
     * @return array<int, array<string, mixed>>
     */
    public function reviewQueue(): array
    {
        $rows = AccountabilityItemStatus::where('status', 'enviado')
            ->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $accs      = Accountability::whereIn('id', $rows->pluck('accountability_id')->unique())->get()->keyBy('id');
        $attsAll   = AccountabilityAttachment::whereIn('accountability_id', $accs->keys())->get();
        $userNames = User::pluck('name', 'id');
        $allItems  = Accountability::getItems();

        $queue = [];
        foreach ($rows as $row) {
            $acc = $accs->get((int) $row->accountability_id);
            if ($acc === null) {
                continue;
            }

            $itemNumber = (int) $row->item_number;
            $enabled    = Accountability::getEnabledItemsForAccountability($acc->toArray());
            if (! isset($enabled[$itemNumber])) {
                continue;
            }

            $atts = $attsAll->where('accountability_id', (int) $acc->id)->where('item_number', $itemNumber);
            if ($atts->isEmpty()) {
                continue;
            }

            $queue[] = [
                'accountability_id'    => (int) $acc->id,
                'accountability_title' => (string) $acc->title,
                'is_paused'            => ! empty($acc->is_paused),
                'deadline'             => $acc->deadline ? date('d/m/Y', strtotime((string) $acc->deadline)) : null,
                'item_number'          => $itemNumber,
                'item_title'           => rtrim((string) ($allItems[$itemNumber] ?? "Item {$itemNumber}"), ';.'),
                'attachments_count'    => $atts->count(),
                'uploaders'            => $atts->pluck('user_id')->unique()
                    ->map(fn ($id) => (string) ($userNames[$id] ?? "Usuário #{$id}"))
                    ->values()
                    ->all(),
                'sent_at'              => $row->updated_at ? date('d/m/Y H:i', strtotime((string) $row->updated_at)) : null,
            ];
        }

        return $queue;
    }

    private function attachmentCount(int $accountabilityId, int $itemNumber): int
    {
        return AccountabilityAttachment::where('accountability_id', $accountabilityId)
            ->where('item_number', $itemNumber)
            ->count();
    }
}
