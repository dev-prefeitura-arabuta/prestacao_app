<?php

namespace App\Services;

use App\Models\WhatsappConfig;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenWaService
{
    /**
     * Carrega a configuração do WhatsApp (OpenWA) do banco de dados com defaults seguros.
     *
     * @return array<string, mixed>
     */
    public function loadConfig(): array
    {
        $defaults = [
            'api_url'              => 'http://localhost:2785',
            'api_key'              => '',
            'session_id'           => '',
            'session_name'         => 'prestacao-contas',
            'engine_type'          => 'whatsapp-web.js',
            'default_country_code' => '55',
            'timeout'              => 15,
            'webhook_url'          => '',
            'status'               => 0,
            'saved'                => false,
        ];

        $localKeyFile = base_path('services/openwa/data/.api-key');
        $detectedKey  = is_file($localKeyFile) ? trim((string) @file_get_contents($localKeyFile)) : '';

        try {
            $row = WhatsappConfig::first();
            if ($row !== null) {
                $data          = array_merge($defaults, $row->toArray());
                $data['saved'] = true;
                if ($detectedKey !== '') {
                    $data['detected_local_key'] = $detectedKey;
                }
                return $data;
            }
        } catch (Throwable) {
            // Tabela ainda não migrada ou indisponível
        }

        if ($detectedKey !== '') {
            $defaults['detected_local_key'] = $detectedKey;
        }

        return $defaults;
    }

    /**
     * Verifica se o gateway OpenWA está online e acessível.
     *
     * @return array{online: bool, message: string, sessions: array<int, array<string, mixed>>}
     */
    public function getStatus(?array $config = null): array
    {
        $cfg     = $config ?? $this->loadConfig();
        $baseUrl = rtrim((string) ($cfg['api_url'] ?? 'http://localhost:2785'), '/');
        $apiKey  = trim((string) ($cfg['api_key'] ?? ''));
        $timeout = max(3, min(15, (int) ($cfg['timeout'] ?? 8)));

        if ($baseUrl === '') {
            return ['online' => false, 'message' => 'URL do OpenWA não configurada.', 'sessions' => []];
        }

        try {
            $healthResp = Http::timeout($timeout)->get($baseUrl . '/api/health');
            if (! $healthResp->successful()) {
                $healthResp = Http::timeout($timeout)->get($baseUrl . '/health');
            }

            if (! $healthResp->successful()) {
                return [
                    'online'   => false,
                    'message'  => 'Gateway respondeu com HTTP ' . $healthResp->status(),
                    'sessions' => [],
                ];
            }

            $sessions = [];
            if ($apiKey !== '') {
                $sessResp = Http::timeout($timeout)
                    ->withHeaders(['X-API-Key' => $apiKey])
                    ->get($baseUrl . '/api/sessions');

                if ($sessResp->successful()) {
                    $json = $sessResp->json();
                    if (is_array($json)) {
                        $sessions = isset($json['data']) && is_array($json['data'])
                            ? $json['data']
                            : (array_is_list($json) ? $json : []);
                    }
                }
            }

            return [
                'online'   => true,
                'message'  => 'OpenWA online em ' . $baseUrl,
                'sessions' => $sessions,
            ];
        } catch (Throwable) {
            return [
                'online'   => false,
                'message'  => 'Não foi possível conectar ao OpenWA em ' . $baseUrl . '. Verifique se o serviço está em execução.',
                'sessions' => [],
            ];
        }
    }

    /**
     * Cria ou inicia uma sessão no OpenWA e retorna o status/ID da sessão.
     *
     * @return array{success: bool, session_id?: string, status?: string, qr?: ?string, error?: string}
     */
    public function ensureAndStartSession(?string $sessionId = null, ?string $sessionName = null): array
    {
        $cfg     = $this->loadConfig();
        $baseUrl = rtrim((string) ($cfg['api_url'] ?? 'http://localhost:2785'), '/');
        $apiKey  = trim((string) ($cfg['api_key'] ?: ($cfg['detected_local_key'] ?? '')));
        $timeout = max(5, (int) ($cfg['timeout'] ?? 15));

        if ($apiKey === '') {
            return ['success' => false, 'error' => 'Configure a API Key do OpenWA antes de iniciar a sessão.'];
        }

        $targetId   = trim((string) ($sessionId ?: ($cfg['session_id'] ?? '')));
        $targetName = trim((string) ($sessionName ?: ($cfg['session_name'] ?? 'prestacao-contas')));
        if ($targetName === '') {
            $targetName = 'prestacao-contas';
        }

        try {
            $client = Http::timeout($timeout)->withHeaders([
                'X-API-Key' => $apiKey,
                'Accept'    => 'application/json',
            ]);

            if ($targetId === '') {
                // Reaproveita sessão já existente com o mesmo nome (evita conflito 409)
                $listResp = $client->get($baseUrl . '/api/sessions');
                if ($listResp->successful()) {
                    $listJson = $listResp->json();
                    $items    = is_array($listJson)
                        ? (isset($listJson['data']) && is_array($listJson['data']) ? $listJson['data'] : (array_is_list($listJson) ? $listJson : []))
                        : [];
                    foreach ($items as $item) {
                        if (is_array($item) && (($item['name'] ?? '') === $targetName) && ! empty($item['id'])) {
                            $targetId = (string) $item['id'];
                            break;
                        }
                    }
                }
            }

            if ($targetId === '') {
                $createResp = $client->post($baseUrl . '/api/sessions', [
                    'name' => $targetName,
                ]);

                if (! $createResp->successful()) {
                    return [
                        'success' => false,
                        'error'   => 'Falha ao criar sessão no OpenWA (HTTP ' . $createResp->status() . '): ' . $createResp->body(),
                    ];
                }

                $created  = $createResp->json();
                $targetId = (string) ($created['id'] ?? $created['data']['id'] ?? $created['sessionId'] ?? '');
                if ($targetId === '') {
                    return ['success' => false, 'error' => 'OpenWA não retornou o ID da sessão criada.'];
                }
            }

            // Garante que a configuração e o session_id fiquem salvos no banco
            $row = WhatsappConfig::first();
            if ($row) {
                $update = ['session_id' => $targetId, 'session_name' => $targetName];
                if (trim((string) $row->api_key) === '' && $apiKey !== '') {
                    $update['api_key'] = $apiKey;
                }
                $row->update($update);
            } else {
                WhatsappConfig::create([
                    'api_url'              => $baseUrl,
                    'api_key'              => $apiKey,
                    'session_id'           => $targetId,
                    'session_name'         => $targetName,
                    'engine_type'          => (string) ($cfg['engine_type'] ?? 'whatsapp-web.js'),
                    'default_country_code' => (string) ($cfg['default_country_code'] ?? '55'),
                    'timeout'              => $timeout,
                    'status'               => 1,
                ]);
            }

            // Inicia a sessão
            $client->post($baseUrl . '/api/sessions/' . urlencode($targetId) . '/start');

            // Busca o QR Code (se já estiver pronto; caso contrário, o frontend faz polling automático)
            $cfg['api_key']    = $apiKey;
            $cfg['session_id'] = $targetId;
            $qrData            = $this->fetchQrCode($targetId, $cfg);

            return [
                'success'    => true,
                'session_id' => $targetId,
                'status'     => $qrData['status'] ?? 'initializing',
                'qr'         => $qrData['qr'] ?? null,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Erro ao comunicar com o OpenWA: ' . $e->getMessage()];
        }
    }

    /**
     * Consulta o QR Code e status atual de uma sessão no OpenWA.
     *
     * @return array{success: bool, status?: string, qr?: ?string, error?: string}
     */
    public function fetchQrCode(string $sessionId, ?array $config = null): array
    {
        $cfg     = $config ?? $this->loadConfig();
        $baseUrl = rtrim((string) ($cfg['api_url'] ?? 'http://localhost:2785'), '/');
        $apiKey  = trim((string) ($cfg['api_key'] ?: ($cfg['detected_local_key'] ?? '')));
        $timeout = max(5, (int) ($cfg['timeout'] ?? 15));

        if ($apiKey === '' || trim($sessionId) === '') {
            return ['success' => false, 'error' => 'Clique primeiro em "Iniciar Sessão / Gerar QR" para criar a sessão.'];
        }

        try {
            $client = Http::timeout($timeout)->withHeaders([
                'X-API-Key' => $apiKey,
                'Accept'    => 'application/json',
            ]);

            $statusResp = $client->get($baseUrl . '/api/sessions/' . urlencode($sessionId));
            $statusData = $statusResp->successful() ? $statusResp->json() : [];
            $sessStatus = (string) ($statusData['status'] ?? $statusData['data']['status'] ?? 'unknown');
            $lastError  = (string) ($statusData['lastError'] ?? $statusData['data']['lastError'] ?? '');

            if ($sessStatus === 'failed') {
                return [
                    'success' => false,
                    'status'  => 'failed',
                    'error'   => 'A sessão falhou no OpenWA' . ($lastError !== '' ? ': ' . $lastError : '.') . ' Clique em "Iniciar Sessão / Gerar QR" novamente.',
                ];
            }

            $qr = null;
            $qrResp = $client->get($baseUrl . '/api/sessions/' . urlencode($sessionId) . '/qr');
            if ($qrResp->successful()) {
                $contentType = (string) $qrResp->header('Content-Type');
                if (str_contains($contentType, 'image/')) {
                    $qr = 'data:' . $contentType . ';base64,' . base64_encode($qrResp->body());
                } else {
                    $json = $qrResp->json();
                    $raw  = $json['qrCode'] ?? $json['qr'] ?? $json['data']['qrCode'] ?? $json['data']['qr'] ?? null;
                    if (is_string($raw) && $raw !== '') {
                        $qr = $raw;
                    }
                }
            }

            return [
                'success' => true,
                'status'  => $sessStatus,
                'qr'      => $qr,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Erro ao consultar QR Code: ' . $e->getMessage()];
        }
    }

    /**
     * Envia uma mensagem de texto pelo WhatsApp via OpenWA.
     *
     * @return array{success: bool, error: ?string}
     */
    public function sendText(string $phone, string $text): array
    {
        $cfg = $this->loadConfig();

        if ((int) ($cfg['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error'   => 'A integração com WhatsApp (OpenWA) está desativada nas configurações.',
            ];
        }

        $baseUrl   = rtrim((string) ($cfg['api_url'] ?? 'http://localhost:2785'), '/');
        $apiKey    = trim((string) ($cfg['api_key'] ?? ''));
        $sessionId = trim((string) ($cfg['session_id'] ?? ''));
        $timeout   = max(5, (int) ($cfg['timeout'] ?? 15));

        if ($baseUrl === '' || $apiKey === '' || $sessionId === '') {
            return [
                'success' => false,
                'error'   => 'Configuração do OpenWA incompleta (verifique URL, API Key e ID da Sessão em Configurações > WhatsApp).',
            ];
        }

        $chatId = $this->formatChatId($phone, (string) ($cfg['default_country_code'] ?? '55'));
        if ($chatId === '') {
            return ['success' => false, 'error' => 'Número de telefone inválido.'];
        }

        try {
            $resp = Http::timeout($timeout)
                ->withHeaders([
                    'X-API-Key'    => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post($baseUrl . '/api/sessions/' . urlencode($sessionId) . '/messages/send-text', [
                    'chatId' => $chatId,
                    'text'   => $text,
                ]);

            if (! $resp->successful()) {
                return [
                    'success' => false,
                    'error'   => 'OpenWA retornou erro HTTP ' . $resp->status() . ': ' . $resp->body(),
                ];
            }

            return ['success' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Falha ao enviar mensagem via OpenWA: ' . $e->getMessage()];
        }
    }

    /**
     * Normaliza um número de telefone para o formato chatId do OpenWA (ex: 5549999999999@c.us).
     */
    public function formatChatId(string $phone, string $countryCode = '55'): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        if (str_contains($phone, '@')) {
            return $phone;
        }

        // Antes, um número de 11 dígitos com DDD 55 (ex: 55 99999-9999) não recebia o DDI
        // porque "já começava com 55". Phone::toInternational trata DDD e DDI separadamente.
        $digits = Phone::toInternational($phone, $countryCode);

        return $digits === null ? '' : $digits . '@c.us';
    }
}
