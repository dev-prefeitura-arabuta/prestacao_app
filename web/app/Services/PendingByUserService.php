<?php

namespace App\Services;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityItemStatus;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Support\Phone;

/**
 * Painel de pendências por pessoa: o que cada usuário ainda precisa enviar
 * nas prestações em andamento (não pausadas e não finalizadas).
 */
class PendingByUserService
{
    /** Situações em que o item ainda depende do usuário. */
    private const OPEN_STATUSES = ['pendente', 'rejeitado', 'prazo_vencido'];

    /**
     * @return array<int, array{
     *     user: array{id: int, name: string, whatsapp: string, has_whatsapp: bool},
     *     overdue_count: int,
     *     items: array<int, array<string, mixed>>
     * }>
     */
    public function build(): array
    {
        $allItems = Accountability::getItems();
        $today    = strtotime(date('Y-m-d'));

        // Prestações em andamento
        $accs = [];
        foreach (Accountability::where(fn ($q) => $q->whereNull('is_paused')->orWhere('is_paused', false))->get() as $acc) {
            $arr      = $acc->toArray();
            $statuses = AccountabilityItemStatus::where('accountability_id', (int) $acc->id)
                ->pluck('status', 'item_number')
                ->mapWithKeys(fn ($st, $n) => [(int) $n => (string) $st])
                ->all();

            if (Accountability::isFinalized($arr, $statuses)) {
                continue;
            }

            $accs[(int) $acc->id] = [
                'model'      => $acc,
                'enabled'    => array_map('intval', array_keys(Accountability::getEnabledItemsForAccountability($arr))),
                'statuses'   => $statuses,
                'overdue'    => ! empty($acc->deadline) && strtotime((string) $acc->deadline) < $today,
                'with_files' => AccountabilityAttachment::where('accountability_id', (int) $acc->id)
                    ->distinct()
                    ->pluck('item_number')
                    ->map(fn ($n) => (int) $n)
                    ->all(),
            ];
        }

        if (empty($accs)) {
            return [];
        }

        $result = [];
        foreach (User::where('role', '!=', 'admin')->orderBy('name')->get() as $user) {
            $perms = UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id);
            if (empty($perms)) {
                continue;
            }

            $items   = [];
            $overdue = 0;
            foreach ($accs as $accId => $info) {
                foreach (array_intersect($perms, $info['enabled']) as $itemNumber) {
                    $status = $info['statuses'][$itemNumber] ?? ($info['overdue'] ? 'prazo_vencido' : 'pendente');
                    if (! in_array($status, self::OPEN_STATUSES, true)) {
                        continue;
                    }

                    if ($info['overdue']) {
                        $overdue++;
                    }

                    $items[] = [
                        'accountability_id'    => $accId,
                        'accountability_title' => (string) $info['model']->title,
                        'deadline'             => $info['model']->deadline ? date('d/m/Y', strtotime((string) $info['model']->deadline)) : null,
                        'overdue'              => $info['overdue'],
                        'item_number'          => (int) $itemNumber,
                        'item_title'           => rtrim((string) ($allItems[$itemNumber] ?? "Item {$itemNumber}"), ';.'),
                        'status'               => $status,
                        // Já anexou, mas ainda não clicou em Enviar
                        'has_files'            => in_array((int) $itemNumber, $info['with_files'], true),
                    ];
                }
            }

            if (empty($items)) {
                continue;
            }

            $result[] = [
                'user' => [
                    'id'           => (int) $user->id,
                    'name'         => (string) $user->name,
                    'whatsapp'     => (string) ($user->whatsapp ?? ''),
                    'has_whatsapp' => Phone::toInternational($user->whatsapp) !== null,
                ],
                'overdue_count' => $overdue,
                'items'         => $items,
            ];
        }

        // Quem tem mais itens atrasados primeiro, depois quem tem mais pendências
        usort($result, fn ($a, $b) => [$b['overdue_count'], count($b['items'])] <=> [$a['overdue_count'], count($a['items'])]);

        return $result;
    }
}
