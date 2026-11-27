<?php

namespace Tests\Unit\Services;

use App\Services\MobileTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MobileTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_emite_e_resolve_token(): void
    {
        $service = new MobileTokenService();
        $user    = $this->makeUser();

        $token = $service->issue($user);

        $this->assertSame(64, strlen($token));
        $this->assertSame((int) $user->id, (int) $service->resolve($token)?->id);
    }

    public function test_armazena_apenas_o_hash_do_token(): void
    {
        $service = new MobileTokenService();
        $token   = $service->issue($this->makeUser());

        $this->assertFalse(Cache::has('mobile_token_' . $token));
        $this->assertTrue(Cache::has('mobile_token_' . hash('sha256', $token)));
    }

    public function test_token_revogado_ou_desconhecido_nao_resolve(): void
    {
        $service = new MobileTokenService();
        $token   = $service->issue($this->makeUser());

        $service->revoke($token);

        $this->assertNull($service->resolve($token));
        $this->assertNull($service->resolve('token-inventado'));
        $this->assertNull($service->resolve(''));
    }

    public function test_token_expira_em_7_dias(): void
    {
        $service = new MobileTokenService();
        $token   = $service->issue($this->makeUser());

        $this->travel(6)->days();
        $this->assertNotNull($service->resolve($token));

        $this->travel(2)->days();
        $this->assertNull($service->resolve($token));
    }
}
