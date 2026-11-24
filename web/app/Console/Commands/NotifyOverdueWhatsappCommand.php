<?php

namespace App\Console\Commands;

use App\Services\OverdueWhatsappNotifierService;
use Illuminate\Console\Command;

class NotifyOverdueWhatsappCommand extends Command
{
    protected $signature = 'whatsapp:notify-overdue
        {--dry-run : Apenas simula e exibe as mensagens que seriam enviadas, sem disparar no WhatsApp}
        {--force : Reenvia mesmo se o usuário já tiver sido notificado hoje}';

    protected $description = 'Envia alertas automáticos no WhatsApp para usuários responsáveis por documentos atrasados da prestação de contas.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');

        $service = new OverdueWhatsappNotifierService();
        $res     = $service->notifyOverdue($dryRun, $force);

        $this->info(sprintf(
            'Prestações vencidas verificadas: %d | Destinatários elegíveis: %d',
            $res['checked_accountabilities'],
            $res['candidates']
        ));

        if ($dryRun) {
            if (empty($res['previews'])) {
                $this->line('Nenhuma mensagem pendente para envio (ou já notificada hoje).');
                return self::SUCCESS;
            }
            foreach ($res['previews'] as $p) {
                $this->line('----------------------------------------');
                $this->info("Para: {$p['user_name']} ({$p['whatsapp']}) [Prestação #{$p['accountability_id']} • Item {$p['item_number']}]");
                $this->line($p['message']);
            }
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Enviados: %d | Já notificados hoje: %d | Falhas: %d',
            $res['sent'],
            $res['skipped_already_sent'],
            $res['failed']
        ));

        foreach ($res['errors'] as $err) {
            $this->error($err);
        }

        return $res['failed'] > 0 && $res['sent'] === 0 ? self::FAILURE : self::SUCCESS;
    }
}
