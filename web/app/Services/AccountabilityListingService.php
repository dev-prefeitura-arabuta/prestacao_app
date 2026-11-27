<?php

namespace App\Services;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityItemStatus;
use App\Support\Actor;

/**
 * Lista de prestações "ativas" visíveis para um usuário, com contadores de progresso
 * (compartilhada entre a tela Web e a API mobile).
 *
 * Regras:
 *  - prestações finalizadas vão apenas para o Histórico Anual;
 *  - usuários comuns não veem prestações pausadas nem as sem itens liberados para eles;
 *  - usuários comuns não veem prestações já enviadas, exceto se houver item rejeitado
 *    ou anexos ainda não enviados.
 */
class AccountabilityListingService
{
    /**
     * @return array<int, array{
     *     accountability: array<string, mixed>,
     *     attachments_count: int,
     *     filled_items: int,
     *     enabled_total: int,
     *     visible_total: int,
     *     progress: int,
     *     rejected_count: int
     * }>
     */
    public function activeFor(Actor $actor): array
    {
        $allowedItems = $actor->allowedItems();

        $attsByAccItem = [];
        $attCounts     = [];
        foreach (AccountabilityAttachment::select('accountability_id', 'item_number')->get() as $att) {
            $accId   = (int) $att->accountability_id;
            $itemNum = (int) $att->item_number;
            if ($actor->canAccessItem($itemNum)) {
                $attCounts[$accId]                = ($attCounts[$accId] ?? 0) + 1;
                $attsByAccItem[$accId][$itemNum] = ($attsByAccItem[$accId][$itemNum] ?? 0) + 1;
            }
        }

        $statusByAcc = [];
        foreach (AccountabilityItemStatus::select('accountability_id', 'item_number', 'status')->get() as $row) {
            $statusByAcc[(int) $row->accountability_id][(int) $row->item_number] = (string) $row->status;
        }

        $result = [];
        foreach (Accountability::orderBy('id', 'desc')->get()->toArray() as $acc) {
            $accId    = (int) $acc['id'];
            $statuses = $statusByAcc[$accId] ?? [];

            if (Accountability::isFinalized($acc, $statuses)) {
                continue;
            }
            if (! $actor->isAdmin && ! empty($acc['is_paused'])) {
                continue;
            }

            $enabled = array_map('intval', array_keys(Accountability::getEnabledItemsForAccountability($acc)));
            $visible = $actor->isAdmin ? $enabled : array_values(array_intersect($allowedItems, $enabled));
            if (empty($visible)) {
                continue;
            }

            $rejected = 0;
            if (! $actor->isAdmin) {
                $hasSentAny     = false;
                $hasUnsentFiles = false;

                foreach ($visible as $itemNum) {
                    $st       = $statuses[$itemNum] ?? 'pendente';
                    $hasFiles = ! empty($attsByAccItem[$accId][$itemNum]);

                    if (in_array($st, ['enviado', 'confirmado'], true)) {
                        $hasSentAny = true;
                    } elseif ($st === 'rejeitado') {
                        $rejected++;
                    } elseif ($hasFiles && in_array($st, ['pendente', 'prazo_vencido'], true)) {
                        $hasUnsentFiles = true;
                    }
                }

                if ($hasSentAny && $rejected === 0 && ! $hasUnsentFiles) {
                    continue;
                }
            }

            $filled = 0;
            foreach ($visible as $itemNum) {
                if (! empty($attsByAccItem[$accId][$itemNum])) {
                    $filled++;
                }
            }

            $result[] = [
                'accountability'    => $acc,
                'attachments_count' => $attCounts[$accId] ?? 0,
                'filled_items'      => $filled,
                'enabled_total'     => count($enabled),
                'visible_total'     => count($visible),
                'progress'          => (int) round(($filled / max(1, count($visible))) * 100),
                'rejected_count'    => $rejected,
            ];
        }

        return $result;
    }
}
