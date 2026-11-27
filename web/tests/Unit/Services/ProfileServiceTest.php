<?php

namespace Tests\Unit\Services;

use App\Exceptions\ActionException;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProfileService();
    }

    public function test_atualiza_dados_normalizando_email_e_whatsapp_vazio(): void
    {
        $user = $this->makeUser('user', [], ['whatsapp' => '11999999999']);

        $updated = $this->service->update($user, [
            'name'     => '  Ana Souza ',
            'email'    => 'ANA@Teste.Local', // espaços já são removidos pelo middleware TrimStrings
            'whatsapp' => '   ',
        ]);

        $this->assertSame('Ana Souza', $updated->name);
        $this->assertSame('ana@teste.local', $updated->email);
        $this->assertNull($updated->whatsapp);
        $this->assertTrue(password_verify('segredo123', $updated->password_hashed), 'Senha não deve mudar sem new_password');
    }

    public function test_whatsapp_e_salvo_no_formato_padrao(): void
    {
        $user = $this->makeUser();

        $updated = $this->service->update($user, ['name' => $user->name, 'email' => $user->email, 'whatsapp' => '+55 49 99811-0169']);

        $this->assertSame('(49) 99811-0169', $updated->whatsapp);
    }

    public function test_whatsapp_invalido_e_recusado(): void
    {
        $user = $this->makeUser();

        try {
            $this->service->update($user, ['name' => $user->name, 'email' => $user->email, 'whatsapp' => '49 8829-6525']);
            $this->fail('Esperava erro de validação');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('telefone válido', $e->errors()['whatsapp'][0]);
        }
    }

    public function test_troca_a_senha_quando_informada_e_confirmada(): void
    {
        $user = $this->makeUser();

        $updated = $this->service->update($user, [
            'name' => $user->name, 'email' => $user->email,
            'new_password' => 'novaSenha1', 'confirm_password' => 'novaSenha1',
        ]);

        $this->assertTrue(password_verify('novaSenha1', $updated->password_hashed));
    }

    public function test_confirmacao_de_senha_diferente_e_recusada(): void
    {
        $user = $this->makeUser();

        $this->expectException(ValidationException::class);
        $this->service->update($user, [
            'name' => $user->name, 'email' => $user->email,
            'new_password' => 'novaSenha1', 'confirm_password' => 'outra',
        ]);
    }

    public function test_email_de_outro_usuario_e_recusado(): void
    {
        $user  = $this->makeUser();
        $outro = $this->makeUser();

        try {
            $this->service->update($user, ['name' => $user->name, 'email' => $outro->email]);
            $this->fail('Esperava erro de validação');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_pode_manter_o_proprio_email(): void
    {
        $user = $this->makeUser();

        $this->assertSame($user->email, $this->service->update($user, ['name' => 'Nome Novo', 'email' => $user->email])->email);
    }

    public function test_avatar_e_gravado_e_o_anterior_removido(): void
    {
        $root = $this->useTempStorage();
        $user = $this->makeUser();
        $data = ['name' => $user->name, 'email' => $user->email];

        $first  = $this->service->update($user, $data, UploadedFile::fake()->image('a.png', 20, 20))->avatar;
        $second = $this->service->update($user, $data, UploadedFile::fake()->image('b.jpg', 20, 20))->avatar;

        $this->assertStringStartsWith('uploads/avatars/user_' . $user->id . '_', $second);
        $this->assertFileExists($root . '/app/' . $second);
        $this->assertFileDoesNotExist($root . '/app/' . $first);
    }

    public function test_avatar_com_formato_invalido_e_recusado(): void
    {
        $this->useTempStorage();
        $user = $this->makeUser();

        $this->expectException(ActionException::class);
        $this->service->update($user, ['name' => $user->name, 'email' => $user->email], UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'));
    }

    public function test_remove_avatar(): void
    {
        $root = $this->useTempStorage();
        $user = $this->makeUser();
        $path = $this->service->update($user, ['name' => $user->name, 'email' => $user->email], UploadedFile::fake()->image('a.png'))->avatar;

        $this->assertNull($this->service->removeAvatar($user)->avatar);
        $this->assertFileDoesNotExist($root . '/app/' . $path);
    }

    public function test_avatar_response_sem_foto_retorna_404(): void
    {
        $user = $this->makeUser();

        try {
            $this->service->avatarResponse((int) $user->id);
            $this->fail('Esperava 404');
        } catch (ActionException $e) {
            $this->assertSame(404, $e->status());
        }
    }
}
