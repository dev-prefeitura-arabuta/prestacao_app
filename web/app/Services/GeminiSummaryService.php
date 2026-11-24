<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiSummaryService
{
    private const CACHE_TTL_SECONDS = 86400; // 24 horas

    public function getProvider(): string
    {
        $raw = strtolower(trim((string) env('AI_PROVIDER', 'groq'), " \t\n\r\0\x0B'\""));
        return in_array($raw, ['groq', 'gemini'], true) ? $raw : 'groq';
    }

    public function isEnabled(): bool
    {
        $enabled = filter_var(
            env('AI_SUMMARY_ENABLED', env('GEMINI_SUMMARY_ENABLED', true)),
            FILTER_VALIDATE_BOOLEAN
        );
        return $enabled && $this->getApiKey() !== '';
    }

    public function getApiKey(): string
    {
        $keyName = $this->getProvider() === 'groq' ? 'GROQ_API_KEY' : 'GEMINI_API_KEY';
        $raw     = (string) env($keyName, '');
        return trim($raw, " \t\n\r\0\x0B'\"");
    }

    public function getModel(): string
    {
        if ($this->getProvider() === 'groq') {
            $raw = trim((string) env('GROQ_MODEL', 'llama-3.3-70b-versatile'), " \t\n\r\0\x0B'\"");
            return $raw !== '' ? $raw : 'llama-3.3-70b-versatile';
        }

        $raw = trim((string) env('GEMINI_MODEL', 'gemini-2.5-flash'), " \t\n\r\0\x0B'\"");
        $raw = (string) preg_replace('/^models\//i', '', $raw);
        return $raw !== '' ? $raw : 'gemini-2.5-flash';
    }

    /**
     * Gera um resumo inteligente usando Groq API (ou Gemini API) fundamentado nos textos (PDF + OCR local)
     * e nas validações determinísticas executadas em PHP.
     *
     * @param array<string, mixed>              $accountability
     * @param array<string, mixed>              $stats
     * @param array<int, array<string, mixed>>  $analyzedDocs
     * @param array<string, mixed>              $validations
     * @return array{success: bool, text: ?string, provider: string, model: string, cached: bool, tokens_used: int, error: ?string}
     */
    public function summarize(
        string $question,
        string $intent,
        array $accountability,
        array $stats,
        array $analyzedDocs,
        array $validations
    ): array {
        $provider      = $this->getProvider();
        $providerLabel = $provider === 'groq' ? 'Groq API' : 'Gemini API';
        $apiKey        = $this->getApiKey();
        $model         = $this->getModel();

        if (! $this->isEnabled() || $apiKey === '') {
            $envVar = $provider === 'groq' ? 'GROQ_API_KEY' : 'GEMINI_API_KEY';
            return [
                'success'     => false,
                'text'        => null,
                'provider'    => $providerLabel,
                'model'       => $model,
                'cached'      => false,
                'tokens_used' => 0,
                'error'       => "{$envVar} não configurada no .env.",
            ];
        }

        $prompt   = $this->buildPrompt($question, $intent, $accountability, $stats, $analyzedDocs, $validations);
        $cacheKey = $provider . '_' . md5($provider . '|' . $model . '|' . $prompt);

        $cached = $this->readCache($cacheKey);
        if ($cached !== null) {
            return [
                'success'     => true,
                'text'        => (string) ($cached['text'] ?? ''),
                'provider'    => (string) ($cached['provider'] ?? $providerLabel),
                'model'       => (string) ($cached['model'] ?? $model),
                'cached'      => true,
                'tokens_used' => 0,
                'error'       => null,
            ];
        }

        $timeout = max(15, min(90, (int) env('AI_TIMEOUT', 45)));

        if ($provider === 'groq') {
            return $this->callGroq($apiKey, $model, $prompt, $cacheKey, $timeout);
        }

        return $this->callGemini($apiKey, $model, $prompt, $cacheKey, $timeout);
    }

    /**
     * @return array{success: bool, text: ?string, provider: string, model: string, cached: bool, tokens_used: int, error: ?string}
     */
    private function callGroq(string $apiKey, string $model, string $prompt, string $cacheKey, int $timeout): array
    {
        $modelsToTry = array_values(array_unique([$model, 'llama-3.3-70b-versatile', 'llama-3.1-8b-instant']));
        $lastError   = null;

        foreach ($modelsToTry as $candidateModel) {
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ])
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model'       => $candidateModel,
                        'temperature' => 0.2,
                        'max_tokens'  => 1400,
                        'messages'    => [
                            [
                                'role'    => 'system',
                                'content' => 'Você é um auditor especialista em Prestação de Contas Públicas Municipais no Brasil. Responda em português do Brasil de forma objetiva, fiel aos documentos extraídos (PDF/OCR) e às validações PHP.',
                            ],
                            [
                                'role'    => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);

                if ($response->status() === 404) {
                    $lastError = "Modelo {$candidateModel} não encontrado na Groq API.";
                    continue;
                }

                if (! $response->successful()) {
                    $errJson   = $response->json();
                    $apiMsg    = is_array($errJson) ? (string) ($errJson['error']['message'] ?? $response->body()) : $response->body();
                    $lastError = 'Groq API (HTTP ' . $response->status() . '): ' . mb_substr($apiMsg, 0, 220);
                    break;
                }

                $json = $response->json();
                $text = trim((string) ($json['choices'][0]['message']['content'] ?? ''));
                if ($text === '') {
                    $lastError = 'Groq API retornou resposta vazia.';
                    break;
                }

                $tokensUsed = (int) ($json['usage']['total_tokens'] ?? 0);

                $this->writeCache($cacheKey, [
                    'text'     => $text,
                    'provider' => 'Groq API',
                    'model'    => $candidateModel,
                    'ts'       => time(),
                ]);

                return [
                    'success'     => true,
                    'text'        => $text,
                    'provider'    => 'Groq API',
                    'model'       => $candidateModel,
                    'cached'      => false,
                    'tokens_used' => $tokensUsed,
                    'error'       => null,
                ];
            } catch (Throwable $e) {
                $lastError = 'Falha ao conectar na Groq API: ' . $e->getMessage();
                break;
            }
        }

        return [
            'success'     => false,
            'text'        => null,
            'provider'    => 'Groq API',
            'model'       => $model,
            'cached'      => false,
            'tokens_used' => 0,
            'error'       => $lastError ?? 'Falha desconhecida ao consultar Groq API.',
        ];
    }

    /**
     * @return array{success: bool, text: ?string, provider: string, model: string, cached: bool, tokens_used: int, error: ?string}
     */
    private function callGemini(string $apiKey, string $model, string $prompt, string $cacheKey, int $timeout): array
    {
        $modelsToTry     = array_values(array_unique([$model, 'gemini-2.5-flash', 'gemini-2.0-flash']));
        $lastError       = null;
        $isVertexExpress = str_starts_with($apiKey, 'AQ.');

        foreach ($modelsToTry as $candidateModel) {
            try {
                if ($isVertexExpress) {
                    $url = sprintf(
                        'https://aiplatform.googleapis.com/v1/publishers/google/models/%s:generateContent?key=%s',
                        urlencode($candidateModel),
                        urlencode($apiKey)
                    );
                } else {
                    $url = sprintf(
                        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                        urlencode($candidateModel),
                        urlencode($apiKey)
                    );
                }

                $response = Http::timeout($timeout)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'contents' => [
                            [
                                'role'  => 'user',
                                'parts' => [['text' => $prompt]],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.2,
                            'maxOutputTokens' => 1400,
                        ],
                    ]);

                if ($response->status() === 404) {
                    $lastError = "Modelo {$candidateModel} não encontrado na Gemini API.";
                    continue;
                }

                if (! $response->successful()) {
                    $errJson   = $response->json();
                    $apiMsg    = is_array($errJson) ? (string) ($errJson['error']['message'] ?? $response->body()) : $response->body();
                    $lastError = 'Gemini API (HTTP ' . $response->status() . '): ' . mb_substr($apiMsg, 0, 220);
                    break;
                }

                $json = $response->json();
                $text = (string) ($json['candidates'][0]['content']['parts'][0]['text'] ?? '');
                $text = trim($text);

                if ($text === '') {
                    $lastError = 'Gemini API retornou resposta vazia.';
                    break;
                }

                $tokensUsed = (int) ($json['usageMetadata']['totalTokenCount'] ?? 0);

                $this->writeCache($cacheKey, [
                    'text'     => $text,
                    'provider' => 'Gemini API',
                    'model'    => $candidateModel,
                    'ts'       => time(),
                ]);

                return [
                    'success'     => true,
                    'text'        => $text,
                    'provider'    => 'Gemini API',
                    'model'       => $candidateModel,
                    'cached'      => false,
                    'tokens_used' => $tokensUsed,
                    'error'       => null,
                ];
            } catch (Throwable $e) {
                $lastError = 'Falha ao conectar na Gemini API: ' . $e->getMessage();
                break;
            }
        }

        return [
            'success'     => false,
            'text'        => null,
            'provider'    => 'Gemini API',
            'model'       => $model,
            'cached'      => false,
            'tokens_used' => 0,
            'error'       => $lastError ?? 'Falha desconhecida ao consultar Gemini API.',
        ];
    }

    /**
     * @param array<string, mixed>              $accountability
     * @param array<string, mixed>              $stats
     * @param array<int, array<string, mixed>>  $analyzedDocs
     * @param array<string, mixed>              $validations
     */
    private function buildPrompt(
        string $question,
        string $intent,
        array $accountability,
        array $stats,
        array $analyzedDocs,
        array $validations
    ): string {
        $title    = (string) ($accountability['title'] ?? 'Prestação de Contas');
        $deadline = ! empty($accountability['deadline'])
            ? date('d/m/Y', strtotime((string) $accountability['deadline']))
            : 'Não definido';

        $sc = $stats['status_counts'] ?? [];

        $lines = [];
        $lines[] = 'Você é um auditor especialista em Prestação de Contas Públicas Municipais no Brasil.';
        $lines[] = 'Responda em português do Brasil, de forma objetiva, clara e bem estruturada em tópicos (use **negrito** para destaques).';
        $lines[] = 'REGRAS ESTRITAS:';
        $lines[] = '1. Use APENAS os fatos presentes nos textos extraídos (PDF/OCR local) e nas Validações PHP abaixo. Nunca invente valores, CNPJs ou datas.';
        $lines[] = '2. Sempre destaque inconsistências ou alertas apontados pelas Validações PHP.';
        $lines[] = '3. Seja conciso e direto ao ponto.';
        $lines[] = '';
        $lines[] = "PERGUNTA DO USUÁRIO (intenção: {$intent}): " . ($question !== '' ? $question : 'resumo geral');
        $lines[] = '';
        $lines[] = "PRESTAÇÃO DE CONTAS: {$title} | Prazo: {$deadline}";
        $lines[] = sprintf(
            'ITENS: %d liberados (%d com anexo) | Confirmados: %d | Enviados: %d | Pendentes: %d | Rejeitados: %d | Vencidos: %d',
            (int) ($stats['total_items'] ?? 0),
            (int) ($stats['items_with_files'] ?? 0),
            (int) ($sc['confirmado'] ?? 0),
            (int) ($sc['enviado'] ?? 0),
            (int) ($sc['pendente'] ?? 0),
            (int) ($sc['rejeitado'] ?? 0),
            (int) ($sc['prazo_vencido'] ?? 0)
        );
        $lines[] = '';
        $lines[] = '=== VALIDAÇÕES PRÓPRIAS EM PHP (DETERMINÍSTICAS) ===';
        $alerts = $validations['alerts'] ?? [];
        if (empty($alerts)) {
            $lines[] = '- Nenhuma inconsistência estrutural detectada nas validações automáticas em PHP.';
        } else {
            foreach (array_slice($alerts, 0, 20) as $a) {
                $lines[] = '- [' . strtoupper((string) ($a['level'] ?? 'info')) . '] ' . (string) ($a['message'] ?? '');
            }
        }

        $lines[] = '';
        $lines[] = '=== DOCUMENTOS EXTRAÍDOS (PDF + OCR LOCAL) ===';

        $budgetChars = 16000;
        $usedChars   = 0;

        foreach ($analyzedDocs as $doc) {
            $numPad = str_pad((string) ($doc['item_number'] ?? 0), 2, '0', STR_PAD_LEFT);
            $source = ! empty($doc['used_ocr']) ? 'OCR Local' : 'Texto Direto';
            $header = sprintf(
                "\n[Item %s — %s] Arquivo: %s (%s • %s • %s)",
                $numPad,
                (string) ($doc['item_title'] ?? ''),
                (string) ($doc['original_name'] ?? ''),
                strtoupper((string) ($doc['ext'] ?? '')),
                (string) ($doc['doc_type'] ?? ''),
                $source
            );

            $meta = [];
            if (! empty($doc['values'])) {
                $meta[] = 'Valores: R$ ' . implode(', R$ ', array_map(static fn ($v) => number_format((float) $v, 2, ',', '.'), array_slice($doc['values'], 0, 5)));
            }
            if (! empty($doc['cnpjs'])) {
                $meta[] = 'CNPJs: ' . implode(', ', $doc['cnpjs']);
            }
            if (! empty($doc['cpfs'])) {
                $meta[] = 'CPFs: ' . implode(', ', $doc['cpfs']);
            }
            if (! empty($doc['dates'])) {
                $meta[] = 'Datas: ' . implode(', ', $doc['dates']);
            }
            if (! empty($doc['doc_numbers'])) {
                $meta[] = 'Nº Doc: ' . implode(', ', $doc['doc_numbers']);
            }

            $excerpt = '';
            $rawText = trim((string) ($doc['text'] ?? ''));
            if ($rawText !== '' && $usedChars < $budgetChars) {
                $slice   = mb_substr($rawText, 0, min(1400, $budgetChars - $usedChars));
                $usedChars += mb_strlen($slice);
                $excerpt = "Trecho extraído: " . preg_replace('/\s+/u', ' ', $slice);
            } elseif (! empty($doc['notes'])) {
                $excerpt = 'Obs: ' . implode(' ', $doc['notes']);
            }

            $lines[] = $header;
            if (! empty($meta)) {
                $lines[] = 'Dados PHP: ' . implode(' | ', $meta);
            }
            if ($excerpt !== '') {
                $lines[] = $excerpt;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readCache(string $cacheKey): ?array
    {
        $path = storage_path('app/gemini_summary_cache/' . $cacheKey . '.json');
        if (! is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || empty($decoded['text'])) {
            return null;
        }
        if ((time() - (int) ($decoded['ts'] ?? 0)) > self::CACHE_TTL_SECONDS) {
            @unlink($path);
            return null;
        }
        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeCache(string $cacheKey, array $payload): void
    {
        $dir = storage_path('app/gemini_summary_cache');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . $cacheKey . '.json', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
