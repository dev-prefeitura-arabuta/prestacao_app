<?php

namespace Tests\Unit\Support;

use App\Exceptions\ActionException;
use App\Support\UploadedFiles;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadedFilesTest extends TestCase
{
    private const ALLOWED = ['pdf', 'jpg', 'jpeg', 'png'];

    public function test_aceita_extensao_permitida(): void
    {
        $file = UploadedFile::fake()->create('nota.PDF', 10, 'application/pdf');

        $this->assertSame('pdf', UploadedFiles::validate($file, self::ALLOWED, 1024 * 1024, 'inválido'));
    }

    public function test_usa_mime_real_quando_extensao_do_cliente_esta_ausente(): void
    {
        // Fotos da câmera às vezes chegam sem extensão
        $fake = UploadedFile::fake()->image('foto.png', 10, 10);
        $file = new UploadedFile($fake->getPathname(), 'foto', 'image/png', null, true);

        $this->assertSame('png', UploadedFiles::validate($file, self::ALLOWED, 1024 * 1024, 'inválido'));
    }

    public function test_recusa_extensao_nao_permitida(): void
    {
        $file = UploadedFile::fake()->create('script.php', 1, 'text/x-php');

        try {
            UploadedFiles::validate($file, self::ALLOWED, 1024 * 1024, 'Formato inválido');
            $this->fail('Deveria recusar .php');
        } catch (ActionException $e) {
            $this->assertSame('Formato inválido', $e->getMessage());
            $this->assertSame(422, $e->status());
        }
    }

    public function test_recusa_arquivo_maior_que_o_limite(): void
    {
        $file = UploadedFile::fake()->create('grande.pdf', 2048, 'application/pdf'); // 2 MB

        try {
            UploadedFiles::validate($file, self::ALLOWED, 1024 * 1024, 'inválido');
            $this->fail('Deveria recusar arquivo grande');
        } catch (ActionException $e) {
            $this->assertSame(413, $e->status());
            $this->assertStringContainsString('1 MB', $e->getMessage());
        }
    }

    public function test_mime_for(): void
    {
        $this->assertSame('application/pdf', UploadedFiles::mimeFor('PDF'));
        $this->assertSame('image/webp', UploadedFiles::mimeFor('webp'));
        $this->assertSame('application/octet-stream', UploadedFiles::mimeFor('exe'));
    }

    public function test_inline_response_de_arquivo_inexistente_lanca_404(): void
    {
        $this->useTempStorage();

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage('Arquivo físico não encontrado.');

        UploadedFiles::inlineResponse('uploads/nao-existe.pdf', 'x.pdf');
    }

    public function test_inline_response_define_cabecalhos_seguros(): void
    {
        $root = $this->useTempStorage();
        file_put_contents($root . '/app/arquivo.pdf', '%PDF-1.4');

        $response = UploadedFiles::inlineResponse('arquivo.pdf', 'Relatório final.pdf');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('inline; filename="Relat__rio_final.pdf"', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString("filename*=UTF-8''Relat%C3%B3rio%20final.pdf", $response->headers->get('Content-Disposition'));
    }

    public function test_delete_stored_remove_arquivo_e_ignora_inexistente(): void
    {
        $root = $this->useTempStorage();
        file_put_contents($root . '/app/apagar.txt', 'x');

        UploadedFiles::deleteStored('apagar.txt');
        UploadedFiles::deleteStored('nao-existe.txt');
        UploadedFiles::deleteStored(null);

        $this->assertFileDoesNotExist($root . '/app/apagar.txt');
    }
}
