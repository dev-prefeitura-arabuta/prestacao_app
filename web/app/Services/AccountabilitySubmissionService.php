<?php

namespace App\Services;

use App\Exceptions\ActionException;
use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AdminNotification;
use App\Support\Actor;

/**
 * Envio dos documentos de uma prestação para análise do administrador (Web e API mobile).
 */
class AccountabilitySubmissionService
{
    /** Status que podem ser (re)enviados para análise. */
    private const SENDABLE_STATUSES = ['pendente', 'rejeitado', 'prazo_vencido'];

    /**
     * Prestação + itens habilitados nela em que o usuário pode atuar.
     *
     * @return array{accountability: Accountability, enabled: array<int, int>, allowed: array<int, int>, with_files: array<int, int>}
     *
     * @throws ActionException
     */
    public function context(Actor $actor, int $accountabilityId): array
    {
        if ($actor->isAdmin) {
            throw ActionException::forbidden('Administradores não utilizam o envio interno de prestação. Use o botão "Enviar por E-mail".');
        }

        $acc = Accountability::find($accountabilityId);
        if ($acc === null) {
            throw ActionException::notFound('Prestação de contas não encontrada.');
        }

        $enabled = array_map('intval', array_keys(Accountability::getEnabledItemsForAccountability($acc->toArray())));
        $allowed = array_values(array_intersect($actor->allowedItems(), $enabled));
        if (empty($allowed)) {
            throw ActionException::forbidden('Você não possui itens liberados nesta prestação.');
        }

        $withFiles = AccountabilityAttachment::where('accountability_id', $accountabilityId)
            ->whereIn('item_number', $allowed)
            ->pluck('item_number')
            ->map(static fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        return ['accountability' => $acc, 'enabled' => $enabled, 'allowed' => $allowed, 'with_files' => $withFiles];
    }

    /**
     * Marca os itens como 'enviado', registra histórico e notifica os administradores.
     *
     * @param array<int, int> $items
     * @return array{sent_count: int, all_filled: bool}
     */
    public function submit(Actor $actor, Accountability $acc, array $items, array $enabledItems, string $historySuffix = ''): array
    {
        $accId     = (int) $acc->id;
        $sentCount = 0;

        foreach ($items as $itemNum) {
            $existing = AccountabilityItemStatus::where('accountability_id', $accId)
                ->where('item_number', $itemNum)
                ->first();

            if ($existing === null || in_array($existing->status, self::SENDABLE_STATUSES, true)) {
                AccountabilityItemStatus::setStatus($accId, (int) $itemNum, 'enviado', $actor->id);
                $sentCount++;
            }
        }

        AccountabilityHistory::log($accId, $actor->id, "Enviou documentos da prestação de contas ({$sentCount} item(ns)) para análise{$historySuffix}");

        // Todos os itens habilitados da prestação (de todos os usuários) já têm anexos?
        $itemsWithAnyFile = AccountabilityAttachment::where('accountability_id', $accId)
            ->pluck('item_number')
            ->map(static fn ($v) => (int) $v)
            ->unique()
            ->all();
        $allFilled = count(array_diff($enabledItems, $itemsWithAnyFile)) === 0;

        $title = (string) ($acc->title ?: "Prestação #{$accId}");
        AdminNotification::notifyAdmins(
            $allFilled ? 'Todos os anexos enviados!' : 'Envio de anexos concluído',
            $allFilled
                ? "Todos os anexos foram enviados na prestação \"{$title}\" (último envio por {$actor->name})."
                : "{$actor->name} enviou {$sentCount} item(ns) na prestação \"{$title}\" para análise.",
            $accId,
            $actor->name
        );

        return ['sent_count' => $sentCount, 'all_filled' => $allFilled];
    }
}
