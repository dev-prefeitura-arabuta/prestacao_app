<?php

namespace Tests\Unit\Support;

use App\Support\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pode_atuar_em_todos_os_itens(): void
    {
        $actor = $this->actorFor($this->makeUser('admin'));

        $this->assertTrue($actor->isAdmin);
        $this->assertSame(range(1, 24), $actor->allowedItems());
        $this->assertTrue($actor->canAccessItem(17));
    }

    public function test_usuario_so_acessa_itens_liberados(): void
    {
        $actor = $this->actorFor($this->makeUser('user', [3, 1, 3]));

        $this->assertFalse($actor->isAdmin);
        $this->assertSame([1, 3], $actor->allowedItems());
        $this->assertTrue($actor->canAccessItem(3));
        $this->assertFalse($actor->canAccessItem(2));
    }

    public function test_usuario_sem_permissoes_nao_acessa_nada(): void
    {
        $actor = $this->actorFor($this->makeUser('user'));

        $this->assertSame([], $actor->allowedItems());
        $this->assertFalse($actor->canAccessItem(1));
    }

    public function test_from_session_le_dados_da_sessao(): void
    {
        session(['user_id' => 7, 'user_name' => 'Maria', 'user_role' => 'admin']);
        $actor = Actor::fromSession();

        $this->assertSame(7, $actor->id);
        $this->assertSame('Maria', $actor->name);
        $this->assertTrue($actor->isAdmin);
    }

    public function test_from_session_sem_papel_e_usuario_comum(): void
    {
        session(['user_id' => 8, 'user_name' => 'João']);

        $this->assertFalse(Actor::fromSession()->isAdmin);
    }
}
