<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersAdminTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Maria Teste',
            'email'         => 'Maria@Teste.local',
            'whatsapp'      => '(11) 98765-4321',
            'password'      => 'segredo123',
            'role'          => 'user',
            'allowed_items' => ['1', '5'],
        ], $overrides);
    }

    public function test_admin_lista_e_abre_formularios(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('user', [1], ['name' => 'Fulano Listado']);
        $this->loginAs($admin);

        $this->get('/users')->assertOk()->assertSee('Fulano Listado');
        $this->get('/users/create')->assertOk();
        $this->get('/users/' . $other->id . '/edit')->assertOk();
        $this->get('/users/999/edit')->assertRedirect('/users');
    }

    public function test_admin_cadastra_usuario_com_permissoes(): void
    {
        $this->loginAs($this->makeUser('admin'));

        $this->post('/users', $this->validPayload())->assertRedirect('/users')->assertSessionHas('success');

        $user = User::where('email', 'maria@teste.local')->firstOrFail();
        $this->assertSame('Maria Teste', $user->name);
        $this->assertNotEmpty($user->whatsapp);
        $this->assertTrue(password_verify('segredo123', (string) $user->password_hashed));
        $this->assertEqualsCanonicalizing([1, 5], UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id));
    }

    public function test_cadastro_recusa_dados_invalidos(): void
    {
        $existing = $this->makeUser();
        $this->loginAs($this->makeUser('admin'));
        $before = User::count();

        $cases = [
            'nome curto'         => ['name' => 'ab'],
            'email duplicado'    => ['email' => $existing->email],
            'email invalido'     => ['email' => 'nao-e-email'],
            'whatsapp invalido'  => ['whatsapp' => '1234'],
            'senha curta'        => ['password' => '123'],
            'perfil inexistente' => ['role' => 'root'],
        ];

        foreach ($cases as $label => $override) {
            $this->from('/users/create')
                ->post('/users', $this->validPayload($override))
                ->assertRedirect('/users/create')
                ->assertSessionHas('errors_list');
            $this->assertSame($before, User::count(), "Caso '{$label}' criou usuário");
        }
    }

    public function test_admin_atualiza_usuario_sem_trocar_senha(): void
    {
        $user = $this->makeUser('user', [1]);
        $this->loginAs($this->makeUser('admin'));

        $this->post('/users/' . $user->id . '/update', [
            'name'          => 'Nome Novo',
            'email'         => $user->email,
            'whatsapp'      => '',
            'role'          => 'user',
            'password'      => '',
            'allowed_items' => ['2', '3'],
        ])->assertRedirect('/users')->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nome Novo', $user->name);
        $this->assertTrue(password_verify('segredo123', (string) $user->password_hashed));
        $this->assertEqualsCanonicalizing([2, 3], UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id));
    }

    public function test_atualizar_os_proprios_dados_atualiza_a_sessao(): void
    {
        $admin = $this->makeUser('admin');
        $this->loginAs($admin);

        $this->post('/users/' . $admin->id . '/update', [
            'name'  => 'Admin Renomeado',
            'email' => $admin->email,
            'role'  => 'admin',
        ])->assertSessionHas('user_name', 'Admin Renomeado');
    }

    public function test_admin_altera_perfil_e_exclui_usuario(): void
    {
        $user = $this->makeUser('user', [1]);
        $this->loginAs($this->makeUser('admin'));

        $this->post('/users/' . $user->id . '/role', ['role' => 'admin'])->assertRedirect('/users');
        $this->assertSame('admin', $user->fresh()->role);

        $this->post('/users/' . $user->id . '/role', ['role' => 'superuser'])->assertSessionHas('error');
        $this->assertSame('admin', $user->fresh()->role);

        $this->post('/users/' . $user->id . '/delete')->assertRedirect('/users')->assertSessionHas('success');
        $this->assertNull(User::find($user->id));
        $this->assertSame([], UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id));
    }

    public function test_admin_nao_exclui_a_propria_conta(): void
    {
        $admin = $this->makeUser('admin');
        $this->loginAs($admin);

        $this->post('/users/' . $admin->id . '/delete')->assertRedirect('/users')->assertSessionHas('error');
        $this->assertNotNull(User::find($admin->id));
    }
}
