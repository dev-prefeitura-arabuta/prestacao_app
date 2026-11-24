<?php

namespace App\Services;

use App\Models\Accountability;
use Illuminate\Support\Facades\Http;
use Throwable;

class SpreadsheetAiExtractorService
{
    /**
     * Lê todos os documentos anexados (PDF + OCR local + ZIP) e usa IA (Groq/Gemini)
     * com fallback em PHP para gerar lançamentos estruturados na planilha (receita, aporte, despesa).
     *
     * @param array<string, mixed>             $accountability
     * @param array<int, array<string, mixed>> $attachments
     * @return array{success: bool, entries: array<int, array<string, mixed>>, engine: string, error: ?string}
     */
    public function extractEntries(array $accountability, array $attachments): array
    {
        if (empty($attachments)) {
            return [
                'success' => false,
                'entries' => [],
                'engine'  => 'Nenhum',
                'error'   => 'Esta prestação de contas ainda não possui documentos anexados.',
            ];
        }

        $allItems    = Accountability::getItems();
        $localReader = new ExtractiveAssistantService();
        $docs        = $localReader->analyzeAttachments($attachments, $allItems);

        // Tenta extração estruturada via IA (Groq ou Gemini configurado no .env)
        $aiResult = $this->extractWithAi($accountability, $docs);
        if (! empty($aiResult['success']) && ! empty($aiResult['entries'])) {
            return $aiResult;
        }

        // Fallback determinístico em PHP + OCR (caso IA esteja offline ou sem chave)
        $fallbackEntries = $this->buildFallbackEntries($docs);

        return [
            'success' => ! empty($fallbackEntries),
            'entries' => $fallbackEntries,
            'engine'  => 'OCR Local + Extração PHP (fallback)',
            'error'   => empty($fallbackEntries) ? ($aiResult['error'] ?? 'Não foi possível extrair dados financeiros dos anexos.') : null,
        ];
    }

    /**
     * @param array<string, mixed>             $accountability
     * @param array<int, array<string, mixed>> $docs
     * @return array{success: bool, entries: array<int, array<string, mixed>>, engine: string, error: ?string}
     */
    private function extractWithAi(array $accountability, array $docs): array
    {
        $summarySvc = new GeminiSummaryService();
        if (! $summarySvc->isEnabled()) {
            return ['success' => false, 'entries' => [], 'engine' => 'Local', 'error' => 'IA não configurada no .env.'];
        }

        $provider = $summarySvc->getProvider();
        $apiKey   = $summarySvc->getApiKey();
        $model    = $summarySvc->getModel();
        $timeout  = max(20, min(90, (int) env('AI_TIMEOUT', 60)));

        $prompt = $this->buildJsonPrompt($accountability, $docs);

        try {
            $rawJsonText = '';
            $engineName  = '';

            if ($provider === 'groq') {
                $resp = Http::timeout($timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ])
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model'       => $model,
                        'temperature' => 0.1,
                        'max_tokens'  => 2400,
                        'messages'    => [
                            [
                                'role'    => 'system',
                                'content' => 'Você é um extrator contábil de prestação de contas públicas. Retorne EXCLUSIVAMENTE um JSON válido (sem markdown, sem comentários) contendo um array de objetos com os lançamentos extraídos dos documentos.',
                            ],
                            [
                                'role'    => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);

                if (! $resp->successful()) {
                    return ['success' => false, 'entries' => [], 'engine' => 'Groq API', 'error' => 'Groq HTTP ' . $resp->status()];
                }
                $rawJsonText = (string) ($resp->json()['choices'][0]['message']['content'] ?? '');
                $engineName  = "Groq API ({$model}) + OCR Local";
            } else {
                $isVertex = str_starts_with($apiKey, 'AQ.');
                $url      = $isVertex
                    ? sprintf('https://aiplatform.googleapis.com/v1/publishers/google/models/%s:generateContent?key=%s', urlencode($model), urlencode($apiKey))
                    : sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', urlencode($model), urlencode($apiKey));

                $resp = Http::timeout($timeout)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $prompt]]],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.1,
                            'maxOutputTokens' => 2400,
                        ],
                    ]);

                if (! $resp->successful()) {
                    return ['success' => false, 'entries' => [], 'engine' => 'Gemini API', 'error' => 'Gemini HTTP ' . $resp->status()];
                }
                $rawJsonText = (string) ($resp->json()['candidates'][0]['content']['parts'][0]['text'] ?? '');
                $engineName  = "Gemini API ({$model}) + OCR Local";
            }

            $parsed = $this->parseJsonEntries($rawJsonText);
            if (empty($parsed)) {
                return ['success' => false, 'entries' => [], 'engine' => $engineName, 'error' => 'A IA não retornou lançamentos válidos em JSON.'];
            }

            // Se todos os documentos enviados pertencem a um único item, garante o item_number correto
            $distinctItems = array_values(array_unique(array_filter(array_map(static fn ($d) => (int) ($d['item_number'] ?? 0), $docs))));
            if (count($distinctItems) === 1) {
                foreach ($parsed as &$pRow) {
                    $pRow['item_number'] = $distinctItems[0];
                }
                unset($pRow);
            }

            // Vincula o attachment_id correspondente (pelo nome do arquivo em notes ou quando há 1 anexo)
            foreach ($parsed as &$pRow) {
                $pRow['attachment_id'] = $this->resolveAttachmentId($pRow, $docs);
            }
            unset($pRow);

            return [
                'success' => true,
                'entries' => $parsed,
                'engine'  => $engineName,
                'error'   => null,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'entries' => [], 'engine' => 'IA', 'error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed>             $accountability
     * @param array<int, array<string, mixed>> $docs
     */
    private function buildJsonPrompt(array $accountability, array $docs): string
    {
        $title = (string) ($accountability['title'] ?? 'Prestação de Contas');

        $lines = [];
        $lines[] = "Analise os documentos anexados da prestação de contas \"{$title}\" e extraia TODOS os dados financeiros e documentais disponíveis para preencher uma planilha contábil.";
        $lines[] = 'Classifique cada lançamento em `entry_type` usando EXATAMENTE um destes 3 valores:';
        $lines[] = '- "receita": repasses, transferências de convênio, rendimentos financeiros, créditos recebidos.';
        $lines[] = '- "aporte": aporte de contrapartida municipal/estadual (especialmente itens 11 e 12 ou menção a contrapartida/aporte).';
        $lines[] = '- "despesa": notas fiscais, empenhos, pagamentos, encargos tributários, medições, devoluções.';
        $lines[] = '';
        $lines[] = 'Retorne APENAS um JSON válido no formato de array de objetos (1 ou mais itens por documento que contenha dados relevantes):';
        $lines[] = '[';
        $lines[] = '  {';
        $lines[] = '    "entry_date": "YYYY-MM-DD ou vazio",';
        $lines[] = '    "item_number": 3,';
        $lines[] = '    "document_number": "Ex: NF 1234 ou Empenho 56",';
        $lines[] = '    "supplier_name": "Nome do fornecedor, órgão ou banco",';
        $lines[] = '    "cnpj_cpf": "00.000.000/0000-00 ou vazio",';
        $lines[] = '    "description": "Descrição clara do lançamento extraída do documento",';
        $lines[] = '    "entry_type": "receita|aporte|despesa",';
        $lines[] = '    "amount": 1250.50,';
        $lines[] = '    "notes": "Origem: nome_do_arquivo.pdf"';
        $lines[] = '  }';
        $lines[] = ']';
        $lines[] = '';
        $lines[] = 'REGRAS: Mantenha exatamente o `item_number` informado em `[Item XX]`. Nunca invente valores que não existam no texto (se não houver valor no documento, use 0). Use ponto decimal em `amount`. Para NF-e, prefira o VALOR TOTAL DA NOTA.';
        $lines[] = '';
        $lines[] = '=== DOCUMENTOS ANEXADOS (PDF + OCR LOCAL) ===';

        $budget = 14000;
        $used   = 0;

        foreach ($docs as $doc) {
            $itemNum = (int) ($doc['item_number'] ?? 0);
            $lines[] = sprintf(
                "\n[Item %02d — %s] Arquivo: %s (%s)",
                $itemNum,
                (string) ($doc['item_title'] ?? ''),
                (string) ($doc['original_name'] ?? ''),
                (string) ($doc['doc_type'] ?? '')
            );

            $hints = [];
            if (! empty($doc['values'])) {
                $hints[] = 'Valores detectados: R$ ' . implode(', R$ ', array_map(static fn ($v) => number_format((float) $v, 2, ',', '.'), array_slice($doc['values'], 0, 5)));
            }
            if (! empty($doc['cnpjs'])) {
                $hints[] = 'CNPJs: ' . implode(', ', $doc['cnpjs']);
            }
            if (! empty($doc['cpfs'])) {
                $hints[] = 'CPFs: ' . implode(', ', $doc['cpfs']);
            }
            if (! empty($doc['dates'])) {
                $hints[] = 'Datas: ' . implode(', ', $doc['dates']);
            }
            if (! empty($doc['doc_numbers'])) {
                $hints[] = 'Nº Doc: ' . implode(', ', $doc['doc_numbers']);
            }
            if (! empty($hints)) {
                $lines[] = 'Metadados PHP: ' . implode(' | ', $hints);
            }

            $rawText = trim((string) ($doc['text'] ?? ''));
            if ($rawText !== '' && $used < $budget) {
                $slice = mb_substr($rawText, 0, min(1500, $budget - $used));
                $used += mb_strlen($slice);
                $lines[] = 'Texto: ' . preg_replace('/\s+/u', ' ', $slice);
            } else {
                $lines[] = 'Texto: (sem texto extraível — use os metadados/nome do arquivo se pertinente)';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseJsonEntries(string $raw): array
    {
        $clean = trim($raw);
        // Remove blocos ```json ... ``` se o modelo incluir
        if (preg_match('/```(?:json)?\s*(\[[\s\S]*?\])\s*```/i', $clean, $m)) {
            $clean = $m[1];
        } elseif (preg_match('/(\[[\s\S]*\])/', $clean, $m)) {
            $clean = $m[1];
        }

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = strtolower(trim((string) ($row['entry_type'] ?? 'despesa')));
            if (! in_array($type, ['receita', 'aporte', 'despesa'], true)) {
                $type = 'despesa';
            }

            $date = $this->normalizeDate((string) ($row['entry_date'] ?? ''));
            $amt  = $this->normalizeAmount($row['amount'] ?? 0);
            $item = isset($row['item_number']) && (int) $row['item_number'] >= 1 && (int) $row['item_number'] <= 24
                ? (int) $row['item_number']
                : null;

            $desc = trim((string) ($row['description'] ?? ''));
            $sup  = trim((string) ($row['supplier_name'] ?? ''));
            $docN = trim((string) ($row['document_number'] ?? ''));

            if ($desc === '' && $sup === '' && $docN === '' && $amt <= 0) {
                continue;
            }

            $out[] = [
                'item_number'     => $item,
                'entry_date'      => $date,
                'document_number' => mb_substr($docN, 0, 100) ?: null,
                'supplier_name'   => mb_substr($sup, 0, 200) ?: null,
                'cnpj_cpf'        => mb_substr(trim((string) ($row['cnpj_cpf'] ?? '')), 0, 30) ?: null,
                'description'     => mb_substr($desc, 0, 500) ?: null,
                'entry_type'      => $type,
                'amount'          => $amt,
                'notes'           => mb_substr(trim((string) ($row['notes'] ?? 'Extraído via IA')), 0, 500),
            ];
        }

        return $out;
    }

    /**
     * Fallback em PHP puro + OCR quando a IA não estiver disponível.
     *
     * @param array<int, array<string, mixed>> $docs
     * @return array<int, array<string, mixed>>
     */
    private function buildFallbackEntries(array $docs): array
    {
        $out = [];
        foreach ($docs as $doc) {
            $itemNum = (int) ($doc['item_number'] ?? 0);
            $type    = 'despesa';
            if (in_array($itemNum, [11, 12], true)) {
                $type = 'aporte';
            } elseif (in_array($itemNum, [13, 14], true)) {
                $type = 'receita';
            }

            $date = ! empty($doc['dates'][0]) ? $this->normalizeDate((string) $doc['dates'][0]) : null;
            $cnpj = ! empty($doc['cnpjs'][0]) ? (string) $doc['cnpjs'][0] : (! empty($doc['cpfs'][0]) ? (string) $doc['cpfs'][0] : null);
            $docN = ! empty($doc['doc_numbers'][0]) ? (string) $doc['doc_numbers'][0] : null;
            $amt  = (float) ($doc['main_value'] ?? 0);
            $orig = (string) ($doc['original_name'] ?? 'documento');

            $out[] = [
                'item_number'     => ($itemNum >= 1 && $itemNum <= 24) ? $itemNum : null,
                'attachment_id'   => ! empty($doc['id']) ? (int) $doc['id'] : null,
                'entry_date'      => $date,
                'document_number' => $docN ? mb_substr($docN, 0, 100) : null,
                'supplier_name'   => null,
                'cnpj_cpf'        => $cnpj ? mb_substr($cnpj, 0, 30) : null,
                'description'     => mb_substr((string) ($doc['item_title'] ?: $orig), 0, 500),
                'entry_type'      => $type,
                'amount'          => round($amt, 2),
                'notes'           => mb_substr('Extraído localmente de: ' . $orig, 0, 500),
            ];
        }

        return $out;
    }

    /**
     * Tenta vincular um lançamento extraído ao ID real do anexo que o originou.
     *
     * @param array<string, mixed>             $entry
     * @param array<int, array<string, mixed>> $docs
     */
    private function resolveAttachmentId(array $entry, array $docs): ?int
    {
        if (count($docs) === 1 && ! empty($docs[0]['id'])) {
            return (int) $docs[0]['id'];
        }

        $notes = mb_strtolower((string) ($entry['notes'] ?? ''), 'UTF-8');
        foreach ($docs as $d) {
            $orig = mb_strtolower(trim((string) ($d['original_name'] ?? '')), 'UTF-8');
            if ($orig !== '' && $notes !== '' && str_contains($notes, $orig) && ! empty($d['id'])) {
                return (int) $d['id'];
            }
        }

        $entryItem = (int) ($entry['item_number'] ?? 0);
        if ($entryItem > 0) {
            $sameItem = array_values(array_filter($docs, static fn ($d) => (int) ($d['item_number'] ?? 0) === $entryItem && ! empty($d['id'])));
            if (count($sameItem) === 1) {
                return (int) $sameItem[0]['id'];
            }
        }

        return null;
    }

    private function normalizeDate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw)) {
            return $raw;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }

    private function normalizeAmount(mixed $raw): float
    {
        if (is_int($raw) || is_float($raw)) {
            return round(max(0.0, (float) $raw), 2);
        }
        $s = str_replace(['R$', ' '], '', trim((string) $raw));
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }
        return is_numeric($s) ? round(max(0.0, (float) $s), 2) : 0.0;
    }
}
