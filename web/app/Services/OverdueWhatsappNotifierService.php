<?php

namespace App\Services;

use App\Models\Accountability;
use App\Models\AccountabilityItemStatus;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Support\Facades\DB;
use Throwable;

class OverdueWhatsappNotifierService
{
    /**
     * Varre todas as prestações com prazo vencido e envia aviso no WhatsApp
     * para cada usuário responsável pelos itens pendentes/rejeitados/vencidos.
     *
     * @return array{
     *   checked_accountabilities: int,
     *   candidates: int,
     *   sent: int,
     *   skipped_already_sent: int,
     *   failed: int,
     *   previews: array<int, array<string, mixed>>,
     *   errors: array<int, string>
     * }
     */
    public function notifyOverdue(bool $dryRun = false, bool $force = false): array
    {
        $today = date('Y-m-d');

        $result = [
            'checked_accountabilities' => 0,
            'candidates'               => 0,
            'sent'                     => 0,
            'skipped_already_sent'     => 0,
            'failed'                   => 0,
            'previews'                 => [],
            'errors'                   => [],
        ];

        $overdueAccs = Accountability::whereNotNull('deadline')
            ->where('deadline', '<', $today)
            ->where(static function ($q) {
                $q->whereNull('is_paused')->orWhere('is_paused', 0);
            })
            ->orderBy('deadline', 'asc')
            ->get();

        $result['checked_accountabilities'] = $overdueAccs->count();
        if ($overdueAccs->isEmpty()) {
            return $result;
        }

        // Mapeia permissões: item_number => [user_id, ...]
        $permsByItem = [];
        foreach (UserAccountabilityPermission::all() as $perm) {
            $permsByItem[(int) $perm->item_number][] = (int) $perm->user_id;
        }

        // Carrega usuários que possuem WhatsApp cadastrado
        $usersWithWhatsapp = [];
        foreach (User::whereNotNull('whatsapp')->get() as $u) {
            $phone = trim((string) $u->whatsapp);
            if ($phone !== '') {
                $usersWithWhatsapp[(int) $u->id] = [
                    'id'       => (int) $u->id,
                    'name'     => trim((string) $u->name),
                    'whatsapp' => $phone,
                ];
            }
        }

        $openWa = new OpenWaService();

        foreach ($overdueAccs as $accObj) {
            $acc          = $accObj->toArray();
            $accId        = (int) $acc['id'];
            $accTitle     = trim((string) ($acc['title'] ?? "Prestação #{$accId}"));
            $deadlineYmd  = (string) $acc['deadline'];
            $enabledItems = Accountability::getEnabledItemsForAccountability($acc);

            $savedStatuses = [];
            foreach (AccountabilityItemStatus::where('accountability_id', $accId)->get() as $stRow) {
                $savedStatuses[(int) $stRow->item_number] = (string) $stRow->status;
            }

            foreach ($enabledItems as $itemNum => $itemLabel) {
                $itemNum = (int) $itemNum;
                $status  = $savedStatuses[$itemNum] ?? 'prazo_vencido';

                // Ignora itens já enviados ou confirmados
                if (in_array($status, ['enviado', 'confirmado'], true)) {
                    continue;
                }

                $responsibleUserIds = array_values(array_unique($permsByItem[$itemNum] ?? []));
                if (empty($responsibleUserIds)) {
                    continue;
                }

                foreach ($responsibleUserIds as $uid) {
                    if (! isset($usersWithWhatsapp[$uid])) {
                        continue;
                    }

                    $user = $usersWithWhatsapp[$uid];
                    $result['candidates']++;

                    if (! $force && $this->wasAlreadyNotifiedToday($accId, $itemNum, $uid, $today)) {
                        $result['skipped_already_sent']++;
                        continue;
                    }

                    $portalUrl = rtrim((string) config('app.url', 'http://localhost:8000'), '/') . '/accountability/' . $accId;
                    $message   = $this->buildOverdueMessage(
                        $user['name'],
                        $itemNum,
                        (string) $itemLabel,
                        $accTitle,
                        $deadlineYmd,
                        $portalUrl
                    );

                    if ($dryRun) {
                        $result['previews'][] = [
                            'accountability_id' => $accId,
                            'item_number'       => $itemNum,
                            'user_name'         => $user['name'],
                            'whatsapp'          => $user['whatsapp'],
                            'message'           => $message,
                        ];
                        continue;
                    }

                    $sendRes = $openWa->sendText($user['whatsapp'], $message);
                    $ok      = ! empty($sendRes['success']);

                    $this->logNotification(
                        $accId,
                        $itemNum,
                        $uid,
                        $user['whatsapp'],
                        $message,
                        $today,
                        $ok ? 'sent' : 'failed',
                        $ok ? null : (string) ($sendRes['error'] ?? 'Falha no envio')
                    );

                    if ($ok) {
                        $result['sent']++;
                    } else {
                        $result['failed']++;
                        $result['errors'][] = "{$user['name']} (Item {$itemNum}): " . ($sendRes['error'] ?? 'Erro desconhecido');
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Monta a mensagem personalizada em português com concordância de gênero (masculino/feminino)
     * e número (singular/plural) com base no nome do usuário, item do checklist e título da prestação.
     */
    public function buildOverdueMessage(
        string $userName,
        int $itemNumber,
        string $itemLabel,
        string $accountabilityTitle,
        string $deadlineYmd,
        ?string $portalUrl = null
    ): string {
        $firstName  = $this->extractFirstName($userName);
        $nameGender = $this->detectNameGender($userName);
        $grammar    = $this->getItemGrammar($itemNumber, $itemLabel);
        $prep       = $this->getTitlePreposition($accountabilityTitle);
        $dateBr     = $this->formatDateBr($deadlineYmd);

        $greeting = match ($nameGender) {
            'f'     => "Olá, {$firstName}! Prezada, {$grammar['article']} {$grammar['label']}",
            'm'     => "Olá, {$firstName}! Prezado, {$grammar['article']} {$grammar['label']}",
            default => "Olá, {$firstName}! " . $this->mbUcfirst($grammar['article']) . " {$grammar['label']}",
        };

        $cleanTitle = trim($accountabilityTitle, " \t\n\r\0\x0B.;");

        $msg = "{$greeting} {$prep} {$cleanTitle} {$grammar['verb']} na data {$dateBr}. "
            . "Por gentileza, acesse o portal e envie {$grammar['doc_phrase']}.";

        if ($portalUrl !== null && trim($portalUrl) !== '') {
            $msg .= "\n\nAcesse o portal: " . trim($portalUrl);
        }

        return $msg;
    }

    /**
     * Detecta o gênero provável de um nome brasileiro ('f' = feminino, 'm' = masculino, 'n' = neutro).
     */
    public function detectNameGender(string $fullName): string
    {
        $first = $this->normalizeAscii($this->extractFirstName($fullName));
        if ($first === '' || strlen($first) < 2) {
            return 'n';
        }

        // Nomes masculinos que terminam em 'a' ou exceções comuns
        $maleExceptions = [
            'lucas', 'matheus', 'mateus', 'douglas', 'elias', 'isaias', 'josias', 'tobias',
            'jonas', 'tomas', 'thomas', 'nicolas', 'nikolas', 'alex', 'felix', 'carlos',
            'marcos', 'vinicius', 'marcius', 'luan', 'renan', 'alan', 'allan', 'jean',
            'ryan', 'bryan', 'jonathan', 'nathan', 'christian', 'cristian', 'willian',
            'william', 'anderson', 'emerson', 'jefferson', 'robson', 'edson', 'nelson',
            'wilson', 'hudson', 'alisson', 'alysson', 'maycon', 'maicon', 'cleiton',
            'clayton', 'wellington', 'washington', 'everton', 'weverton', 'airton',
            'ayrton', 'milton', 'nilton', 'hamilton', 'gilson', 'valdir', 'aldemir',
            'ademir', 'vladimir', 'jair', 'claudemir', 'vanderlei', 'wanderlei', 'sidnei',
            'sidney', 'wesley', 'kaua', 'caua', 'luka', 'luca', 'joshua', 'nikita',
            'andre', 'felipe', 'phillipe', 'guilherme', 'alexandre', 'jorge', 'henrique',
            'jose', 'josue', 'davi', 'david', 'levi', 'yuri', 'iuri', 'dante', 'vicente',
            'clemente', 'valente', 'igor', 'vitor', 'victor', 'heitor', 'artur', 'arthur',
            'cesar', 'cezar', 'oscar', 'omar', 'edgar', 'walter', 'valter', 'wagner',
            'vagner', 'roger', 'kleber', 'cleber', 'herbert', 'robert', 'albert',
            'gabriel', 'rafael', 'daniel', 'miguel', 'samuel', 'emanuel', 'israel',
            'michael', 'maikel', 'joel', 'noel', 'abel', 'uriel', 'ariel', 'manoel',
            'manuel', 'joao', 'pedro', 'paulo', 'thiago', 'tiago', 'diego', 'diogo',
            'rodrigo', 'bruno', 'breno', 'caio', 'fabio', 'flavio', 'mario', 'sergio',
            'marcio', 'lucio', 'julio', 'claudio', 'otavio', 'antonio', 'fernando',
            'eduardo', 'ricardo', 'roberto', 'alberto', 'gilberto', 'humberto', 'norberto',
            'leonardo', 'bernardo', 'gustavo', 'leandro', 'sandro', 'evandro', 'alessandro',
            'alexsandro', 'marcelo', 'danilo', 'murilo', 'camilo', 'italo', 'saulo',
            'mauro', 'lauro', 'renato', 'adriano', 'juliano', 'fabiano', 'luciano',
            'cristiano', 'cassiano', 'mariano', 'emiliano', 'sebastiao', 'rafael', 'sidemar',
        ];

        // Nomes femininos que NÃO terminam em 'a'
        $femaleExceptions = [
            'beatriz', 'raquel', 'rachel', 'isabel', 'izabel', 'ester', 'esther', 'ines',
            'inez', 'alice', 'aline', 'simone', 'denise', 'caroline', 'karoline', 'elaine',
            'helaine', 'eliane', 'liliane', 'viviane', 'juliane', 'luciane', 'mariane',
            'tatiane', 'cristiane', 'fabiane', 'regiane', 'rosane', 'josiane', 'daiane',
            'dayane', 'rayane', 'raiane', 'liane', 'diane', 'jeane', 'jane', 'ivone',
            'cleide', 'neide', 'marlene', 'darlene', 'arlene', 'irene', 'selene', 'helene',
            'solange', 'elisangela', 'rosangela', 'gisele', 'giselle', 'michele', 'michelle',
            'danielle', 'daniele', 'gabrielle', 'gabriele', 'isabelle', 'isabele', 'nicole',
            'nichole', 'camille', 'camile', 'emilly', 'emily', 'kelly', 'kely', 'sueli',
            'suely', 'shirley', 'sirlene', 'marli', 'marly', 'naomi', 'noemi', 'rute',
            'ruth', 'judite', 'edith', 'edite', 'odete', 'ivete', 'elizete', 'elisete',
            'janete', 'marinete', 'gorete', 'claudete', 'salete', 'bernadete', 'antonieta',
            'lais', 'thais', 'tais', 'iris', 'isis', 'clarice', 'clarise', 'eunice',
            'janice', 'berenice', 'joice', 'joyce', 'grace', 'gleice', 'gleyce', 'ingrid',
            'astrid', 'carmen', 'carmem', 'miriam', 'mirian', 'lilian', 'vivian', 'karen',
            'ellen', 'helen', 'heloise', 'louise', 'luise', 'monique', 'veronique', 'dominique',
            'yasmin', 'iasmin', 'jasmim', 'jennifer', 'jenifer', 'stephanie', 'stefany',
            'tiffany', 'kimberly', 'ashley', 'lindsay', 'mary', 'rose', 'roseli', 'rosely',
            'luceli', 'araceli', 'cibele', 'sibele', 'estefani', 'estefany', 'ketlin',
            'kethlyn', 'evelyn', 'evelin', 'marilyn', 'carol', 'mabel', 'anabel', 'liz',
        ];

        $neutralWords = ['admin', 'administrador', 'usuario', 'equipe', 'setor', 'prefeitura', 'municipio', 'suporte', 'teste'];

        if (in_array($first, $neutralWords, true)) {
            return 'n';
        }
        if (in_array($first, $maleExceptions, true)) {
            return 'm';
        }
        if (in_array($first, $femaleExceptions, true)) {
            return 'f';
        }

        // Regra geral do português brasileiro
        if (str_ends_with($first, 'a') || str_ends_with($first, 'ane') || str_ends_with($first, 'ene') || str_ends_with($first, 'ine') || str_ends_with($first, 'ete')) {
            return 'f';
        }
        if (str_ends_with($first, 'o') || str_ends_with($first, 'os') || str_ends_with($first, 'or') || str_ends_with($first, 'on') || str_ends_with($first, 'er') || str_ends_with($first, 'el')) {
            return 'm';
        }

        return 'n';
    }

    /**
     * Retorna artigo, verbo, frase final e rótulo limpo para cada item do checklist (1..24).
     *
     * @return array{article: string, verb: string, doc_phrase: string, label: string}
     */
    public function getItemGrammar(int $itemNumber, string $itemLabel): array
    {
        $clean = trim($itemLabel, " \t\n\r\0\x0B.;");

        $map = [
            1  => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            2  => ['article' => 'as', 'verb' => 'venceram', 'doc_phrase' => 'os documentos'],
            3  => ['article' => 'as', 'verb' => 'venceram', 'doc_phrase' => 'os documentos'],
            4  => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            5  => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            6  => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            7  => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            8  => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            9  => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            10 => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            11 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            12 => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            13 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            14 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            15 => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            16 => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            17 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            18 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            19 => ['article' => 'a',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            20 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            21 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            22 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            23 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
            24 => ['article' => 'o',  'verb' => 'venceu',   'doc_phrase' => 'o documento'],
        ];

        if (isset($map[$itemNumber])) {
            return array_merge($map[$itemNumber], ['label' => $clean]);
        }

        // Fallback gramatical automático para itens customizados
        $firstWord = $this->normalizeAscii(explode(' ', $clean)[0] ?? '');
        if (str_ends_with($firstWord, 'as') || str_ends_with($firstWord, 'oes')) {
            return ['article' => 'as', 'verb' => 'venceram', 'doc_phrase' => 'os documentos', 'label' => $clean];
        }
        if (str_ends_with($firstWord, 'os')) {
            return ['article' => 'os', 'verb' => 'venceram', 'doc_phrase' => 'os documentos', 'label' => $clean];
        }
        if (str_ends_with($firstWord, 'a') || str_ends_with($firstWord, 'cao') || str_ends_with($firstWord, 'sao') || str_ends_with($firstWord, 'dade') || $firstWord === 'ordem') {
            return ['article' => 'a', 'verb' => 'venceu', 'doc_phrase' => 'o documento', 'label' => $clean];
        }

        return ['article' => 'o', 'verb' => 'venceu', 'doc_phrase' => 'o documento', 'label' => $clean];
    }

    /**
     * Define a preposição adequada (da/do/das/dos/de) para ligar o item ao título da prestação.
     */
    public function getTitlePreposition(string $title): string
    {
        $firstWord = $this->normalizeAscii(explode(' ', trim($title))[0] ?? '');
        if ($firstWord === '') {
            return 'de';
        }

        $femSing = [
            'aquisicao', 'reforma', 'construcao', 'pavimentacao', 'ampliacao', 'implantacao',
            'manutencao', 'recuperacao', 'instalacao', 'compra', 'obra', 'creche', 'escola',
            'unidade', 'estrada', 'ponte', 'praca', 'quadra', 'iluminacao', 'drenagem',
            'sinalizacao', 'revitalizacao', 'modernizacao', 'adequacao', 'contratacao',
            'prestacao', 'execucao', 'perfuracao', 'edificacao', 'urbanizacao', 'estruturacao',
            'capacitacao', 'locacao', 'entrega', 'substituicao', 'restauracao',
        ];
        $femPlur = ['obras', 'reformas', 'compras', 'maquinas', 'estradas', 'escolas', 'unidades', 'aquisicoes'];
        $mascSing = [
            'convenio', 'contrato', 'projeto', 'programa', 'termo', 'plano', 'fundo', 'sistema',
            'equipamento', 'veiculo', 'caminhao', 'trator', 'onibus', 'hospital', 'posto',
            'centro', 'ginasio', 'mercado', 'saneamento', 'asfalto', 'calcamento', 'custeio',
            'investimento', 'recurso', 'repasse', 'financiamento', 'consorcio', 'fornecimento',
            'aparelhamento', 'melhoramento', 'servicos',
        ];
        $mascPlur = ['equipamentos', 'veiculos', 'recursos', 'projetos', 'convenios', 'servicos', 'materiais'];

        if (in_array($firstWord, $femSing, true)) {
            return 'da';
        }
        if (in_array($firstWord, $femPlur, true)) {
            return 'das';
        }
        if (in_array($firstWord, $mascPlur, true)) {
            return 'dos';
        }
        if (in_array($firstWord, $mascSing, true)) {
            return 'do';
        }

        if (str_ends_with($firstWord, 'cao') || str_ends_with($firstWord, 'sao') || str_ends_with($firstWord, 'dade') || str_ends_with($firstWord, 'a')) {
            return 'da';
        }
        if (str_ends_with($firstWord, 'as')) {
            return 'das';
        }
        if (str_ends_with($firstWord, 'os')) {
            return 'dos';
        }
        if (str_ends_with($firstWord, 'o') || str_ends_with($firstWord, 'ento') || str_ends_with($firstWord, 'r')) {
            return 'do';
        }

        return 'de';
    }

    private function wasAlreadyNotifiedToday(int $accId, int $itemNum, int $userId, string $today): bool
    {
        try {
            return DB::table('whatsapp_overdue_notifications')
                ->where('accountability_id', $accId)
                ->where('item_number', $itemNum)
                ->where('user_id', $userId)
                ->where('notified_on', $today)
                ->where('status', 'sent')
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function logNotification(
        int $accId,
        int $itemNum,
        int $userId,
        string $phone,
        string $message,
        string $today,
        string $status,
        ?string $errorMessage
    ): void {
        try {
            DB::table('whatsapp_overdue_notifications')->insert([
                'accountability_id' => $accId,
                'item_number'       => $itemNum,
                'user_id'           => $userId,
                'phone'             => $phone,
                'message'           => $message,
                'notified_on'       => $today,
                'status'            => $status,
                'error_message'     => $errorMessage,
                'created_at'        => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // Não interrompe o lote se o log falhar
        }
    }

    private function extractFirstName(string $fullName): string
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
        $first = trim((string) ($parts[0] ?? ''));
        return $first !== '' ? $first : 'Responsável';
    }

    private function formatDateBr(string $ymd): string
    {
        $ts = strtotime($ymd);
        return $ts !== false ? date('d/m/Y', $ts) : $ymd;
    }

    private function normalizeAscii(string $text): string
    {
        $lower = mb_strtolower(trim($text), 'UTF-8');
        $map   = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        return preg_replace('/[^a-z]/', '', strtr($lower, $map)) ?? '';
    }

    private function mbUcfirst(string $str): string
    {
        if ($str === '') {
            return '';
        }
        return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($str, 1, null, 'UTF-8');
    }
}
