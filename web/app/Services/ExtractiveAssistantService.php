<?php

namespace App\Services;

use Throwable;
use ZipArchive;

/**
 * Assistente documental e resumidor extrativo 100% em PHP.
 * Não consome tokens, não faz chamadas externas e opera com baixíssimo uso de CPU/RAM.
 */
class ExtractiveAssistantService
{
    private const MAX_FILE_BYTES = 15 * 1024 * 1024; // 15 MB por arquivo
    private const MAX_OCR_PDF_IMAGES = 3;
    private const MAX_OCR_ZIP_IMAGES = 3;

    /** @var string|false|null */
    private string|false|null $tesseractBinary = null;

    /** @var array<string, true> */
    private array $stopwords;

    /** @var array<string, float> */
    private array $domainBoosts = [
        'valor'        => 2.2,
        'total'        => 2.2,
        'nota'         => 1.8,
        'fiscal'       => 2.0,
        'empenho'      => 2.0,
        'cnpj'         => 1.9,
        'cpf'          => 1.7,
        'contrato'     => 1.8,
        'objeto'       => 2.0,
        'obra'         => 1.6,
        'medicao'      => 1.8,
        'medição'      => 1.8,
        'saldo'        => 1.8,
        'receita'      => 1.7,
        'despesa'      => 1.7,
        'pagamento'    => 1.8,
        'fornecedor'   => 1.7,
        'emissao'      => 1.5,
        'emissão'      => 1.5,
        'tributo'      => 1.6,
        'encargo'      => 1.6,
        'parecer'      => 1.8,
        'conclusao'    => 2.0,
        'conclusão'    => 2.0,
        'execucao'     => 1.7,
        'execução'     => 1.7,
        'contrapartida'=> 1.8,
        'recurso'      => 1.6,
        'aprovado'     => 1.7,
        'regular'      => 1.7,
        'pendencia'    => 1.8,
        'pendência'    => 1.8,
    ];

    public function __construct()
    {
        $words = [
            'a', 'o', 'os', 'as', 'um', 'uma', 'uns', 'umas', 'de', 'do', 'da', 'dos', 'das',
            'em', 'no', 'na', 'nos', 'nas', 'por', 'para', 'com', 'sem', 'sob', 'sobre', 'entre',
            'que', 'quem', 'qual', 'quais', 'como', 'quando', 'onde', 'porque', 'pois', 'mas',
            'ou', 'e', 'se', 'nao', 'não', 'sim', 'mais', 'menos', 'muito', 'muita', 'tambem',
            'também', 'ja', 'já', 'ainda', 'ate', 'até', 'foi', 'ser', 'sao', 'são', 'esta',
            'está', 'estao', 'estão', 'este', 'esta', 'esse', 'essa', 'isso', 'isto', 'aquele',
            'aquela', 'pelo', 'pela', 'pelos', 'pelas', 'ao', 'aos', 'à', 'às', 'seu', 'sua',
            'seus', 'suas', 'nosso', 'nossa', 'deve', 'devem', 'pode', 'podem', 'ter', 'tem',
            'têm', 'haver', 'ha', 'há', 'cada', 'todo', 'toda', 'todos', 'todas', 'outro', 'outra',
        ];
        $this->stopwords = array_fill_keys($words, true);
    }

    /**
     * Responde a uma pergunta ou comando sobre uma prestação de contas específica.
     *
     * @param array<string, mixed>         $accountability
     * @param array<int, string>           $enabledItems
     * @param array<int, string>           $itemStatuses
     * @param array<int, string>           $itemReasons
     * @param array<int, array<string, mixed>> $attachments
     * @return array<string, mixed>
     */
    public function answer(
        string $question,
        array $accountability,
        array $enabledItems,
        array $itemStatuses,
        array $itemReasons,
        array $attachments
    ): array {
        $startedAt = microtime(true);
        $rawQuery  = trim($question);
        $normQuery = $this->normalizeForMatch($rawQuery);

        $analyzedDocs = [];
        foreach ($attachments as $att) {
            $itemNum          = (int) ($att['item_number'] ?? 0);
            $itemTitle        = rtrim((string) ($enabledItems[$itemNum] ?? "Item {$itemNum}"), ';.');
            $analyzedDocs[]   = $this->analyzeFile($att, $itemTitle);
        }

        $stats       = $this->buildOverviewStats($enabledItems, $itemStatuses, $analyzedDocs);
        $validations = $this->runPhpValidations($accountability, $enabledItems, $itemStatuses, $analyzedDocs);

        // 1. Pergunta sobre um item específico (ex.: "item 3", "resumir item 12")
        if (preg_match('/\bitem\s*#?\s*(\d{1,2})\b/u', $normQuery, $m)) {
            $targetItem = (int) $m[1];
            $reply      = $this->formatItemReply($targetItem, $enabledItems, $itemStatuses, $itemReasons, $analyzedDocs);
            $intent     = 'item';
        } elseif ($this->matchesAny($normQuery, ['validar', 'validacao', 'validacoes', 'auditoria', 'inconsistencia', 'inconsistencias', 'conferencia', 'erros'])) {
            $reply  = $this->formatValidationsReply($validations);
            $intent = 'validations';
        } elseif ($normQuery === '' || $this->matchesAny($normQuery, ['resumo', 'resumir', 'geral', 'visao geral', 'consolidado', 'sintese', 'tudo'])) {
            $reply  = $this->formatGeneralSummary($accountability, $enabledItems, $itemStatuses, $itemReasons, $analyzedDocs, $stats, $validations);
            $intent = 'summary';
        } elseif ($this->matchesAny($normQuery, ['valor', 'valores', 'dinheiro', 'gasto', 'gastos', 'total', 'soma', 'somar', 'r$', 'reais', 'financeiro'])) {
            $reply  = $this->formatValuesReply($analyzedDocs, $stats);
            $intent = 'values';
        } elseif ($this->matchesAny($normQuery, ['cnpj', 'cpf', 'fornecedor', 'fornecedores', 'emitente', 'empresa', 'empresas'])) {
            $reply  = $this->formatEntitiesReply($analyzedDocs);
            $intent = 'entities';
        } elseif ($this->matchesAny($normQuery, ['pendente', 'pendentes', 'falta', 'faltando', 'pendencia', 'pendencias', 'rejeitado', 'rejeitados', 'rejeicao', 'prazo', 'status', 'situacao'])) {
            $reply  = $this->formatPendingReply($enabledItems, $itemStatuses, $itemReasons, $analyzedDocs, $stats);
            $intent = 'pending';
        } elseif ($this->matchesAny($normQuery, ['anexo', 'anexos', 'arquivo', 'arquivos', 'documento', 'documentos', 'listar', 'lista'])) {
            $reply  = $this->formatAttachmentsListReply($analyzedDocs);
            $intent = 'attachments';
        } elseif ($this->matchesAny($normQuery, ['ajuda', 'help', 'comandos', 'como usar', 'o que voce faz'])) {
            $reply  = $this->formatHelpReply();
            $intent = 'help';
        } else {
            $reply  = $this->formatSearchReply($rawQuery, $enabledItems, $analyzedDocs);
            $intent = 'search';
        }

        // Camada de IA (Groq API ou Gemini API, conforme AI_PROVIDER no .env)
        $aiUsed       = false;
        $aiCached     = false;
        $aiProvider   = null;
        $aiModel      = null;
        $aiError      = null;
        $tokensUsed   = 0;

        $aiIntents = ['summary', 'item', 'search', 'validations', 'values', 'entities'];
        if (in_array($intent, $aiIntents, true) && ! empty($analyzedDocs)) {
            $aiService = new GeminiSummaryService();
            if ($aiService->isEnabled()) {
                $aiRes = $aiService->summarize($rawQuery, $intent, $accountability, $stats, $analyzedDocs, $validations);
                if (! empty($aiRes['success']) && ! empty($aiRes['text'])) {
                    $reply      = (string) $aiRes['text'];
                    $aiUsed     = true;
                    $aiCached   = ! empty($aiRes['cached']);
                    $aiProvider = (string) ($aiRes['provider'] ?? 'IA');
                    $aiModel    = (string) ($aiRes['model'] ?? '');
                    $tokensUsed = (int) ($aiRes['tokens_used'] ?? 0);
                } else {
                    $aiError = (string) ($aiRes['error'] ?? '');
                }
            }
        }

        $elapsedMs    = (int) round((microtime(true) - $startedAt) * 1000);
        $ocrDocsCount = count(array_filter($analyzedDocs, static fn ($d) => ! empty($d['used_ocr'])));
        $engineLabel  = $aiUsed
            ? ($aiProvider ?: 'IA') . ' (' . ($aiModel ?: 'auto') . ')' . ($aiCached ? ' [cache]' : '') . ' + OCR Local + Validações PHP'
            : 'Modo Local (OCR + Validações PHP)';

        return [
            'success' => true,
            'intent'  => $intent,
            'answer'  => $reply,
            'meta'    => [
                'elapsed_ms'         => $elapsedMs,
                'attachments_count'  => count($analyzedDocs),
                'items_count'        => count($enabledItems),
                'ocr_enabled'        => $this->isOcrAvailable(),
                'ocr_docs_count'     => $ocrDocsCount,
                'php_warnings_count' => (int) ($validations['warnings_count'] ?? 0),
                'gemini_used'        => $aiUsed,
                'gemini_model'       => $aiModel,
                'gemini_cached'      => $aiCached,
                'gemini_error'       => $aiError,
                'ai_provider'        => $aiProvider,
                'engine'             => $engineLabel,
                'tokens_used'        => $tokensUsed,
            ],
        ];
    }

    /**
     * Extrai texto (PDF + OCR local + ZIP) e metadados estruturados de uma lista de anexos.
     *
     * @param array<int, array<string, mixed>> $attachments
     * @param array<int, string>               $itemTitles
     * @return array<int, array<string, mixed>>
     */
    public function analyzeAttachments(array $attachments, array $itemTitles = []): array
    {
        $analyzed = [];
        foreach ($attachments as $att) {
            $itemNum    = (int) ($att['item_number'] ?? 0);
            $itemTitle  = rtrim((string) ($itemTitles[$itemNum] ?? "Item {$itemNum}"), ';.');
            $analyzed[] = $this->analyzeFile($att, $itemTitle);
        }
        return $analyzed;
    }

    /**
     * Extrai texto, dados estruturados (regex) e resumo extrativo de um único anexo.
     *
     * @param array<string, mixed> $att
     * @return array<string, mixed>
     */
    private function analyzeFile(array $att, string $itemTitle): array
    {
        $attId        = (int) ($att['id'] ?? 0);
        $itemNum      = (int) ($att['item_number'] ?? 0);
        $originalName = (string) ($att['original_name'] ?? 'arquivo');
        $ext          = strtolower((string) ($att['file_ext'] ?? pathinfo($originalName, PATHINFO_EXTENSION)));
        $fileSize     = (int) ($att['file_size'] ?? 0);
        $filePath     = (string) ($att['file_path'] ?? '');
        $fullPath     = $filePath !== '' ? storage_path('app/' . $filePath) : '';

        $extractedText = '';
        $notes         = [];
        $zipEntries    = [];
        $xmlStructured = [];
        $usedOcr       = false;

        if ($fullPath === '' || ! is_file($fullPath)) {
            $notes[] = 'Arquivo físico não encontrado no servidor.';
        } elseif (filesize($fullPath) > self::MAX_FILE_BYTES) {
            $notes[] = 'Arquivo acima de 15 MB (leitura de texto ignorada para manter baixo consumo).';
        } else {
            if ($ext === 'pdf') {
                $extractedText = $this->extractTextFromPdf($fullPath);
                if (mb_strlen(trim($extractedText)) < 25) {
                    $ocrPdfText = $this->extractTextFromScannedPdf($fullPath);
                    if (mb_strlen(trim($ocrPdfText)) >= 15) {
                        $extractedText = $ocrPdfText;
                        $usedOcr       = true;
                        $notes[]       = 'Texto extraído de PDF escaneado via OCR local (0 tokens).';
                    } else {
                        $notes[] = 'PDF escaneado/imagem ou protegido (sem texto legível encontrado mesmo após OCR).';
                    }
                }
            } elseif ($ext === 'zip') {
                $zipData       = $this->extractFromZip($fullPath);
                $extractedText = $zipData['text'];
                $zipEntries    = $zipData['entries'];
                $xmlStructured = $zipData['xml_structured'];
                $usedOcr       = ! empty($zipData['used_ocr']);
                if ($usedOcr) {
                    $notes[] = 'Imagens internas do ZIP lidas via OCR local (0 tokens).';
                }
                if ($extractedText === '' && ! empty($zipEntries)) {
                    $notes[] = 'ZIP contém ' . count($zipEntries) . ' arquivo(s) sem texto legível.';
                }
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tif', 'tiff'], true)) {
                $dims    = @getimagesize($fullPath);
                $dimText = (is_array($dims) && ! empty($dims[0]) && ! empty($dims[1]))
                    ? "Imagem {$dims[0]}×{$dims[1]}px"
                    : 'Imagem anexada';

                if ($this->isOcrAvailable()) {
                    $ocrText = $this->extractTextFromImage($fullPath);
                    if (mb_strlen(trim($ocrText)) >= 10) {
                        $extractedText = $ocrText;
                        $usedOcr       = true;
                        $notes[]       = "{$dimText} — texto extraído via OCR local (0 tokens).";
                    } else {
                        $notes[] = "{$dimText} (OCR executado, mas sem texto legível — provável foto sem texto).";
                    }
                } else {
                    $notes[] = "{$dimText} (motor Tesseract OCR não encontrado no servidor).";
                }
            }
        }

        $searchCorpus = $originalName . "\n" . $extractedText;

        $values    = $this->extractCurrencyValues($extractedText);
        $cnpjs     = $this->extractCnpjs($searchCorpus);
        $cpfs      = $this->extractCpfs($searchCorpus);
        $dates     = $this->extractDates($extractedText);
        $docNums   = $this->extractDocumentNumbers($searchCorpus);

        // Incorpora dados estruturados de XML de NF-e (se houver dentro do ZIP)
        foreach ($xmlStructured as $xmlInfo) {
            if (! empty($xmlInfo['value']) && (float) $xmlInfo['value'] > 0) {
                $values[] = (float) $xmlInfo['value'];
            }
            if (! empty($xmlInfo['cnpj'])) {
                $cnpjs[] = $xmlInfo['cnpj'];
            }
            if (! empty($xmlInfo['number'])) {
                $docNums[] = 'NF-e ' . $xmlInfo['number'];
            }
            if (! empty($xmlInfo['date'])) {
                $dates[] = $xmlInfo['date'];
            }
        }

        $values  = array_values(array_unique($values));
        rsort($values);
        $cnpjs   = array_values(array_unique($cnpjs));
        $cpfs    = array_values(array_unique($cpfs));
        $dates   = array_values(array_unique($dates));
        $docNums = array_values(array_unique($docNums));

        $docType           = $this->inferDocumentType($itemNum, $originalName, $ext, $extractedText);
        $extractiveSummary = $this->buildExtractiveSummary($extractedText, 2, 340);

        return [
            'id'                 => $attId,
            'item_number'        => $itemNum,
            'item_title'         => $itemTitle,
            'original_name'      => $originalName,
            'ext'                => $ext,
            'size_human'         => $this->formatBytes($fileSize),
            'doc_type'           => $docType,
            'text'               => $extractedText,
            'has_text'           => mb_strlen(trim($extractedText)) >= 10,
            'used_ocr'           => $usedOcr,
            'values'             => $values,
            'main_value'         => ! empty($values) ? (float) $values[0] : 0.0,
            'cnpjs'              => $cnpjs,
            'cpfs'               => $cpfs,
            'dates'              => array_slice($dates, 0, 5),
            'doc_numbers'        => array_slice($docNums, 0, 5),
            'zip_entries'        => $zipEntries,
            'extractive_summary' => $extractiveSummary,
            'notes'              => $notes,
        ];
    }

    /**
     * @param array<int, string>             $enabledItems
     * @param array<int, string>             $itemStatuses
     * @param array<int, array<string, mixed>> $analyzedDocs
     * @return array<string, mixed>
     */
    private function buildOverviewStats(array $enabledItems, array $itemStatuses, array $analyzedDocs): array
    {
        $statusCounts = [
            'pendente'      => 0,
            'enviado'       => 0,
            'confirmado'    => 0,
            'rejeitado'     => 0,
            'prazo_vencido' => 0,
        ];

        foreach ($enabledItems as $num => $_) {
            $st = $itemStatuses[$num] ?? 'pendente';
            if (! isset($statusCounts[$st])) {
                $statusCounts[$st] = 0;
            }
            $statusCounts[$st]++;
        }

        $totalEstimatedValue = 0.0;
        $docsWithValue       = 0;
        $docsWithText        = 0;
        $allCnpjs            = [];
        $itemsWithFiles      = [];

        foreach ($analyzedDocs as $doc) {
            $itemsWithFiles[(int) $doc['item_number']] = true;
            if ($doc['has_text']) {
                $docsWithText++;
            }
            if ((float) $doc['main_value'] > 0) {
                $totalEstimatedValue += (float) $doc['main_value'];
                $docsWithValue++;
            }
            foreach ($doc['cnpjs'] as $c) {
                $allCnpjs[$c] = true;
            }
        }

        return [
            'status_counts'         => $statusCounts,
            'items_with_files'      => count($itemsWithFiles),
            'total_items'           => count($enabledItems),
            'total_attachments'     => count($analyzedDocs),
            'docs_with_text'        => $docsWithText,
            'docs_with_value'       => $docsWithValue,
            'total_estimated_value' => round($totalEstimatedValue, 2),
            'unique_cnpjs_count'    => count($allCnpjs),
        ];
    }

    /**
     * @param array<string, mixed>             $accountability
     * @param array<int, string>               $enabledItems
     * @param array<int, string>               $itemStatuses
     * @param array<int, string>               $itemReasons
     * @param array<int, array<string, mixed>> $analyzedDocs
     * @param array<string, mixed>             $stats
     */
    private function formatGeneralSummary(
        array $accountability,
        array $enabledItems,
        array $itemStatuses,
        array $itemReasons,
        array $analyzedDocs,
        array $stats,
        array $validations = []
    ): string {
        $title    = (string) ($accountability['title'] ?? 'Prestação de Contas');
        $deadline = ! empty($accountability['deadline'])
            ? date('d/m/Y', strtotime((string) $accountability['deadline']))
            : 'Não definido';

        $sc    = $stats['status_counts'];
        $lines = [];
        $lines[] = "📋 **Resumo Consolidado — {$title}**";
        $lines[] = "• Prazo limite: **{$deadline}**";
        $lines[] = "• Itens liberados: **{$stats['total_items']}** ({$stats['items_with_files']} com anexo(s))";
        $lines[] = "• Situação: **{$sc['confirmado']}** confirmado(s) | **{$sc['enviado']}** enviado(s) | **{$sc['pendente']}** pendente(s) | **{$sc['rejeitado']}** rejeitado(s)"
            . ($sc['prazo_vencido'] > 0 ? " | **{$sc['prazo_vencido']}** prazo vencido" : '');
        $lines[] = "• Anexos analisados localmente: **{$stats['total_attachments']}** ({$stats['docs_with_text']} com texto extraível)";

        if ($stats['total_estimated_value'] > 0) {
            $lines[] = "• Soma estimada dos maiores valores encontrados nos textos: **" . $this->formatMoney((float) $stats['total_estimated_value']) . "** ({$stats['docs_with_value']} arquivo(s) com valor)";
        }
        if ($stats['unique_cnpjs_count'] > 0) {
            $lines[] = "• CNPJs distintos identificados: **{$stats['unique_cnpjs_count']}**";
        }

        if (empty($analyzedDocs)) {
            $lines[] = '';
            $lines[] = 'Nenhum arquivo foi anexado nos itens visíveis desta prestação até o momento.';
            return implode("\n", $lines);
        }

        // Agrupa anexos por item
        $byItem = [];
        foreach ($analyzedDocs as $doc) {
            $byItem[(int) $doc['item_number']][] = $doc;
        }

        $lines[] = '';
        $lines[] = '📑 **Destaques por item anexado:**';

        foreach ($byItem as $itemNum => $docs) {
            $itemTitle = rtrim((string) ($enabledItems[$itemNum] ?? "Item {$itemNum}"), ';.');
            $stLabel   = $this->statusLabel($itemStatuses[$itemNum] ?? 'pendente');
            $numPad    = str_pad((string) $itemNum, 2, '0', STR_PAD_LEFT);
            $lines[]   = "\n**Item {$numPad} — {$itemTitle}** ({$stLabel})";

            foreach ($docs as $doc) {
                $metaParts = [strtoupper($doc['ext']), $doc['size_human'], $doc['doc_type']];
                if ((float) $doc['main_value'] > 0) {
                    $metaParts[] = 'Valor: ' . $this->formatMoney((float) $doc['main_value']);
                }
                if (! empty($doc['cnpjs'])) {
                    $metaParts[] = 'CNPJ: ' . $doc['cnpjs'][0];
                }
                if (! empty($doc['dates'])) {
                    $metaParts[] = 'Data: ' . $doc['dates'][0];
                }

                $lines[] = "• **{$doc['original_name']}** (" . implode(' • ', $metaParts) . ')';

                if ($doc['extractive_summary'] !== '') {
                    $lines[] = '  ↳ ' . $doc['extractive_summary'];
                } elseif (! empty($doc['notes'])) {
                    $lines[] = '  ↳ ' . implode(' ', $doc['notes']);
                }
            }

            if (($itemStatuses[$itemNum] ?? '') === 'rejeitado' && ! empty($itemReasons[$itemNum])) {
                $lines[] = '  ⚠️ Motivo da rejeição: ' . $itemReasons[$itemNum];
            }
        }

        $lines[] = '';
        if (! empty($validations['alerts'])) {
            $lines[] = '🛡️ **Validações Automáticas em PHP (' . count($validations['alerts']) . ' alerta(s)):**';
            foreach (array_slice($validations['alerts'], 0, 5) as $al) {
                $icon    = ($al['level'] ?? '') === 'error' ? '❌' : '⚠️';
                $lines[] = "• {$icon} " . (string) $al['message'];
            }
            $lines[] = '';
        }
        $lines[] = '💡 Dica: digite **"validações"**, **"item 3"**, **"valores"**, **"CNPJ"**, **"pendências"** ou qualquer palavra-chave para pesquisar dentro dos arquivos.';

        return implode("\n", $lines);
    }

    /**
     * Executa validações determinísticas próprias em PHP sobre os documentos analisados:
     * - Validação matemática de dígitos verificadores de CNPJ e CPF (Módulo 11)
     * - Presença de valores monetários (R$) em itens financeiros
     * - Verificação de datas posteriores ao prazo limite da prestação
     * - Detecção de anexos ilegíveis/sem texto após OCR
     *
     * @param array<string, mixed>              $accountability
     * @param array<int, string>                $enabledItems
     * @param array<int, string>                $itemStatuses
     * @param array<int, array<string, mixed>>  $analyzedDocs
     * @return array{alerts: array<int, array{level: string, message: string}>, warnings_count: int, valid_cnpjs: int, invalid_cnpjs: int, valid_cpfs: int, invalid_cpfs: int}
     */
    private function runPhpValidations(
        array $accountability,
        array $enabledItems,
        array $itemStatuses,
        array $analyzedDocs
    ): array {
        $alerts       = [];
        $validCnpjs   = 0;
        $invalidCnpjs = 0;
        $validCpfs    = 0;
        $invalidCpfs  = 0;

        $financialItems = [1, 2, 3, 4, 6, 13, 14, 15, 22];
        $deadlineTs     = ! empty($accountability['deadline'])
            ? strtotime((string) $accountability['deadline'] . ' 23:59:59')
            : false;

        foreach ($analyzedDocs as $doc) {
            $itemNum = (int) ($doc['item_number'] ?? 0);
            $numPad  = str_pad((string) $itemNum, 2, '0', STR_PAD_LEFT);
            $name    = (string) ($doc['original_name'] ?? 'arquivo');

            if (empty($doc['has_text'])) {
                $alerts[] = [
                    'level'   => 'warning',
                    'message' => "Item {$numPad} ({$name}): sem texto legível mesmo após parser/OCR — requer conferência visual.",
                ];
            }

            foreach ((array) ($doc['cnpjs'] ?? []) as $cnpj) {
                if ($this->isValidCnpj((string) $cnpj)) {
                    $validCnpjs++;
                } else {
                    $invalidCnpjs++;
                    $alerts[] = [
                        'level'   => 'error',
                        'message' => "Item {$numPad} ({$name}): CNPJ **{$cnpj}** com dígito verificador inválido (possível erro no documento ou OCR).",
                    ];
                }
            }

            foreach ((array) ($doc['cpfs'] ?? []) as $cpf) {
                if ($this->isValidCpf((string) $cpf)) {
                    $validCpfs++;
                } else {
                    $invalidCpfs++;
                    $alerts[] = [
                        'level'   => 'error',
                        'message' => "Item {$numPad} ({$name}): CPF **{$cpf}** com dígito verificador inválido.",
                    ];
                }
            }

            if (in_array($itemNum, $financialItems, true) && ! empty($doc['has_text']) && empty($doc['values'])) {
                $alerts[] = [
                    'level'   => 'warning',
                    'message' => "Item {$numPad} ({$name}): item financeiro sem valor monetário (`R$`) identificado no texto.",
                ];
            }

            if ($deadlineTs !== false && ! empty($doc['dates'])) {
                foreach ($doc['dates'] as $dBr) {
                    $parts = explode('/', (string) $dBr);
                    if (count($parts) === 3) {
                        $docTs = strtotime("{$parts[2]}-{$parts[1]}-{$parts[0]}");
                        if ($docTs !== false && $docTs > $deadlineTs) {
                            $alerts[] = [
                                'level'   => 'warning',
                                'message' => "Item {$numPad} ({$name}): data **{$dBr}** posterior ao prazo limite da prestação.",
                            ];
                            break;
                        }
                    }
                }
            }
        }

        // Verifica itens rejeitados ou vencidos
        foreach ($enabledItems as $num => $label) {
            $st     = $itemStatuses[$num] ?? 'pendente';
            $numPad = str_pad((string) $num, 2, '0', STR_PAD_LEFT);
            if ($st === 'rejeitado') {
                $alerts[] = [
                    'level'   => 'error',
                    'message' => "Item {$numPad} está com status **Rejeitado** e aguarda correção.",
                ];
            } elseif ($st === 'prazo_vencido') {
                $alerts[] = [
                    'level'   => 'warning',
                    'message' => "Item {$numPad} está com **Prazo Vencido**.",
                ];
            }
        }

        return [
            'alerts'         => $alerts,
            'warnings_count' => count($alerts),
            'valid_cnpjs'    => $validCnpjs,
            'invalid_cnpjs'  => $invalidCnpjs,
            'valid_cpfs'     => $validCpfs,
            'invalid_cpfs'   => $invalidCpfs,
        ];
    }

    /**
     * @param array<string, mixed> $validations
     */
    private function formatValidationsReply(array $validations): string
    {
        $alerts = $validations['alerts'] ?? [];
        $lines  = [];
        $lines[] = '🛡️ **Validações Próprias em PHP (Conferência Determinística)**';
        $lines[] = sprintf(
            '• CNPJs verificados: **%d** válido(s) / **%d** inválido(s) | CPFs: **%d** válido(s) / **%d** inválido(s)',
            (int) ($validations['valid_cnpjs'] ?? 0),
            (int) ($validations['invalid_cnpjs'] ?? 0),
            (int) ($validations['valid_cpfs'] ?? 0),
            (int) ($validations['invalid_cpfs'] ?? 0)
        );

        if (empty($alerts)) {
            $lines[] = '';
            $lines[] = '✅ Nenhuma inconsistência encontrada nas validações automáticas em PHP.';
            return implode("\n", $lines);
        }

        $lines[] = '';
        $lines[] = '**Alertas encontrados (' . count($alerts) . '):**';
        foreach ($alerts as $al) {
            $icon    = ($al['level'] ?? '') === 'error' ? '❌' : '⚠️';
            $lines[] = "• {$icon} " . (string) $al['message'];
        }

        return implode("\n", $lines);
    }

    private function isValidCnpj(string $cnpj): bool
    {
        $digits = preg_replace('/\D/', '', $cnpj) ?? '';
        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $digits[$i]) * $weights1[$i];
        }
        $rem = $sum % 11;
        $d1  = $rem < 2 ? 0 : 11 - $rem;
        if ((int) $digits[12] !== $d1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += ((int) $digits[$i]) * $weights2[$i];
        }
        $rem = $sum % 11;
        $d2  = $rem < 2 ? 0 : 11 - $rem;

        return (int) $digits[13] === $d2;
    }

    private function isValidCpf(string $cpf): bool
    {
        $digits = preg_replace('/\D/', '', $cpf) ?? '';
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += ((int) $digits[$i]) * (($t + 1) - $i);
            }
            $d = ((10 * $sum) % 11) % 10;
            if ((int) $digits[$t] !== $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string>               $enabledItems
     * @param array<int, string>               $itemStatuses
     * @param array<int, string>               $itemReasons
     * @param array<int, array<string, mixed>> $analyzedDocs
     */
    private function formatItemReply(
        int $targetItem,
        array $enabledItems,
        array $itemStatuses,
        array $itemReasons,
        array $analyzedDocs
    ): string {
        if (! isset($enabledItems[$targetItem])) {
            return "O **Item {$targetItem}** não está habilitado ou não está liberado para o seu usuário nesta prestação.";
        }

        $title   = rtrim((string) $enabledItems[$targetItem], ';.');
        $status  = $itemStatuses[$targetItem] ?? 'pendente';
        $stLabel = $this->statusLabel($status);
        $docs    = array_values(array_filter($analyzedDocs, static fn ($d) => (int) $d['item_number'] === $targetItem));

        $lines   = [];
        $lines[] = "📌 **Item " . str_pad((string) $targetItem, 2, '0', STR_PAD_LEFT) . " — {$title}**";
        $lines[] = "• Situação atual: **{$stLabel}**";
        $lines[] = '• Anexos encontrados: **' . count($docs) . '**';

        if ($status === 'rejeitado' && ! empty($itemReasons[$targetItem])) {
            $lines[] = "• ⚠️ Motivo da rejeição: **{$itemReasons[$targetItem]}**";
        }

        if (empty($docs)) {
            $lines[] = '';
            $lines[] = 'Nenhum arquivo foi anexado neste item até o momento.';
            return implode("\n", $lines);
        }

        foreach ($docs as $doc) {
            $lines[] = '';
            $lines[] = "📄 **{$doc['original_name']}** ({$doc['doc_type']} • " . strtoupper($doc['ext']) . " • {$doc['size_human']})";

            if (! empty($doc['doc_numbers'])) {
                $lines[] = '• Números identificados: ' . implode(', ', $doc['doc_numbers']);
            }
            if (! empty($doc['values'])) {
                $formattedVals = array_map(fn ($v) => $this->formatMoney((float) $v), array_slice($doc['values'], 0, 6));
                $lines[] = '• Valores encontrados: **' . implode('**, **', $formattedVals) . '**';
            }
            if (! empty($doc['cnpjs'])) {
                $lines[] = '• CNPJ(s): ' . implode(', ', $doc['cnpjs']);
            }
            if (! empty($doc['cpfs'])) {
                $lines[] = '• CPF(s): ' . implode(', ', $doc['cpfs']);
            }
            if (! empty($doc['dates'])) {
                $lines[] = '• Datas encontradas: ' . implode(', ', $doc['dates']);
            }
            if (! empty($doc['zip_entries'])) {
                $previewEntries = array_slice($doc['zip_entries'], 0, 8);
                $more           = count($doc['zip_entries']) > 8 ? ' (+' . (count($doc['zip_entries']) - 8) . ' arquivo(s))' : '';
                $lines[]        = '• Conteúdo do ZIP: ' . implode(', ', $previewEntries) . $more;
            }
            if ($doc['extractive_summary'] !== '') {
                $lines[] = '• Resumo extrativo: ' . $doc['extractive_summary'];
            }
            if (! empty($doc['notes'])) {
                $lines[] = '• Observação: ' . implode(' ', $doc['notes']);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $analyzedDocs
     * @param array<string, mixed>             $stats
     */
    private function formatValuesReply(array $analyzedDocs, array $stats): string
    {
        $lines   = [];
        $lines[] = '💰 **Valores Monetários Identificados (R$)**';

        $foundAny = false;
        foreach ($analyzedDocs as $doc) {
            if (empty($doc['values'])) {
                continue;
            }
            $foundAny  = true;
            $numPad    = str_pad((string) $doc['item_number'], 2, '0', STR_PAD_LEFT);
            $topValues = array_map(fn ($v) => $this->formatMoney((float) $v), array_slice($doc['values'], 0, 4));
            $lines[]   = "• **Item {$numPad}** — {$doc['original_name']}: **" . implode('**, **', $topValues) . '**';
        }

        if (! $foundAny) {
            $lines[] = '';
            $lines[] = 'Nenhum valor monetário (`R$`) foi encontrado nos textos extraídos (incluindo OCR de imagens/PDFs escaneados).';
            $lines[] = 'Observação: fotos sem texto legível ou imagens em baixa resolução podem não conter valores reconhecíveis.';
            return implode("\n", $lines);
        }

        $lines[] = '';
        $lines[] = '• Soma estimada (considerando o maior valor de cada arquivo): **' . $this->formatMoney((float) $stats['total_estimated_value']) . '**';
        $lines[] = '_Nota: confira sempre os valores diretamente nos documentos originais, pois um PDF pode conter subtotais e totais na mesma página._';

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $analyzedDocs
     */
    private function formatEntitiesReply(array $analyzedDocs): string
    {
        $lines    = [];
        $lines[]  = '🏢 **CNPJs e CPFs Identificados nos Anexos**';
        $foundAny = false;

        foreach ($analyzedDocs as $doc) {
            if (empty($doc['cnpjs']) && empty($doc['cpfs'])) {
                continue;
            }
            $foundAny = true;
            $numPad   = str_pad((string) $doc['item_number'], 2, '0', STR_PAD_LEFT);
            $parts    = [];
            if (! empty($doc['cnpjs'])) {
                $parts[] = 'CNPJ: ' . implode(', ', $doc['cnpjs']);
            }
            if (! empty($doc['cpfs'])) {
                $parts[] = 'CPF: ' . implode(', ', $doc['cpfs']);
            }
            $lines[] = "• **Item {$numPad}** — {$doc['original_name']}: " . implode(' | ', $parts);
        }

        if (! $foundAny) {
            $lines[] = '';
            $lines[] = 'Nenhum CNPJ ou CPF foi encontrado nos textos extraíveis dos anexos desta prestação.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, string>               $enabledItems
     * @param array<int, string>               $itemStatuses
     * @param array<int, string>               $itemReasons
     * @param array<int, array<string, mixed>> $analyzedDocs
     * @param array<string, mixed>             $stats
     */
    private function formatPendingReply(
        array $enabledItems,
        array $itemStatuses,
        array $itemReasons,
        array $analyzedDocs,
        array $stats
    ): string {
        $attCountByItem = [];
        foreach ($analyzedDocs as $doc) {
            $n                  = (int) $doc['item_number'];
            $attCountByItem[$n] = ($attCountByItem[$n] ?? 0) + 1;
        }

        $rejected = [];
        $overdue  = [];
        $pending  = [];

        foreach ($enabledItems as $num => $label) {
            $clean  = rtrim($label, ';.');
            $numPad = str_pad((string) $num, 2, '0', STR_PAD_LEFT);
            $st     = $itemStatuses[$num] ?? 'pendente';
            $cnt    = $attCountByItem[$num] ?? 0;

            if ($st === 'rejeitado') {
                $reason     = ! empty($itemReasons[$num]) ? " — Motivo: {$itemReasons[$num]}" : '';
                $rejected[] = "• **Item {$numPad}** — {$clean} ({$cnt} anexo(s)){$reason}";
            } elseif ($st === 'prazo_vencido') {
                $overdue[] = "• **Item {$numPad}** — {$clean} ({$cnt} anexo(s))";
            } elseif ($st === 'pendente') {
                $pending[] = "• **Item {$numPad}** — {$clean} (" . ($cnt > 0 ? "{$cnt} anexo(s) não enviado(s)" : 'sem anexos') . ')';
            }
        }

        $lines   = [];
        $lines[] = '⚠️ **Pendências e Situação dos Itens**';

        if (! empty($rejected)) {
            $lines[] = "\n**Rejeitados (" . count($rejected) . '):**';
            $lines   = array_merge($lines, $rejected);
        }

        if (! empty($overdue)) {
            $lines[] = "\n**Prazo Vencido (" . count($overdue) . '):**';
            $lines   = array_merge($lines, $overdue);
        }

        if (! empty($pending)) {
            $lines[] = "\n**Pendentes (" . count($pending) . '):**';
            $lines   = array_merge($lines, $pending);
        }

        if (empty($rejected) && empty($overdue) && empty($pending)) {
            $sc      = $stats['status_counts'];
            $lines[] = '';
            $lines[] = "✅ Nenhuma pendência encontrada! Todos os itens estão enviados ({$sc['enviado']}) ou confirmados ({$sc['confirmado']}).";
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $analyzedDocs
     */
    private function formatAttachmentsListReply(array $analyzedDocs): string
    {
        if (empty($analyzedDocs)) {
            return '📎 Nenhum arquivo anexado nos itens visíveis desta prestação.';
        }

        $lines   = [];
        $lines[] = '📎 **Lista de Anexos (' . count($analyzedDocs) . ' arquivo(s)):**';

        foreach ($analyzedDocs as $doc) {
            $numPad = str_pad((string) $doc['item_number'], 2, '0', STR_PAD_LEFT);
            if (! empty($doc['used_ocr'])) {
                $extra = 'texto via OCR';
            } elseif ($doc['has_text']) {
                $extra = 'texto extraído';
            } else {
                $extra = 'sem texto legível';
            }
            $lines[] = "• **Item {$numPad}** — **{$doc['original_name']}** (" . strtoupper($doc['ext']) . ", {$doc['size_human']}, {$extra})";
        }

        return implode("\n", $lines);
    }

    /**
     * Busca livre por palavras-chave nos nomes dos arquivos, títulos dos itens e conteúdo extraído.
     *
     * @param array<int, string>               $enabledItems
     * @param array<int, array<string, mixed>> $analyzedDocs
     */
    private function formatSearchReply(string $rawQuery, array $enabledItems, array $analyzedDocs): string
    {
        $tokens = $this->tokenizeQuery($rawQuery);
        if (empty($tokens)) {
            return $this->formatHelpReply();
        }

        $matches = [];
        foreach ($analyzedDocs as $doc) {
            $haystack = $this->normalizeForMatch(
                $doc['original_name'] . ' ' . $doc['item_title'] . ' ' . $doc['text'] . ' ' . implode(' ', $doc['zip_entries'])
            );

            $score = 0;
            foreach ($tokens as $tok) {
                $cnt = substr_count($haystack, $tok);
                if ($cnt > 0) {
                    $score += min(5, $cnt);
                }
            }

            if ($score > 0) {
                $snippet = $this->extractBestSnippet($doc['text'], $tokens);
                $matches[] = [
                    'score'   => $score,
                    'doc'     => $doc,
                    'snippet' => $snippet,
                ];
            }
        }

        usort($matches, static fn ($a, $b) => $b['score'] <=> $a['score']);

        // Também verifica se o termo bate com o título de algum item do checklist
        $matchingItems = [];
        foreach ($enabledItems as $num => $label) {
            $normLabel = $this->normalizeForMatch($label);
            foreach ($tokens as $tok) {
                if (str_contains($normLabel, $tok)) {
                    $matchingItems[$num] = rtrim($label, ';.');
                    break;
                }
            }
        }

        $lines   = [];
        $lines[] = "🔎 **Resultado da busca por \"{$rawQuery}\":**";

        if (! empty($matches)) {
            $lines[] = '';
            $lines[] = '**Anexos encontrados (' . count($matches) . '):**';
            foreach (array_slice($matches, 0, 8) as $m) {
                $doc     = $m['doc'];
                $numPad  = str_pad((string) $doc['item_number'], 2, '0', STR_PAD_LEFT);
                $lines[] = "• **Item {$numPad}** — **{$doc['original_name']}** ({$doc['doc_type']})";
                if ($m['snippet'] !== '') {
                    $lines[] = '  ↳ "...' . $m['snippet'] . '..."';
                } elseif ($doc['extractive_summary'] !== '') {
                    $lines[] = '  ↳ ' . $doc['extractive_summary'];
                }
            }
        }

        if (! empty($matchingItems)) {
            $lines[] = '';
            $lines[] = '**Itens do checklist relacionados:**';
            foreach ($matchingItems as $num => $label) {
                $numPad  = str_pad((string) $num, 2, '0', STR_PAD_LEFT);
                $lines[] = "• **Item {$numPad}** — {$label} _(digite \"item {$num}\" para detalhar)_";
            }
        }

        if (empty($matches) && empty($matchingItems)) {
            $lines[] = '';
            $lines[] = 'Nenhuma ocorrência encontrada nos títulos ou textos extraíveis dos anexos.';
            $lines[] = '💡 Tente termos mais curtos ou use: **"resumo geral"**, **"valores"**, **"CNPJ"**, **"pendências"** ou **"item 3"**.';
        }

        return implode("\n", $lines);
    }

    private function formatHelpReply(): string
    {
        $ocrBadge = $this->isOcrAvailable() ? ' + OCR Local (Imagens e PDFs escaneados)' : '';

        return "⚡ **Assistente Local (100% PHP{$ocrBadge} • 0 tokens)**\n\n"
            . "Você pode usar os botões rápidos ou digitar:\n"
            . "• **resumo geral** — visão consolidada da prestação e resumo extrativo dos anexos\n"
            . "• **valores** — lista os valores monetários (`R$`) encontrados nos documentos e imagens\n"
            . "• **CNPJ** — lista CNPJs e CPFs identificados\n"
            . "• **pendências** — mostra itens pendentes, rejeitados (com motivo) e vencidos\n"
            . "• **item 3** (ou qualquer número) — detalha todos os anexos daquele item\n"
            . "• **anexos** — lista todos os arquivos enviados\n"
            . "• **qualquer palavra-chave** — pesquisa dentro do nome e texto dos PDFs, imagens (OCR) e ZIPs";
    }

    /**
     * Resumo extrativo leve (TF + pesos de domínio) em PHP puro.
     */
    private function buildExtractiveSummary(string $text, int $maxSentences = 2, int $maxChars = 340): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if (mb_strlen($clean) < 30) {
            return '';
        }

        $rawSentences = preg_split('/(?<=[\.\!\?\;])\s+/u', $clean) ?: [$clean];
        $sentences    = [];
        foreach ($rawSentences as $s) {
            $s = trim($s);
            if (
                mb_strlen($s) >= 25
                && mb_strlen($s) <= 320
                && ! str_contains($s, 'http://')
                && ! str_contains($s, 'https://')
                && substr_count($s, '=') < 2
                && substr_count($s, '&') < 2
            ) {
                $sentences[] = $s;
            }
        }

        if (empty($sentences)) {
            return mb_substr($clean, 0, $maxChars) . (mb_strlen($clean) > $maxChars ? '...' : '');
        }

        if (count($sentences) <= $maxSentences) {
            $joined = implode(' ', $sentences);
            return mb_strlen($joined) > $maxChars ? mb_substr($joined, 0, $maxChars) . '...' : $joined;
        }

        // Frequência de termos no documento
        $tf = [];
        foreach ($sentences as $s) {
            foreach ($this->extractWords($s) as $w) {
                $tf[$w] = ($tf[$w] ?? 0) + 1;
            }
        }

        $maxTf = ! empty($tf) ? max($tf) : 1;
        $scored = [];

        foreach ($sentences as $idx => $s) {
            $words = $this->extractWords($s);
            if (empty($words)) {
                continue;
            }

            $score = 0.0;
            foreach ($words as $w) {
                $normTf = ($tf[$w] ?? 0) / $maxTf;
                $boost  = $this->domainBoosts[$w] ?? 1.0;
                $score += $normTf * $boost;
            }

            // Normaliza pelo tamanho e favorece levemente as primeiras frases (cabeçalho/objeto)
            $score = ($score / sqrt(count($words))) + ($idx < 2 ? 0.35 : 0.0);

            if (preg_match('/R\$\s*[0-9]/i', $s)) {
                $score += 0.9;
            }
            if (preg_match('/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/', $s)) {
                $score += 0.6;
            }

            $scored[] = ['idx' => $idx, 'score' => $score, 'text' => $s];
        }

        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, $maxSentences);
        usort($top, static fn ($a, $b) => $a['idx'] <=> $b['idx']);

        $summary = implode(' ', array_column($top, 'text'));
        if (mb_strlen($summary) > $maxChars) {
            $summary = mb_substr($summary, 0, $maxChars) . '...';
        }

        return $summary;
    }

    /**
     * Extrai texto de um PDF usando smalot/pdfparser (com fallback em pdftotext e PHP puro).
     */
    private function extractTextFromPdf(string $pdfPath): string
    {
        // 1. Parser PHP completo (suporta CIDFont, ToUnicode CMap, FlateDecode e UTF-8)
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $config = new \Smalot\PdfParser\Config();
                $config->setRetainImageContent(false);
                $parser = new \Smalot\PdfParser\Parser([], $config);
                $pdf    = $parser->parseFile($pdfPath);
                $text   = (string) $pdf->getText();
                // Remove caracteres invisíveis (zero-width space, BOM) e normaliza espaços
                $text = (string) preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
                $text = trim((string) preg_replace('/[ \t]+/u', ' ', $text));
                $text = trim((string) preg_replace('/\R{2,}/u', "\n", $text));

                if (mb_strlen($text) >= 15) {
                    return mb_substr($text, 0, 50000);
                }
            } catch (Throwable) {
                // Segue para fallback abaixo
            }
        }

        // 2. Fallback: tenta pdftotext caso esteja instalado no servidor
        $pdftotextOut = $this->tryPdfToTextBinary($pdfPath);
        if (mb_strlen(trim($pdftotextOut)) >= 20) {
            return mb_substr($pdftotextOut, 0, 50000);
        }

        // 3. Fallback em regex PHP puro
        $raw = (string) @file_get_contents($pdfPath);
        if ($raw === '') {
            return '';
        }

        $texts = [];
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streamMatches)) {
            foreach ($streamMatches[1] as $streamData) {
                $decoded = @gzuncompress($streamData);
                if ($decoded === false) {
                    $decoded = @gzinflate($streamData);
                }
                $source = ($decoded !== false) ? $decoded : $streamData;

                if (! str_contains($source, 'Tj') && ! str_contains($source, 'TJ')) {
                    continue;
                }

                if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)\s*Tj/s', $source, $tjMatches)) {
                    foreach ($tjMatches[1] as $t) {
                        $cleaned = $this->cleanPdfString($t);
                        if ($cleaned !== '') {
                            $texts[] = $cleaned;
                        }
                    }
                }

                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $source, $tjArrMatches)) {
                    foreach ($tjArrMatches[1] as $arr) {
                        if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)/s', $arr, $subMatches)) {
                            $line = '';
                            foreach ($subMatches[1] as $st) {
                                $line .= $this->cleanPdfString($st);
                            }
                            if (trim($line) !== '') {
                                $texts[] = $line;
                            }
                        }
                    }
                }
            }
        }

        $joined = trim(preg_replace('/\s+/u', ' ', implode(' ', $texts)) ?? '');
        return mb_substr($joined, 0, 50000);
    }

    private function tryPdfToTextBinary(string $pdfPath): string
    {
        if (! function_exists('proc_open')) {
            return '';
        }

        try {
            $cmd = 'pdftotext -enc UTF-8 ' . escapeshellarg($pdfPath) . ' -';
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $proc = @proc_open($cmd, $descriptors, $pipes);
            if (! is_resource($proc)) {
                return '';
            }
            fclose($pipes[0]);
            $stdout = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($proc);

            return $exitCode === 0 ? $stdout : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function cleanPdfString(string $str): string
    {
        $str   = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", "\r", "\t", '(', ')', '\\'], $str);
        $clean = @mb_convert_encoding($str, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        return (string) preg_replace('/[^\P{C}\n\t]+/u', '', (string) $clean);
    }

    /**
     * @return array{text: string, entries: array<int, string>, xml_structured: array<int, array<string, mixed>>, used_ocr: bool}
     */
    private function extractFromZip(string $zipPath): array
    {
        $entries       = [];
        $texts         = [];
        $xmlStructured = [];
        $usedOcr       = false;
        $ocrImagesDone = 0;

        if (! class_exists(ZipArchive::class)) {
            return ['text' => '', 'entries' => [], 'xml_structured' => [], 'used_ocr' => false];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name      = (string) $zip->getNameIndex($i);
                $entries[] = $name;
                $ext       = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (in_array($ext, ['xml', 'txt', 'csv', 'json'], true)) {
                    $content = $zip->getFromIndex($i);
                    if ($content === false) {
                        continue;
                    }

                    if ($ext === 'xml') {
                        $nfe = $this->parseNfeXml($content);
                        if ($nfe !== null) {
                            $xmlStructured[] = $nfe;
                            $texts[]         = $nfe['summary_line'];
                            continue;
                        }
                    }

                    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags(mb_substr($content, 0, 5000))) ?? '');
                    if ($plain !== '') {
                        $texts[] = "Arquivo {$name}: {$plain}";
                    }
                } elseif (
                    in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)
                    && $ocrImagesDone < self::MAX_OCR_ZIP_IMAGES
                    && $this->isOcrAvailable()
                ) {
                    $content = $zip->getFromIndex($i);
                    if ($content === false || strlen($content) > 5 * 1024 * 1024) {
                        continue;
                    }
                    $ocrImagesDone++;
                    $cacheKey = 'zipimg_' . md5($content);
                    $cached   = $this->readOcrCache($cacheKey);
                    if ($cached !== null) {
                        $imgText = $cached;
                    } else {
                        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ocr_zip_' . uniqid('', true) . '.' . $ext;
                        @file_put_contents($tmpFile, $content);
                        $imgText = $this->runTesseractOnFile($tmpFile);
                        @unlink($tmpFile);
                        $this->writeOcrCache($cacheKey, $imgText);
                    }
                    if (mb_strlen(trim($imgText)) >= 10) {
                        $usedOcr = true;
                        $texts[] = "Imagem {$name} (OCR): {$imgText}";
                    }
                }
            }
            $zip->close();
        }

        return [
            'text'           => mb_substr(implode("\n", $texts), 0, 30000),
            'entries'        => $entries,
            'xml_structured' => $xmlStructured,
            'used_ocr'       => $usedOcr,
        ];
    }

    /**
     * Verifica se o motor Tesseract OCR + wrapper PHP estão disponíveis localmente.
     */
    private function isOcrAvailable(): bool
    {
        return class_exists(\thiagoalessio\TesseractOCR\TesseractOCR::class)
            && $this->resolveTesseractExecutable() !== null;
    }

    /**
     * Localiza automaticamente o executável do Tesseract OCR (Windows/Linux).
     */
    private function resolveTesseractExecutable(): ?string
    {
        if ($this->tesseractBinary !== null) {
            return $this->tesseractBinary === false ? null : $this->tesseractBinary;
        }

        $candidates = [];
        $envPath    = trim((string) env('TESSERACT_PATH', ''));
        if ($envPath !== '') {
            $candidates[] = $envPath;
        }

        $localAppData = getenv('LOCALAPPDATA') ?: '';
        if ($localAppData !== '') {
            $candidates[] = rtrim($localAppData, '\\/') . DIRECTORY_SEPARATOR . 'Programs' . DIRECTORY_SEPARATOR . 'Tesseract-OCR' . DIRECTORY_SEPARATOR . 'tesseract.exe';
        }

        $userProfile = getenv('USERPROFILE') ?: '';
        if ($userProfile !== '') {
            $candidates[] = rtrim($userProfile, '\\/') . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local' . DIRECTORY_SEPARATOR . 'Programs' . DIRECTORY_SEPARATOR . 'Tesseract-OCR' . DIRECTORY_SEPARATOR . 'tesseract.exe';
        }

        $candidates[] = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
        $candidates[] = 'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe';
        $candidates[] = '/usr/bin/tesseract';
        $candidates[] = '/usr/local/bin/tesseract';

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                $this->tesseractBinary = $path;
                return $path;
            }
        }

        // Verifica se "tesseract" está disponível diretamente no PATH do sistema
        $whichCmd = DIRECTORY_SEPARATOR === '\\' ? 'where tesseract 2>NUL' : 'which tesseract 2>/dev/null';
        $found    = trim((string) @shell_exec($whichCmd));
        if ($found !== '') {
            $firstLine = trim(strtok($found, "\r\n") ?: '');
            if ($firstLine !== '' && is_file($firstLine)) {
                $this->tesseractBinary = $firstLine;
                return $firstLine;
            }
        }

        $this->tesseractBinary = false;
        return null;
    }

    /**
     * Resolve o diretório tessdata e o idioma preferido (por -> eng).
     *
     * @return array{lang: string, tessdata_dir: ?string}
     */
    private function resolveTesseractLanguageConfig(string $binaryPath): array
    {
        $projectTessdata = storage_path('app/tessdata');
        $binaryTessdata  = dirname($binaryPath) . DIRECTORY_SEPARATOR . 'tessdata';

        if (is_file($binaryTessdata . DIRECTORY_SEPARATOR . 'por.traineddata')) {
            return ['lang' => 'por', 'tessdata_dir' => null];
        }

        if (is_file($projectTessdata . DIRECTORY_SEPARATOR . 'por.traineddata')) {
            return ['lang' => 'por', 'tessdata_dir' => $projectTessdata];
        }

        return ['lang' => 'eng', 'tessdata_dir' => null];
    }

    /**
     * Executa OCR local em uma imagem (com cache em disco para respostas instantâneas).
     */
    private function extractTextFromImage(string $imagePath): string
    {
        if (! $this->isOcrAvailable() || ! is_file($imagePath)) {
            return '';
        }

        $hash     = @md5_file($imagePath) ?: md5($imagePath . '|' . (string) @filesize($imagePath));
        $cacheKey = 'img_' . $hash;
        $cached   = $this->readOcrCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $text = $this->runTesseractOnFile($imagePath);
        $this->writeOcrCache($cacheKey, $text);

        return $text;
    }

    /**
     * Extrai imagens JPEG embutidas em PDFs escaneados e executa OCR local (até MAX_OCR_PDF_IMAGES páginas).
     */
    private function extractTextFromScannedPdf(string $pdfPath): string
    {
        if (! $this->isOcrAvailable() || ! is_file($pdfPath)) {
            return '';
        }

        $hash     = @md5_file($pdfPath) ?: md5($pdfPath . '|' . (string) @filesize($pdfPath));
        $cacheKey = 'pdf_' . $hash;
        $cached   = $this->readOcrCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $raw = (string) @file_get_contents($pdfPath);
        if ($raw === '') {
            return '';
        }

        $texts      = [];
        $offset     = 0;
        $rawLen     = strlen($raw);
        $imagesDone = 0;

        // Procura streams JPEG embutidos (SOI 0xFFD8FF ... EOI 0xFFD9)
        while ($imagesDone < self::MAX_OCR_PDF_IMAGES && $offset < $rawLen) {
            $start = strpos($raw, "\xFF\xD8\xFF", $offset);
            if ($start === false) {
                break;
            }
            $end = strpos($raw, "\xFF\xD9", $start + 3);
            if ($end === false) {
                break;
            }

            $jpegBytes = substr($raw, $start, ($end + 2) - $start);
            $offset    = $end + 2;

            // Ignora miniaturas / ícones muito pequenos (< 8 KB) ou blobs gigantes (> 8 MB)
            $len = strlen($jpegBytes);
            if ($len < 8192 || $len > 8 * 1024 * 1024) {
                continue;
            }

            $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ocr_pdf_' . uniqid('', true) . '.jpg';
            @file_put_contents($tmpFile, $jpegBytes);
            $ocrOut = $this->runTesseractOnFile($tmpFile);
            @unlink($tmpFile);

            $imagesDone++;
            if (mb_strlen(trim($ocrOut)) >= 10) {
                $texts[] = $ocrOut;
            }
        }

        $joined = mb_substr(trim(implode("\n", $texts)), 0, 50000);
        $this->writeOcrCache($cacheKey, $joined);

        return $joined;
    }

    /**
     * Chama o executável do Tesseract OCR via thiagoalessio/tesseract_ocr.
     */
    private function runTesseractOnFile(string $imagePath): string
    {
        $binary = $this->resolveTesseractExecutable();
        if ($binary === null || ! is_file($imagePath)) {
            return '';
        }

        try {
            $langCfg = $this->resolveTesseractLanguageConfig($binary);
            $ocr     = (new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath))
                ->executable($binary)
                ->lang($langCfg['lang'])
                ->psm(3);

            if ($langCfg['tessdata_dir'] !== null) {
                $ocr->tessdataDir($langCfg['tessdata_dir']);
            }

            $rawText = (string) $ocr->run();
            return $this->cleanOcrText($rawText);
        } catch (Throwable) {
            return '';
        }
    }

    private function cleanOcrText(string $text): string
    {
        $text = (string) preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        $text = (string) preg_replace('/[^\P{C}\n\t]+/u', '', $text);
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string) preg_replace('/\R{2,}/u', "\n", $text);
        return mb_substr(trim($text), 0, 50000);
    }

    private function readOcrCache(string $cacheKey): ?string
    {
        $path = storage_path('app/ocr_cache/' . $cacheKey . '.txt');
        if (! is_file($path)) {
            return null;
        }
        $content = @file_get_contents($path);
        return $content === false ? null : $content;
    }

    private function writeOcrCache(string $cacheKey, string $text): void
    {
        $dir = storage_path('app/ocr_cache');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . $cacheKey . '.txt', $text);
    }

    /**
     * Extrai campos básicos de XML de NF-e brasileira (se aplicável).
     *
     * @return array<string, mixed>|null
     */
    private function parseNfeXml(string $xmlContent): ?array
    {
        if (! str_contains($xmlContent, '<nNF>') && ! str_contains($xmlContent, '<vNF>')) {
            return null;
        }

        $getTag = static function (string $tag) use ($xmlContent): string {
            if (preg_match('/<' . $tag . '>([^<]+)<\/' . $tag . '>/i', $xmlContent, $m)) {
                return trim($m[1]);
            }
            return '';
        };

        $nNF    = $getTag('nNF');
        $vNF    = (float) str_replace(',', '.', $getTag('vNF'));
        $xNome  = $getTag('xNome');
        $cnpj   = preg_replace('/\D/', '', $getTag('CNPJ')) ?? '';
        $dhEmi  = $getTag('dhEmi') ?: $getTag('dEmi');
        $dateBr = '';

        if ($dhEmi !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dhEmi, $dm)) {
            $dateBr = "{$dm[3]}/{$dm[2]}/{$dm[1]}";
        }

        if (strlen($cnpj) === 14) {
            $cnpj = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
        }

        $summaryLine = "NF-e nº {$nNF}"
            . ($xNome !== '' ? " — Emitente: {$xNome}" : '')
            . ($cnpj !== '' ? " (CNPJ {$cnpj})" : '')
            . ($dateBr !== '' ? " — Emissão: {$dateBr}" : '')
            . ($vNF > 0 ? ' — Valor: ' . $this->formatMoney($vNF) : '');

        return [
            'number'       => $nNF,
            'value'        => $vNF,
            'emitter'      => $xNome,
            'cnpj'         => $cnpj,
            'date'         => $dateBr,
            'summary_line' => $summaryLine,
        ];
    }

    /**
     * @return array<int, float>
     */
    private function extractCurrencyValues(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $values = [];

        // 1. Valores explicitamente precedidos por R$ (com ou sem centavos)
        if (preg_match_all('/R\$\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{2})?|[0-9]+(?:,[0-9]{2})?)/iu', $text, $rsMatches)) {
            foreach ($rsMatches[1] as $raw) {
                $norm = str_replace('.', '', $raw);
                $norm = str_replace(',', '.', $norm);
                $val  = (float) $norm;
                if ($val > 0 && $val < 1000000000) {
                    $values[] = round($val, 2);
                }
            }
        }

        // 2. Valores associados a rótulos financeiros (mesmo sem "R$")
        if (preg_match_all('/(?:valor(?:\s+total|\s+l[íi]quido|\s+bruto|\s+unit[áa]rio)?|total(?:\s+geral)?|taxa|mensalidade|empenho|saldo|receita|despesa|pagamento|investimento|custo|pre[çc]o)\s*(?:de)?\s*[:\-]?\s*(?:R\$\s*)?([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2}|[0-9]+,[0-9]{2})/iu', $text, $lblMatches)) {
            foreach ($lblMatches[1] as $raw) {
                $norm = str_replace('.', '', $raw);
                $norm = str_replace(',', '.', $norm);
                $val  = (float) $norm;
                if ($val > 0 && $val < 1000000000) {
                    $values[] = round($val, 2);
                }
            }
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function extractCnpjs(string $text): array
    {
        if (preg_match_all('/\b(\d{2})[\.,](\d{3})[\.,](\d{3})\/(\d{4})-(\d{2})\b/', $text, $m, PREG_SET_ORDER)) {
            $out = [];
            foreach ($m as $match) {
                $out[] = "{$match[1]}.{$match[2]}.{$match[3]}/{$match[4]}-{$match[5]}";
            }
            return array_values(array_unique($out));
        }
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function extractCpfs(string $text): array
    {
        if (preg_match_all('/\b(\d{3})[\.,](\d{3})[\.,](\d{3})-(\d{2})\b/', $text, $m, PREG_SET_ORDER)) {
            $out = [];
            foreach ($m as $match) {
                $out[] = "{$match[1]}.{$match[2]}.{$match[3]}-{$match[4]}";
            }
            return array_values(array_unique($out));
        }
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function extractDates(string $text): array
    {
        if (preg_match_all('/\b(?:0[1-9]|[12]\d|3[01])\/(?:0[1-9]|1[0-2])\/(?:19|20)\d{2}\b/', $text, $m)) {
            return array_values(array_unique($m[0]));
        }
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function extractDocumentNumbers(string $text): array
    {
        $found = [];
        if (preg_match_all('/\b(?:NF-?e?|Nota\s+Fiscal|Empenho|Contrato|Processo|ART|Medição|Medicao|Ordem\s+de\s+Serviço|OS)\s*(?:n[º°o\.]*)?\s*[:\-]?\s*([0-9][0-9\.\/\-]{1,18})/iu', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $found[] = trim(preg_replace('/\s+/', ' ', $match[0]) ?? '');
            }
        }
        return array_values(array_unique($found));
    }

    private function inferDocumentType(int $itemNum, string $originalName, string $ext, string $text): string
    {
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tif', 'tiff'], true);
        if ($ext === 'zip') {
            return 'Pacote Compactado (ZIP)';
        }

        $haystack = $this->normalizeForMatch($originalName . ' ' . mb_substr($text, 0, 1200));

        $map = [
            'nota fiscal'    => 'Nota Fiscal',
            'danfe'          => 'Nota Fiscal (DANFE)',
            'empenho'        => 'Nota de Empenho',
            'balancete'      => 'Balancete',
            'extrato'        => 'Extrato Bancário',
            'conciliacao'    => 'Conciliação Bancária',
            'laudo'          => 'Laudo Técnico',
            'medicao'        => 'Medição',
            'ordem de serv'  => 'Ordem de Serviço',
            'parecer'        => 'Parecer',
            'certidao'       => 'Certidão (CND)',
            'cnd'            => 'Certidão (CND)',
            'art'            => 'ART',
            'comprovante'    => 'Comprovante',
            'recibo'         => 'Recibo',
            'termo'          => 'Termo',
            'relatorio'      => 'Relatório',
        ];

        foreach ($map as $kw => $label) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $haystack)) {
                return $isImage ? "{$label} (OCR)" : $label;
            }
        }

        if ($isImage) {
            return mb_strlen(trim($text)) >= 10 ? 'Imagem / Foto (OCR)' : 'Imagem / Foto';
        }

        $byItem = [
            1  => 'Balancete',
            2  => 'Nota de Empenho',
            3  => 'Nota Fiscal',
            4  => 'Comprovante Tributário',
            5  => 'Ordem de Serviço',
            6  => 'Medição',
            7  => 'Laudo Técnico',
            8  => 'Acervo Fotográfico',
            10 => 'ART',
            13 => 'Extrato Bancário',
            14 => 'Extrato de Aplicação',
            15 => 'Conciliação Bancária',
            18 => 'Parecer de Controle Interno',
            19 => 'CND',
            22 => 'Relatório Físico-Financeiro',
        ];

        return $byItem[$itemNum] ?? 'Documento PDF';
    }

    /**
     * @param array<int, string> $tokens
     */
    private function extractBestSnippet(string $text, array $tokens): string
    {
        if (trim($text) === '') {
            return '';
        }

        $normText = $this->normalizeForMatch($text);
        foreach ($tokens as $tok) {
            $pos = mb_strpos($normText, $tok);
            if ($pos !== false) {
                $start   = max(0, $pos - 80);
                $snippet = trim(mb_substr($text, $start, 220));
                return (string) preg_replace('/\s+/u', ' ', $snippet);
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function extractWords(string $text): array
    {
        $norm = $this->normalizeForMatch($text);
        $raw  = preg_split('/[^a-z0-9]+/u', $norm) ?: [];
        $out  = [];
        foreach ($raw as $w) {
            if (strlen($w) >= 3 && ! isset($this->stopwords[$w]) && ! ctype_digit($w)) {
                $out[] = $w;
            }
        }
        return $out;
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeQuery(string $query): array
    {
        $norm = $this->normalizeForMatch($query);
        $raw  = preg_split('/[^a-z0-9\.\/\-]+/u', $norm) ?: [];
        $out  = [];
        foreach ($raw as $w) {
            $w = trim($w, '.-/');
            if (strlen($w) >= 2 && ! isset($this->stopwords[$w])) {
                $out[] = $w;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @param array<int, string> $keywords
     */
    private function matchesAny(string $normQuery, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($normQuery, $kw)) {
                return true;
            }
        }
        return false;
    }

    private function normalizeForMatch(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $map   = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        return strtr($lower, $map);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'enviado'       => 'Enviado',
            'confirmado'    => 'Confirmado',
            'rejeitado'     => 'Rejeitado',
            'prazo_vencido' => 'Prazo vencido',
            default         => 'Pendente',
        };
    }

    private function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }
        return $bytes . ' B';
    }
}
