<?php

namespace App\Support;

use App\Exceptions\ActionException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Validação de uploads e entrega de arquivos armazenados em storage/app.
 */
final class UploadedFiles
{
    private const MIME_TO_EXT = [
        'image/jpeg'                   => 'jpg',
        'image/jpg'                    => 'jpg',
        'image/pjpeg'                  => 'jpg',
        'image/png'                    => 'png',
        'image/webp'                   => 'webp',
        'application/pdf'              => 'pdf',
        'application/zip'              => 'zip',
        'application/x-zip-compressed' => 'zip',
    ];

    private const EXT_TO_MIME = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'zip'  => 'application/zip',
    ];

    /**
     * Garante que o arquivo foi recebido íntegro, respeita o tamanho máximo e tem extensão permitida.
     * Retorna a extensão normalizada (usa o MIME real quando a extensão do cliente está ausente/errada,
     * comum em fotos tiradas pela câmera do celular).
     *
     * @param array<int, string> $allowedExtensions
     *
     * @throws ActionException
     */
    public static function validate(UploadedFile $file, array $allowedExtensions, int $maxBytes, string $invalidTypeMessage): string
    {
        if (! $file->isValid()) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                throw ActionException::tooLarge('O arquivo excede o tamanho máximo permitido pelo servidor.');
            }
            throw new ActionException('Arquivo inválido ou corrompido durante o envio.');
        }

        if ((int) $file->getSize() > $maxBytes) {
            $mb = (int) round($maxBytes / 1024 / 1024);
            throw ActionException::tooLarge("O arquivo deve ter no máximo {$mb} MB.");
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '' || ! in_array($ext, $allowedExtensions, true)) {
            $ext = self::MIME_TO_EXT[strtolower((string) $file->getMimeType())] ?? $ext;
        }

        if (! in_array($ext, $allowedExtensions, true)) {
            throw new ActionException($invalidTypeMessage);
        }

        return $ext;
    }

    public static function mimeFor(string $ext): string
    {
        return self::EXT_TO_MIME[strtolower($ext)] ?? 'application/octet-stream';
    }

    public static function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new ActionException('Não foi possível criar o diretório de uploads no servidor.', 500);
        }
    }

    /** Remove um arquivo relativo a storage/app, ignorando se não existir. */
    public static function deleteStored(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }

        $full = storage_path('app/' . $relativePath);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Resposta de arquivo armazenado em storage/app, exibido inline no navegador.
     */
    public static function inlineResponse(string $relativePath, string $downloadName, ?string $ext = null): BinaryFileResponse
    {
        $full = storage_path('app/' . $relativePath);
        if (! is_file($full)) {
            throw ActionException::notFound('Arquivo físico não encontrado.');
        }

        $ext       = strtolower($ext ?: pathinfo($full, PATHINFO_EXTENSION));
        $name      = $downloadName !== '' ? $downloadName : ('anexo.' . $ext);
        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: ('anexo.' . $ext);

        return response()->file($full, [
            'Content-Type'           => self::mimeFor($ext),
            'Content-Disposition'    => 'inline; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name),
            'Cache-Control'          => 'no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
