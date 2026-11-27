<?php

namespace App\Services;

use App\Exceptions\ActionException;
use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Support\Actor;
use App\Support\UploadedFiles;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Regras de anexos das prestações de contas (compartilhadas entre Web e API mobile).
 */
class AttachmentService
{
    public const ALLOWED_EXTENSIONS = ['zip', 'jpg', 'jpeg', 'png', 'webp', 'pdf'];
    public const MAX_BYTES          = 15 * 1024 * 1024;
    public const INVALID_TYPE_MSG   = 'Formato inválido. Use PDF, JPG, PNG, WEBP ou ZIP.';

    /**
     * Verifica se o usuário pode anexar arquivos no item da prestação.
     *
     * @throws ActionException
     */
    public function assertCanUpload(Actor $actor, int $accountabilityId, int $itemNumber): Accountability
    {
        $acc = Accountability::find($accountabilityId);
        if ($acc === null) {
            throw ActionException::notFound('Prestação de contas não encontrada.');
        }

        $enabled = Accountability::getEnabledItemsForAccountability($acc->toArray());
        if (! isset($enabled[$itemNumber])) {
            throw new ActionException('Campo de anexo inválido.');
        }

        if (! $actor->isAdmin) {
            if (! $actor->canAccessItem($itemNumber)) {
                throw ActionException::forbidden("Você não possui permissão para anexar arquivos no Item {$itemNumber}.");
            }

            if ($this->itemStatus($accountabilityId, $itemNumber) === 'confirmado') {
                throw ActionException::forbidden('Este item já foi confirmado pelo administrador.');
            }
        }

        return $acc;
    }

    /**
     * Valida e grava um arquivo enviado. Não registra histórico (o chamador agrupa os envios).
     *
     * @throws ActionException
     */
    public function store(Actor $actor, int $accountabilityId, int $itemNumber, UploadedFile $file): AccountabilityAttachment
    {
        $ext = UploadedFiles::validate($file, self::ALLOWED_EXTENSIONS, self::MAX_BYTES, self::INVALID_TYPE_MSG);

        $relDir = 'uploads/accountability/' . $accountabilityId . '/item_' . $itemNumber;
        UploadedFiles::ensureDirectory(storage_path('app/' . $relDir));

        $originalName = $file->getClientOriginalName() ?: ('anexo_' . time() . '.' . $ext);
        $size         = (int) $file->getSize();
        $newName      = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move(storage_path('app/' . $relDir), $newName);

        return AccountabilityAttachment::create([
            'accountability_id' => $accountabilityId,
            'item_number'       => $itemNumber,
            'user_id'           => $actor->id,
            'original_name'     => $originalName,
            'file_name'         => $newName,
            'file_path'         => $relDir . '/' . $newName,
            'file_ext'          => $ext,
            'file_size'         => $size,
            'created_at'        => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Busca um anexo que o usuário pode visualizar/baixar.
     *
     * @throws ActionException
     */
    public function findViewable(Actor $actor, int $attachmentId): AccountabilityAttachment
    {
        $att = AccountabilityAttachment::find($attachmentId);
        if ($att === null) {
            throw ActionException::notFound('Anexo não encontrado.');
        }

        if (! $actor->canAccessItem((int) $att->item_number)) {
            throw ActionException::forbidden('Você não tem permissão para acessar este anexo.');
        }

        return $att;
    }

    /**
     * @throws ActionException
     */
    public function inlineResponse(AccountabilityAttachment $att): BinaryFileResponse
    {
        return UploadedFiles::inlineResponse((string) $att->file_path, (string) $att->original_name, (string) $att->file_ext);
    }

    /**
     * Exclui um anexo. Usuários comuns só excluem anexos próprios, em itens liberados e ainda não confirmados.
     *
     * @throws ActionException
     */
    public function delete(Actor $actor, int $attachmentId): AccountabilityAttachment
    {
        $att = AccountabilityAttachment::find($attachmentId);
        if ($att === null) {
            throw ActionException::notFound('Anexo não encontrado.');
        }

        $accId    = (int) $att->accountability_id;
        $itemNum  = (int) $att->item_number;
        $origName = (string) $att->original_name;

        if (! $actor->isAdmin) {
            if (! $actor->canAccessItem($itemNum) || (int) $att->user_id !== $actor->id) {
                throw ActionException::forbidden('Você só pode excluir anexos enviados por você.');
            }
            if ($this->itemStatus($accId, $itemNum) === 'confirmado') {
                throw ActionException::forbidden('Este item já foi confirmado pelo administrador.');
            }
        }

        UploadedFiles::deleteStored((string) $att->file_path);
        $this->deleteLinkedSpreadsheetEntries($accId, $itemNum, $attachmentId, $origName);
        $att->delete();

        // Se não restar nenhum anexo neste item, volta o status para 'pendente'
        $remaining = AccountabilityAttachment::where('accountability_id', $accId)
            ->where('item_number', $itemNum)
            ->count();
        if ($remaining === 0) {
            AccountabilityItemStatus::setStatus($accId, $itemNum, 'pendente', $actor->id, null);
        }

        AccountabilityHistory::log($accId, $actor->id, "Removeu anexo '{$origName}' do Item {$itemNum}");

        return $att;
    }

    private function itemStatus(int $accountabilityId, int $itemNumber): ?string
    {
        $row = AccountabilityItemStatus::where('accountability_id', $accountabilityId)
            ->where('item_number', $itemNumber)
            ->first();

        return $row?->status;
    }

    private function deleteLinkedSpreadsheetEntries(int $accId, int $itemNum, int $attachmentId, string $origName): void
    {
        try {
            AccountabilitySpreadsheetEntry::where('accountability_id', $accId)
                ->where(static function ($q) use ($attachmentId, $itemNum, $origName) {
                    $q->where('attachment_id', $attachmentId);
                    if ($origName !== '') {
                        $q->orWhere(static function ($q2) use ($itemNum, $origName) {
                            $q2->where('item_number', $itemNum)
                               ->where('notes', 'like', '%' . $origName . '%');
                        });
                    }
                })
                ->delete();
        } catch (Throwable) {
            // Não impede a exclusão do anexo
        }
    }
}
