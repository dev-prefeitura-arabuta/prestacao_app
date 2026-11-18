<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountabilityModel extends Model
{
    protected $table            = 'public_accountability';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'description',
        'status',
        'deadline',
        'enabled_items',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Os 24 itens oficiais de anexos da Prestação de Contas.
     *
     * @return array<int, string>
     */
    public static function getItems(): array
    {
        return [
            1  => 'Balancete de prestação de contas - receita/despesa/saldos;',
            2  => 'Cópias das notas de empenho;',
            3  => 'Cópias de notas fiscais;',
            4  => 'Comprovante de pagamento de encargos tributários incidentes sobre cada etapa executada;',
            5  => 'Ordem de serviço;',
            6  => 'Medição do período completo;',
            7  => 'Laudo técnico de engenharia - relatório simplificado com descrição das etapas executadas;',
            8  => 'Acervo fotográfico atual do objeto;',
            9  => 'Relação de bens adquiridos/produzidos/construídos;',
            10 => 'Anotação de Responsabilidade Técnica (ART) de execução e fiscalização;',
            11 => 'Aporte da contrapartida - comprovado no extrato bancário da conta específica;',
            12 => 'Execução da contrapartida - quando em bens e serviços;',
            13 => 'Extrato bancário com movimentação completa do período;',
            14 => 'Extrato bancário completo da aplicação financeira;',
            15 => 'Conciliação bancária (se houver);',
            16 => 'Identificação oficial da marca do Governo do Estado - placa de obras;',
            17 => 'Cadastro Nacional de Obras (CNO) - até 30 (trinta) dias do início da obra;',
            18 => 'Parecer do órgão de controle interno municipal;',
            19 => 'Certidão Negativa de Débitos (CND) da obra - final de obras;',
            20 => 'Comprovante de devolução de saldos;',
            21 => 'Comprovante de recolhimento de rendimento de recursos;',
            22 => 'Relatório de execução físico-financeira;',
            23 => 'Termo de aceitação definitiva do objeto;',
            24 => 'Termo de encerramento da execução do objeto - fim da execução dos recursos, para consolidação do Termo de Compromisso.',
        ];
    }

    /**
     * Resumo em uma única palavra para cada um dos 24 itens da Prestação de Contas.
     *
     * @return array<int, string>
     */
    public static function getShortItems(): array
    {
        return [
            1  => 'Balancete',
            2  => 'Empenho',
            3  => 'Notas',
            4  => 'Encargos',
            5  => 'Ordem',
            6  => 'Medição',
            7  => 'Laudo',
            8  => 'Fotos',
            9  => 'Bens',
            10 => 'ART',
            11 => 'Aporte',
            12 => 'Contrapartida',
            13 => 'Extrato',
            14 => 'Aplicação',
            15 => 'Conciliação',
            16 => 'Placa',
            17 => 'CNO',
            18 => 'Parecer',
            19 => 'CND',
            20 => 'Devolução',
            21 => 'Rendimento',
            22 => 'Relatório',
            23 => 'Aceitação',
            24 => 'Encerramento',
        ];
    }

    /**
     * Retorna apenas os itens (1..24) habilitados para uma Prestação de Contas específica.
     * Caso `enabled_items` seja nulo/vazio (registros antigos), retorna todos os 24 itens.
     *
     * @param array<string, mixed> $accountability
     * @return array<int, string>
     */
    public static function getEnabledItemsForAccountability(array $accountability): array
    {
        $allItems = self::getItems();
        $raw      = $accountability['enabled_items'] ?? null;

        if (empty($raw)) {
            return $allItems;
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (! is_array($decoded) || empty($decoded)) {
            return $allItems;
        }

        $enabledNums = array_map('intval', $decoded);
        $filtered    = [];

        foreach ($allItems as $num => $label) {
            if (in_array((int) $num, $enabledNums, true)) {
                $filtered[(int) $num] = $label;
            }
        }

        return ! empty($filtered) ? $filtered : $allItems;
    }
}
