<?php

namespace App\Services;

use Throwable;
use ZipArchive;

class OllamaService
{
    private string $provider;
    private string $baseUrl;
    private string $model;
    private string $apiKey;
    private int $timeout;

    public function __construct(?string $baseUrl = null, ?string $model = null, int $timeout = 180)
    {
        $config         = $this->loadConfig();
        $this->provider = strtolower((string) ($config['provider'] ?? 'gemini'));
        $this->timeout  = (int) ($config['timeout'] ?? $timeout);
        $this->baseUrl  = rtrim($baseUrl ?? $config['base_url'] ?? 'http://127.0.0.1:11434', '/');

        if ($this->provider === 'gemini') {
            $this->apiKey = (string) ($config['gemini_api_key'] ?? '');
            $rawGemModel  = (string) ($model ?? $config['gemini_model'] ?? 'gemini-3.8-flash');
            $this->model  = preg_replace('/^models\//i', '', trim($rawGemModel));
        } elseif ($this->provider === 'openrouter') {
            $this->apiKey = (string) ($config['openrouter_api_key'] ?? '');
            $this->model  = (string) ($model ?? $config['openrouter_model'] ?? 'qwen/qwen2.5-vl-72b-instruct:free');
        } elseif ($this->provider === 'groq') {
            $this->apiKey = (string) ($config['groq_api_key'] ?? '');
            $this->model  = (string) ($model ?? $config['groq_model'] ?? 'meta-llama/llama-4-scout-17b-16e-instruct');
        } else {
            $this->provider = 'ollama';
            $this->apiKey   = '';
            $this->model    = (string) ($model ?? $config['model'] ?? 'qwen3-vl:8b');
        }
    }

    public function loadConfig(): array
    {
        $envProvider   = trim((string) (getenv('AI_PROVIDER') ?: $_ENV['AI_PROVIDER'] ?? 'gemini'));
        $envGeminiKey  = trim((string) (getenv('GEMINI_API_KEY') ?: $_ENV['GEMINI_API_KEY'] ?? ''), " '\"");
        $envGeminiMod  = trim((string) (getenv('GEMINI_MODEL') ?: $_ENV['GEMINI_MODEL'] ?? 'gemini-3.8-flash'), " '\"");
        $envOrKey      = trim((string) (getenv('OPENROUTER_API_KEY') ?: $_ENV['OPENROUTER_API_KEY'] ?? ''), " '\"");
        $envOrMod      = trim((string) (getenv('OPENROUTER_MODEL') ?: $_ENV['OPENROUTER_MODEL'] ?? 'qwen/qwen2.5-vl-72b-instruct:free'), " '\"");
        $envGroqKey    = trim((string) (getenv('GROQ_API_KEY') ?: $_ENV['GROQ_API_KEY'] ?? ''), " '\"");
        $envGroqMod    = trim((string) (getenv('GROQ_MODEL') ?: $_ENV['GROQ_MODEL'] ?? 'meta-llama/llama-4-scout-17b-16e-instruct'), " '\"");
        $envOllamaUrl  = trim((string) (getenv('OLLAMA_BASE_URL') ?: $_ENV['OLLAMA_BASE_URL'] ?? 'http://127.0.0.1:11434'), " '\"");
        $envOllamaMod  = trim((string) (getenv('OLLAMA_MODEL') ?: $_ENV['OLLAMA_MODEL'] ?? 'qwen3-vl:8b'), " '\"");
        $envTimeout    = (int) (getenv('AI_TIMEOUT') ?: $_ENV['AI_TIMEOUT'] ?? 180);

        $defaults = [
            'provider'           => $envProvider !== '' ? strtolower($envProvider) : 'gemini',
            'gemini_api_key'     => $envGeminiKey,
            'gemini_model'       => $envGeminiMod !== '' ? $envGeminiMod : 'gemini-3.8-flash',
            'openrouter_api_key' => $envOrKey,
            'openrouter_model'   => $envOrMod !== '' ? $envOrMod : 'qwen/qwen2.5-vl-72b-instruct:free',
            'groq_api_key'       => $envGroqKey,
            'groq_model'         => $envGroqMod !== '' ? $envGroqMod : 'meta-llama/llama-4-scout-17b-16e-instruct',
            'base_url'           => $envOllamaUrl !== '' ? $envOllamaUrl : 'http://127.0.0.1:11434',
            'model'              => $envOllamaMod !== '' ? $envOllamaMod : 'qwen3-vl:8b',
            'timeout'            => $envTimeout > 0 ? $envTimeout : 180,
            'system_prompt'      => 'Você é um auditor especialista em prestação de contas públicas municipais. Analise documentos e notas fiscais em português do Brasil, extraindo com precisão números de documento/nota, CNPJ/CPF, datas e valores monetários (R$).',
        ];

        $configFile = WRITEPATH . 'ollama_config.json';
        if (is_file($configFile)) {
            $decoded = json_decode((string) file_get_contents($configFile), true);
            if (is_array($decoded)) {
                foreach ($decoded as $k => $v) {
                    if ($v !== '' && $v !== null) {
                        $defaults[$k] = $v;
                    }
                }
                if ($envGeminiKey !== '') {
                    $defaults['gemini_api_key'] = $envGeminiKey;
                }
                if ($envGeminiMod !== '') {
                    $defaults['gemini_model'] = $envGeminiMod;
                }
                if ($envOrKey !== '') {
                    $defaults['openrouter_api_key'] = $envOrKey;
                }
                if ($envGroqKey !== '') {
                    $defaults['groq_api_key'] = $envGroqKey;
                }
                if ($envProvider !== '') {
                    $defaults['provider'] = strtolower($envProvider);
                }
            }
        }

        // Auto-seleciona o provedor caso uma API key tenha sido preenchida no .env
        if ($defaults['provider'] === 'gemini' && $defaults['gemini_api_key'] === '') {
            if ($defaults['openrouter_api_key'] !== '') {
                $defaults['provider'] = 'openrouter';
            } elseif ($defaults['groq_api_key'] !== '') {
                $defaults['provider'] = 'groq';
            }
        }

        return $defaults;
    }

    public function saveConfig(array $data): bool
    {
        $configFile = WRITEPATH . 'ollama_config.json';
        $current    = $this->loadConfig();
        $merged     = array_merge($current, [
            'provider'           => strtolower(trim((string) ($data['provider'] ?? $current['provider']))),
            'gemini_api_key'     => trim((string) ($data['gemini_api_key'] ?? $current['gemini_api_key'])),
            'gemini_model'       => trim((string) ($data['gemini_model'] ?? $current['gemini_model'])),
            'openrouter_api_key' => trim((string) ($data['openrouter_api_key'] ?? $current['openrouter_api_key'])),
            'openrouter_model'   => trim((string) ($data['openrouter_model'] ?? $current['openrouter_model'])),
            'groq_api_key'       => trim((string) ($data['groq_api_key'] ?? $current['groq_api_key'])),
            'groq_model'         => trim((string) ($data['groq_model'] ?? $current['groq_model'])),
            'base_url'           => rtrim(trim((string) ($data['base_url'] ?? $current['base_url'])), '/'),
            'model'              => trim((string) ($data['model'] ?? $current['model'])),
            'timeout'            => max(15, min(600, (int) ($data['timeout'] ?? $current['timeout']))),
            'system_prompt'      => trim((string) ($data['system_prompt'] ?? $current['system_prompt'])),
        ]);

        return file_put_contents($configFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    /**
     * Verifica o status do provedor de IA configurado (API Online ou Ollama Local).
     */
    public function getStatus(): array
    {
        if ($this->provider === 'gemini') {
            $hasKey = ($this->apiKey !== '');
            return [
                'online'   => $hasKey,
                'provider' => 'Google Gemini API',
                'model'    => $this->model,
                'models'   => [$this->model],
                'error'    => $hasKey ? null : 'Defina GEMINI_API_KEY no arquivo .env',
            ];
        }

        if ($this->provider === 'openrouter') {
            $hasKey = ($this->apiKey !== '');
            return [
                'online'   => $hasKey,
                'provider' => 'OpenRouter API',
                'model'    => $this->model,
                'models'   => [$this->model],
                'error'    => $hasKey ? null : 'Defina OPENROUTER_API_KEY no arquivo .env',
            ];
        }

        if ($this->provider === 'groq') {
            $hasKey = ($this->apiKey !== '');
            return [
                'online'   => $hasKey,
                'provider' => 'Groq Cloud API',
                'model'    => $this->model,
                'models'   => [$this->model],
                'error'    => $hasKey ? null : 'Defina GROQ_API_KEY no arquivo .env',
            ];
        }

        try {
            $ch = curl_init($this->baseUrl . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            $raw      = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($raw === false || $httpCode !== 200) {
                return [
                    'online'   => false,
                    'provider' => 'Ollama Local',
                    'model'    => $this->model,
                    'models'   => [],
                    'error'    => $err ?: "HTTP {$httpCode}",
                ];
            }

            $decoded = json_decode((string) $raw, true);
            $models  = [];
            foreach (($decoded['models'] ?? []) as $m) {
                if (! empty($m['name'])) {
                    $models[] = (string) $m['name'];
                }
            }

            return [
                'online'   => true,
                'provider' => 'Ollama Local',
                'model'    => $this->model,
                'models'   => $models,
                'error'    => null,
            ];
        } catch (Throwable $e) {
            return [
                'online'   => false,
                'provider' => 'Ollama Local',
                'model'    => $this->model,
                'models'   => [],
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Analisa um anexo (Imagem JPG/PNG, PDF ou ZIP) separando Valores (R$), Número da Nota/Documento,
     * Emitente, CNPJ/CPF e Data de Emissão através do provedor configurado no .env.
     */
    public function analyzeAttachment(string $fullPath, string $itemTitle, string $originalName, string $ext): array
    {
        if (! is_file($fullPath)) {
            return [
                'success' => false,
                'error'   => 'Arquivo físico não encontrado no servidor.',
            ];
        }

        $config       = $this->loadConfig();
        $systemPrompt = (string) ($config['system_prompt'] ?? '');
        $ext          = strtolower($ext);

        $jsonInstruction = "Analise o documento anexado referente ao item da Prestação de Contas:\n"
            . "- Item: \"{$itemTitle}\"\n"
            . "- Nome do Arquivo: \"{$originalName}\"\n";

        // Se o provedor for Gemini, podemos enviar tanto Imagem quanto PDF nativamente em base64!
        if ($this->provider === 'gemini') {
            return $this->analyzeWithGemini($fullPath, $ext, $itemTitle, $originalName, $systemPrompt, $jsonInstruction);
        }

        // Para OpenRouter, Groq ou Ollama, extrai imagens / texto de PDF ou ZIP
        $imagesBase64  = [];
        $extractedText = '';
        $imageMimes    = [];

        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $imagesBase64[] = base64_encode((string) file_get_contents($fullPath));
            $imageMimes[]   = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        } elseif ($ext === 'pdf') {
            $pdfData       = $this->extractFromPdf($fullPath);
            $extractedText = $pdfData['text'];
            $imagesBase64  = $pdfData['images'];
            foreach ($imagesBase64 as $_) {
                $imageMimes[] = 'image/jpeg';
            }
        } elseif ($ext === 'zip') {
            $zipData       = $this->extractFromZip($fullPath);
            $extractedText = $zipData['text'];
            $imagesBase64  = $zipData['images'];
            foreach ($imagesBase64 as $_) {
                $imageMimes[] = 'image/jpeg';
            }
        }

        if ($extractedText !== '') {
            $jsonInstruction .= "\nTexto/Conteúdo extraído do arquivo:\n\"\"\"\n" . mb_substr($extractedText, 0, 7000) . "\n\"\"\"\n";
        }

        $jsonInstruction .= $this->getJsonFormatPrompt();

        if (in_array($this->provider, ['openrouter', 'groq'], true)) {
            return $this->analyzeWithOpenAiCompatible($imagesBase64, $imageMimes, $extractedText, $originalName, $itemTitle, $systemPrompt, $jsonInstruction);
        }

        return $this->analyzeWithOllamaLocal($imagesBase64, $extractedText, $originalName, $itemTitle, $systemPrompt, $jsonInstruction);
    }

    private function getJsonFormatPrompt(): string
    {
        return "\nExtraia os dados do documento separando os números, emitente e valores monetários.\n"
            . "Se algum dado NÃO existir no documento (por exemplo, uma foto de obra não tem número de nota nem valor financeiro), retorne string vazia \"\" nos campos de texto e 0 em \"total_value\".\n"
            . "Retorne APENAS um JSON válido com as seguintes chaves exatas:\n"
            . "{\n"
            . "  \"document_type\": \"Tipo do documento (ex: Nota Fiscal, Nota de Empenho, Balancete, Extrato Bancário, Laudo, Foto, Recibo)\",\n"
            . "  \"document_number\": \"Número da Nota Fiscal, Empenho, Contrato ou Documento (deixe \\\"\\\" se não houver número)\",\n"
            . "  \"emitter_name\": \"Nome da Empresa, Fornecedor, Banco ou Órgão Emitente (deixe \\\"\\\" se não houver)\",\n"
            . "  \"cnpj_cpf\": \"CNPJ ou CPF identificado no documento (deixe \\\"\\\" se não houver)\",\n"
            . "  \"issue_date\": \"Data de emissão ou referência no formato DD/MM/AAAA (deixe \\\"\\\" se não houver)\",\n"
            . "  \"total_value\": 0.00,\n"
            . "  \"compatibility\": \"Compatível | Parcialmente Compatível | Divergente\",\n"
            . "  \"summary\": \"Resumo executivo objetivo dos dados encontrados no documento (valores, números, objeto/descrição e partes envolvidas)\"\n"
            . "}\n"
            . "IMPORTANTE: No campo \"total_value\", retorne um número decimal com ponto (ex: 15420.50). Se não houver valor financeiro no documento, retorne 0.";
    }

    /**
     * Análise via Google Gemini API (suporta JPG, PNG e PDF nativamente em base64!).
     */
    private function analyzeWithGemini(
        string $fullPath,
        string $ext,
        string $itemTitle,
        string $originalName,
        string $systemPrompt,
        string $jsonInstruction
    ): array {
        if ($this->apiKey === '') {
            return [
                'success' => false,
                'error'   => 'Chave GEMINI_API_KEY não configurada no arquivo .env. Crie sua chave gratuita em https://aistudio.google.com/apikey e cole no .env.',
            ];
        }

        $parts        = [];
        $fallbackText = '';

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            $mimeMap = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'pdf'  => 'application/pdf',
            ];
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeMap[$ext],
                    'data'      => base64_encode((string) file_get_contents($fullPath)),
                ],
            ];
        } elseif ($ext === 'zip') {
            $zipData      = $this->extractFromZip($fullPath);
            $fallbackText = $zipData['text'];
            if ($fallbackText !== '') {
                $jsonInstruction .= "\nConteúdo do ZIP:\n" . mb_substr($fallbackText, 0, 7000) . "\n";
            }
            foreach (array_slice($zipData['images'], 0, 2) as $imgB64) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => 'image/jpeg',
                        'data'      => $imgB64,
                    ],
                ];
            }
        }

        $parts[] = [
            'text' => ($systemPrompt !== '' ? $systemPrompt . "\n\n" : '') . $jsonInstruction . $this->getJsonFormatPrompt(),
        ];

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($this->model)
            . ':generateContent?key=' . rawurlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature'      => 0.1,
            ],
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [
                'success' => false,
                'error'   => 'Falha de conexão com a API do Google Gemini: ' . $curlErr,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if ($httpCode !== 200) {
            $errMsg = $decoded['error']['message'] ?? ("Erro HTTP {$httpCode} na API do Gemini.");
            return [
                'success' => false,
                'error'   => 'Gemini API: ' . $errMsg,
            ];
        }

        $rawContent = trim((string) ($decoded['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        if ($rawContent === '') {
            return [
                'success' => false,
                'error'   => 'A API do Gemini não retornou texto na análise.',
            ];
        }

        return $this->buildSuccessResult($rawContent, $fallbackText, $originalName, $itemTitle, 'gemini:' . $this->model);
    }

    /**
     * Análise via OpenRouter (Qwen2.5-VL 72B Free) ou Groq Cloud (Llama 4 Scout Vision).
     */
    private function analyzeWithOpenAiCompatible(
        array $imagesBase64,
        array $imageMimes,
        string $extractedText,
        string $originalName,
        string $itemTitle,
        string $systemPrompt,
        string $jsonInstruction
    ): array {
        $isGroq  = ($this->provider === 'groq');
        $keyName = $isGroq ? 'GROQ_API_KEY' : 'OPENROUTER_API_KEY';

        if ($this->apiKey === '') {
            return [
                'success' => false,
                'error'   => "Chave {$keyName} não configurada no arquivo .env.",
            ];
        }

        $endpoint = $isGroq
            ? 'https://api.groq.com/openai/v1/chat/completions'
            : 'https://openrouter.ai/api/v1/chat/completions';

        $contentBlocks = [
            [
                'type' => 'text',
                'text' => $jsonInstruction,
            ],
        ];

        foreach (array_slice($imagesBase64, 0, 2) as $idx => $imgB64) {
            $mime = $imageMimes[$idx] ?? 'image/jpeg';
            $contentBlocks[] = [
                'type'      => 'image_url',
                'image_url' => [
                    'url' => "data:{$mime};base64,{$imgB64}",
                ],
            ];
        }

        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = [
                'role'    => 'system',
                'content' => $systemPrompt,
            ];
        }
        $messages[] = [
            'role'    => 'user',
            'content' => $contentBlocks,
        ];

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.1,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [
                'success' => false,
                'error'   => 'Falha de conexão com a API: ' . $curlErr,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if ($httpCode !== 200) {
            $errMsg = $decoded['error']['message'] ?? ("Erro HTTP {$httpCode} na API.");
            return [
                'success' => false,
                'error'   => $errMsg,
            ];
        }

        $rawContent = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
        if ($rawContent === '') {
            return [
                'success' => false,
                'error'   => 'A API não retornou conteúdo na análise.',
            ];
        }

        return $this->buildSuccessResult($rawContent, $extractedText, $originalName, $itemTitle, $this->provider . ':' . $this->model);
    }

    /**
     * Análise via Ollama Local (`qwen3-vl:8b`).
     */
    private function analyzeWithOllamaLocal(
        array $imagesBase64,
        string $extractedText,
        string $originalName,
        string $itemTitle,
        string $systemPrompt,
        string $jsonInstruction
    ): array {
        $userMessage = [
            'role'    => 'user',
            'content' => $jsonInstruction,
        ];

        if (! empty($imagesBase64)) {
            $userMessage['images'] = array_slice($imagesBase64, 0, 2);
        }

        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = [
                'role'    => 'system',
                'content' => $systemPrompt,
            ];
        }
        $messages[] = $userMessage;

        $payload = [
            'model'    => $this->model,
            'stream'   => false,
            'format'   => 'json',
            'messages' => $messages,
        ];

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [
                'success' => false,
                'error'   => 'Não foi possível conectar ao Ollama em ' . $this->baseUrl . '. Verifique se o Ollama está em execução ou configure GEMINI_API_KEY no .env. (' . $curlErr . ')',
            ];
        }

        $decoded = json_decode((string) $raw, true);

        if ($httpCode !== 200) {
            $apiError = $decoded['error'] ?? ("Erro HTTP {$httpCode} ao consultar o Ollama.");
            return [
                'success' => false,
                'error'   => (string) $apiError,
            ];
        }

        $rawContent = trim((string) ($decoded['message']['content'] ?? ''));
        if ($rawContent === '') {
            return [
                'success' => false,
                'error'   => 'O modelo não retornou conteúdo na análise.',
            ];
        }

        return $this->buildSuccessResult($rawContent, $extractedText, $originalName, $itemTitle, $this->model);
    }

    private function buildSuccessResult(
        string $rawContent,
        string $fallbackText,
        string $originalName,
        string $itemTitle,
        string $modelUsed
    ): array {
        $structured = $this->parseStructuredJson($rawContent, $fallbackText, $originalName, $itemTitle);

        $lines   = [];
        $lines[] = 'Tipo: ' . $structured['document_type'];

        if ($structured['document_number'] !== '' && $structured['document_number'] !== 'N/I') {
            $lines[] = 'Número Doc/Nota: ' . $structured['document_number'];
        }
        if ($structured['emitter_name'] !== '' && $structured['emitter_name'] !== 'N/I') {
            $emitterLine = 'Emitente: ' . $structured['emitter_name'];
            if ($structured['cnpj_cpf'] !== '' && $structured['cnpj_cpf'] !== 'N/I') {
                $emitterLine .= ' (' . $structured['cnpj_cpf'] . ')';
            }
            $lines[] = $emitterLine;
        } elseif ($structured['cnpj_cpf'] !== '' && $structured['cnpj_cpf'] !== 'N/I') {
            $lines[] = 'CNPJ/CPF: ' . $structured['cnpj_cpf'];
        }
        if ($structured['issue_date'] !== '' && $structured['issue_date'] !== 'N/I') {
            $lines[] = 'Data: ' . $structured['issue_date'];
        }
        if ((float) $structured['total_value'] > 0) {
            $lines[] = 'Valor Identificado: R$ ' . number_format((float) $structured['total_value'], 2, ',', '.');
        }
        if ($structured['compatibility'] !== '') {
            $lines[] = 'Compatibilidade: ' . $structured['compatibility'];
        }
        if ($structured['summary'] !== '') {
            $lines[] = "\nResumo: " . $structured['summary'];
        }

        return [
            'success'    => true,
            'model'      => $modelUsed,
            'content'    => implode("\n", $lines),
            'structured' => $structured,
        ];
    }

    private function parseStructuredJson(string $rawContent, string $fallbackText, string $originalName, string $itemTitle): array
    {
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawContent));
        $data  = json_decode((string) $clean, true);

        if (! is_array($data)) {
            if (preg_match('/\{.*\}/s', $rawContent, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        if (! is_array($data)) {
            $data = [];
        }

        $rawValue = $data['total_value'] ?? 0;
        if (is_string($rawValue)) {
            $rawValue = str_replace(['R$', ' '], '', $rawValue);
            if (str_contains($rawValue, ',') && str_contains($rawValue, '.')) {
                $rawValue = str_replace('.', '', $rawValue);
                $rawValue = str_replace(',', '.', $rawValue);
            } elseif (str_contains($rawValue, ',')) {
                $rawValue = str_replace(',', '.', $rawValue);
            }
        }
        $floatVal = is_numeric($rawValue) ? (float) $rawValue : 0.0;

        if ($floatVal <= 0 && $fallbackText !== '') {
            if (preg_match('/R\$\s*([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2}|[0-9]+,[0-9]{2})/i', $fallbackText, $valMatch)) {
                $normalized = str_replace('.', '', $valMatch[1]);
                $normalized = str_replace(',', '.', $normalized);
                $floatVal   = (float) $normalized;
            }
        }

        $cleanField = static function ($val): string {
            $v = trim((string) ($val ?? ''));
            if (in_array(mb_strtolower($v), ['n/i', 'n/a', 'null', 'none', 'não informado', 'nao informado', 'não aplicável', '-'], true)) {
                return '';
            }
            return $v;
        };

        return [
            'document_type'   => $cleanField($data['document_type'] ?? '') ?: 'Documento Indexado',
            'document_number' => $cleanField($data['document_number'] ?? ''),
            'emitter_name'    => $cleanField($data['emitter_name'] ?? ''),
            'cnpj_cpf'        => $cleanField($data['cnpj_cpf'] ?? ''),
            'issue_date'      => $cleanField($data['issue_date'] ?? ''),
            'total_value'     => round($floatVal, 2),
            'compatibility'   => $cleanField($data['compatibility'] ?? '') ?: 'Compatível',
            'summary'         => trim((string) ($data['summary'] ?? $rawContent)),
            'raw_response'    => $rawContent,
        ];
    }

    private function extractFromPdf(string $pdfPath): array
    {
        $raw    = (string) @file_get_contents($pdfPath);
        $images = [];
        $texts  = [];

        if ($raw === '') {
            return ['text' => '', 'images' => []];
        }

        if (preg_match_all('/\/Subtype\s*\/Image[^>]*stream\r?\n(.*?)\r?\nendstream/s', $raw, $imgMatches)) {
            foreach ($imgMatches[1] as $bin) {
                if (str_starts_with($bin, "\xFF\xD8\xFF")) {
                    $images[] = base64_encode($bin);
                    if (count($images) >= 2) {
                        break;
                    }
                }
            }
        }

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streamMatches)) {
            foreach ($streamMatches[1] as $streamData) {
                $decoded = @gzuncompress($streamData);
                if ($decoded === false) {
                    $decoded = @gzinflate($streamData);
                }
                $source = ($decoded !== false) ? $decoded : $streamData;

                if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)\s*Tj/s', $source, $tjMatches)) {
                    foreach ($tjMatches[1] as $t) {
                        $texts[] = $this->cleanPdfString($t);
                    }
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $source, $tjArrMatches)) {
                    foreach ($tjArrMatches[1] as $arr) {
                        if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)/s', $arr, $subMatches)) {
                            $line = '';
                            foreach ($subMatches[1] as $st) {
                                $line .= $this->cleanPdfString($st);
                            }
                            if ($line !== '') {
                                $texts[] = $line;
                            }
                        }
                    }
                }
            }
        }

        $joinedText = trim(preg_replace('/\s+/', ' ', implode(' ', $texts)));

        return [
            'text'   => $joinedText,
            'images' => $images,
        ];
    }

    private function cleanPdfString(string $str): string
    {
        $str   = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", "\r", "\t", '(', ')', '\\'], $str);
        $clean = @mb_convert_encoding($str, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        return preg_replace('/[^\P{C}\n\t]+/u', '', (string) $clean);
    }

    private function extractFromZip(string $zipPath): array
    {
        $images = [];
        $texts  = [];

        if (! class_exists(ZipArchive::class)) {
            return ['text' => '', 'images' => []];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $fileList = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name       = (string) $zip->getNameIndex($i);
                $fileList[] = $name;
                $ext        = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png'], true) && count($images) < 2) {
                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        $images[] = base64_encode($content);
                    }
                } elseif (in_array($ext, ['xml', 'txt', 'csv', 'json'], true)) {
                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        $texts[] = "Arquivo {$name}: " . strip_tags(mb_substr($content, 0, 3000));
                    }
                }
            }
            $zip->close();
            if (! empty($fileList)) {
                array_unshift($texts, 'Arquivos contidos no ZIP: ' . implode(', ', $fileList));
            }
        }

        return [
            'text'   => implode("\n", $texts),
            'images' => $images,
        ];
    }
}
