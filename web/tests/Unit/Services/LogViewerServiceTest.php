<?php

namespace Tests\Unit\Services;

use App\Services\LogViewerService;
use Tests\TestCase;

class LogViewerServiceTest extends TestCase
{
    private function writeLog(string $content, string $name = 'laravel-2026-11-28.log'): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $content);
    }

    public function test_extrai_contexto_de_registro_comum(): void
    {
        $this->useTempStorage();
        $this->writeLog("[2026-11-28 08:00:00] production.INFO: Login (web) {\"user_id\":3,\"email\":\"a@b.com\",\"ip\":\"10.0.0.1\"}\n");

        $e = app(LogViewerService::class)->read('laravel-2026-11-28.log')['entries'][0];

        $this->assertSame('production', $e['env']);
        $this->assertSame('Login (web)', $e['clean_message']);
        $this->assertSame(['user_id' => 3, 'email' => 'a@b.com', 'ip' => '10.0.0.1'], $e['context']);
        $this->assertNull($e['exception']);
        $this->assertSame([], $e['trace']);
    }

    public function test_extrai_excecao_anteriores_e_frames(): void
    {
        $this->useTempStorage();
        $base = str_replace('\\', '/', base_path());
        $this->writeLog(implode("\n", [
            '[2026-11-28 09:00:00] local.ERROR: Falhou ao salvar {"exception":"[object] (Illuminate\\\\Database\\\\QueryException(code: 2002): SQLSTATE[HY000] recusada',
            'segunda linha da mensagem at ' . $base . '/vendor/laravel/framework/src/Database/Connection.php:825)',
            '[stacktrace]',
            '#0 ' . $base . '/vendor/laravel/framework/src/Database/Connection.php(779): runQueryCallback()',
            '#1 ' . $base . '/app/Services/ItemReviewService.php(120): Illuminate\\\\Database\\\\Connection->run()',
            '#2 {main}',
            '',
            '[previous exception] [object] (PDOException(code: 2002): Connection refused at ' . $base . '/vendor/laravel/framework/src/Database/Connectors/Connector.php:66)',
            '[stacktrace]',
            '#0 ' . $base . '/vendor/x.php(10): y()',
            '"} ',
            '',
        ]));

        $e = app(LogViewerService::class)->read('laravel-2026-11-28.log')['entries'][0];

        $this->assertSame('Falhou ao salvar', $e['clean_message']);
        $this->assertSame('Illuminate\\Database\\QueryException', $e['exception']['class']);
        $this->assertSame('2002', $e['exception']['code']);
        $this->assertStringContainsString("recusada\nsegunda linha", $e['exception']['message']);
        $this->assertSame('vendor/laravel/framework/src/Database/Connection.php', $e['exception']['file']);
        $this->assertSame(825, $e['exception']['line']);

        $this->assertCount(1, $e['previous']);
        $this->assertSame('PDOException', $e['previous'][0]['class']);
        $this->assertCount(1, $e['previous'][0]['trace']);

        $this->assertCount(3, $e['trace']);
        $this->assertFalse($e['trace'][0]['app']);
        $this->assertTrue($e['trace'][1]['app']);
        $this->assertSame('app/Services/ItemReviewService.php', $e['trace'][1]['file']);
        $this->assertSame(120, $e['trace'][1]['line']);
        $this->assertNull($e['trace'][2]['file']);
        $this->assertSame(1, $e['app_frames']);
        $this->assertStringContainsString('[stacktrace]', $e['raw']);
    }

    public function test_mais_recentes_primeiro_e_contagem_por_nivel(): void
    {
        $this->useTempStorage();
        $this->writeLog(implode("\n", [
            '[2026-11-28 08:00:00] local.INFO: primeiro',
            '[2026-11-28 08:01:00] local.DEBUG: segundo',
            '[2026-11-28 08:02:00] local.CRITICAL: terceiro',
            '',
        ]));

        $r = app(LogViewerService::class)->read('laravel-2026-11-28.log');

        $this->assertSame(['terceiro', 'segundo', 'primeiro'], array_column($r['entries'], 'clean_message'));
        $this->assertSame(['error' => 1, 'warning' => 0, 'info' => 1, 'other' => 1], $r['counts']);
    }
}
