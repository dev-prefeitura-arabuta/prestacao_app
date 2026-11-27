<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Marca (ou desmarca) uma conta como desenvolvedor.
 *
 *   php artisan app:developer dev@exemplo.com
 *   php artisan app:developer dev@exemplo.com --remove
 */
class MakeDeveloperCommand extends Command
{
    protected $signature = 'app:developer {email : E-mail da conta} {--remove : Remove o acesso de desenvolvedor}';

    protected $description = 'Concede ou remove o acesso de desenvolvedor (configurações de SMTP, WhatsApp e IA)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user  = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("Nenhuma conta encontrada com o e-mail {$email}.");

            return self::FAILURE;
        }

        if ($this->option('remove')) {
            $user->forceFill(['is_developer' => false])->save();
            $this->info("{$user->name} não é mais desenvolvedor.");

            return self::SUCCESS;
        }

        // Desenvolvedor sempre é administrador
        $user->forceFill(['is_developer' => true, 'role' => 'admin'])->save();
        $this->info("{$user->name} agora é desenvolvedor (acesso a SMTP, WhatsApp e IA). Faça login novamente para aplicar.");

        return self::SUCCESS;
    }
}
