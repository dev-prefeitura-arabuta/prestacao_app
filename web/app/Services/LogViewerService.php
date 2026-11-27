<?php

namespace App\Services;

/**
 * Leitura dos logs do Laravel (storage/logs) para a tela "Logs do Sistema".
 *
 * Aceita o arquivo diário (laravel-AAAA-MM-DD.log) e o arquivo único antigo (laravel.log).
 * Só lê o final de arquivos grandes, para não pesar no servidor.
 */
class LogViewerService
{
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /** Níveis tratados como "erro" no resumo e no filtro. */
    public const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error'];

    private const FILE_PATTERN = '/^laravel(-\d{4}-\d{2}-\d{2})?\.log$/';

    private const HEADER_PATTERN = '/^\[(?<time>\d{4}-\d{2}-\d{2}[ T][0-9:.+\-]+)\] (?<env>[\w-]+)\.(?<level>[A-Z]+): (?<msg>.*)$/';

    private const MAX_READ_BYTES = 3 * 1024 * 1024;

    public function directory(): string
    {
        return storage_path('logs');
    }

    /**
     * Arquivos de log disponíveis, do mais recente para o mais antigo.
     *
     * @return array<int, array{name: string, date: ?string, size: int, modified: int}>
     */
    public function files(): array
    {
        $files = [];
        foreach (glob($this->directory() . DIRECTORY_SEPARATOR . 'laravel*.log') ?: [] as $path) {
            $name = basename($path);
            if (! $this->isValidName($name)) {
                continue;
            }
            preg_match('/(\d{4}-\d{2}-\d{2})/', $name, $m);
            $files[] = [
                'name'     => $name,
                'date'     => $m[1] ?? null,
                'size'     => (int) filesize($path),
                'modified' => (int) filemtime($path),
            ];
        }

        usort($files, fn ($a, $b) => [$b['date'] ?? '', $b['modified']] <=> [$a['date'] ?? '', $a['modified']]);

        return $files;
    }

    /** Impede acesso a qualquer arquivo fora do padrão (ex.: "../.env"). */
    public function isValidName(string $name): bool
    {
        return preg_match(self::FILE_PATTERN, $name) === 1;
    }

    public function path(string $name): ?string
    {
        if (! $this->isValidName($name)) {
            return null;
        }
        $path = $this->directory() . DIRECTORY_SEPARATOR . $name;

        return is_file($path) ? $path : null;
    }

    /**
     * Entradas do arquivo, das mais recentes para as mais antigas.
     *
     * @return array{
     *     entries: array<int, array{time: string, level: string, message: string, details: string}>,
     *     counts: array<string, int>,
     *     total: int,
     *     truncated: bool
     * }
     */
    public function read(string $name, string $level = 'all', string $search = '', int $limit = 300): array
    {
        $empty = ['entries' => [], 'counts' => ['error' => 0, 'warning' => 0, 'info' => 0, 'other' => 0], 'total' => 0, 'truncated' => false];
        $path  = $this->path($name);
        if ($path === null) {
            return $empty;
        }

        [$content, $truncated] = $this->tail($path);

        $entries = [];
        $current = null;
        foreach (preg_split('/\r\n|\n|\r/', $content) ?: [] as $line) {
            if (preg_match(self::HEADER_PATTERN, $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $msg     = $m['msg'];
                $details = '';
                // Exceções trazem o contexto (classe, arquivo, stack trace) logo após a mensagem
                $cut = strpos($msg, ' {"exception":');
                if ($cut !== false) {
                    $details = substr($msg, $cut + 1);
                    $msg     = substr($msg, 0, $cut);
                }
                $current = [
                    'time'    => str_replace('T', ' ', substr($m['time'], 0, 19)),
                    'env'     => $m['env'],
                    'level'   => strtolower($m['level']),
                    'message' => $msg,
                    'details' => $details,
                    'raw'     => $line,
                ];
            } elseif ($current !== null) {
                $current['details'] .= ($current['details'] === '' ? '' : "\n") . $line;
                $current['raw']     .= "\n" . $line;
            }
        }
        if ($current !== null) {
            $entries[] = $current;
        }

        $counts = $empty['counts'];
        foreach ($entries as $e) {
            $counts[$this->group($e['level'])]++;
        }

        $search  = mb_strtolower(trim($search));
        $entries = array_filter($entries, function (array $e) use ($level, $search) {
            if ($level !== 'all' && $this->group($e['level']) !== $level) {
                return false;
            }

            return $search === '' || str_contains(mb_strtolower($e['message'] . "\n" . $e['details']), $search);
        });

        $entries = array_reverse(array_values($entries));
        $page = array_map(fn (array $e) => $this->enrich($e), array_slice($entries, 0, $limit));

        return [
            'entries'   => $page,
            'counts'    => $counts,
            'total'     => count($entries),
            'truncated' => $truncated,
        ];
    }

    /** Quantidade máxima de frames do stack trace exibidos por exceção. */
    private const MAX_FRAMES = 80;

    /**
     * Separa o registro em partes para o preview detalhado:
     * contexto (JSON), exceção (classe, código, mensagem, arquivo:linha), exceções anteriores e stack trace.
     *
     * @param array{time: string, env: string, level: string, message: string, details: string, raw: string} $e
     */
    public function enrich(array $e): array
    {
        $message = $e['message'];
        $context = null;

        // Registros comuns: "mensagem {json do contexto}"
        if ($e['details'] === '' && preg_match('/^(.*?) (\{.*\}|\[.*\])\s*$/s', $message, $m)) {
            $decoded = json_decode($m[2], true);
            if (is_array($decoded) && $decoded !== []) {
                $context = $decoded;
                $message = $m[1];
            }
        }

        $exception = null;
        $previous  = [];
        $trace     = [];

        if ($e['details'] !== '') {
            $body  = preg_replace('/"\}\s*$/', '', rtrim($e['details'])) ?? $e['details'];
            $parts = preg_split('/\R\[previous exception\] /', $body) ?: [$body];

            foreach ($parts as $index => $part) {
                [$head, $frames] = array_pad(preg_split('/\R\[stacktrace\]\R/', $part, 2) ?: [$part], 2, '');
                $info   = $this->parseExceptionHead($head);
                $frames = $this->parseFrames((string) $frames);

                if ($index === 0) {
                    $exception = $info;
                    $trace     = $frames;
                } elseif ($info !== null) {
                    $previous[] = $info + ['trace' => $frames];
                }
            }
        }

        return $e + [
            'clean_message' => trim($message),
            'context'       => $context,
            'exception'     => $exception,
            'previous'      => $previous,
            'trace'         => $trace,
            'app_frames'    => count(array_filter($trace, fn ($f) => $f['app'])),
        ];
    }

    /**
     * @return array{class: string, code: string, message: string, file: string, line: int}|null
     */
    private function parseExceptionHead(string $head): ?array
    {
        $head = $this->unescape(trim(preg_replace('/^\{"exception":"/', '', trim($head)) ?? $head));

        if (! preg_match('/^\[object\] \((?<class>[^\s(]+)\(code: (?<code>[^)]*)\): (?<msg>.*) at (?<file>.+?):(?<line>\d+)\)\s*$/s', $head, $m)) {
            return null;
        }

        return [
            'class'   => $m['class'],
            'code'    => $m['code'],
            'message' => trim($m['msg']),
            'file'    => $this->relativePath($m['file']),
            'line'    => (int) $m['line'],
        ];
    }

    /**
     * @return array<int, array{n: int, file: ?string, line: ?int, call: string, app: bool}>
     */
    private function parseFrames(string $text): array
    {
        $frames = [];
        foreach (preg_split('/\R/', trim($text)) ?: [] as $line) {
            $line = $this->unescape(trim($line));
            if (preg_match('/^#(?<n>\d+) (?<file>.+?)\((?<line>\d+)\): (?<call>.*)$/', $line, $m)) {
                $file     = $this->relativePath($m['file']);
                $frames[] = [
                    'n'    => (int) $m['n'],
                    'file' => $file,
                    'line' => (int) $m['line'],
                    'call' => $m['call'],
                    'app'  => ! str_contains('/' . $file, '/vendor/'),
                ];
            } elseif (preg_match('/^#(?<n>\d+) (?<rest>.+)$/', $line, $m)) {
                $frames[] = ['n' => (int) $m['n'], 'file' => null, 'line' => null, 'call' => $m['rest'], 'app' => false];
            }
            if (count($frames) >= self::MAX_FRAMES) {
                break;
            }
        }

        return $frames;
    }

    /** O Laravel grava o cabeçalho da exceção com escapes de JSON (\\ e \"). */
    private function unescape(string $text): string
    {
        return str_replace(['\\\\', '\\"', '\\/'], ['\\', '"', '/'], $text);
    }

    /** Caminho relativo à raiz do projeto, com barras normais. */
    private function relativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $base = rtrim(str_replace('\\', '/', base_path()), '/') . '/';

        return str_starts_with(strtolower($path), strtolower($base)) ? substr($path, strlen($base)) : $path;
    }

    /** Agrupa os níveis do Laravel em: error, warning, info, other. */
    public function group(string $level): string
    {
        if (in_array($level, self::ERROR_LEVELS, true)) {
            return 'error';
        }
        if ($level === 'warning') {
            return 'warning';
        }
        if ($level === 'info' || $level === 'notice') {
            return 'info';
        }

        return 'other';
    }

    /**
     * @return array{0: string, 1: bool} Conteúdo e se foi cortado (arquivo maior que o limite)
     */
    private function tail(string $path): array
    {
        $size = (int) filesize($path);
        if ($size <= self::MAX_READ_BYTES) {
            return [(string) file_get_contents($path), false];
        }

        $handle = fopen($path, 'rb');
        fseek($handle, $size - self::MAX_READ_BYTES);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        // Descarta a primeira linha (provavelmente cortada no meio)
        $firstBreak = strpos($content, "\n");

        return [$firstBreak === false ? $content : substr($content, $firstBreak + 1), true];
    }
}
