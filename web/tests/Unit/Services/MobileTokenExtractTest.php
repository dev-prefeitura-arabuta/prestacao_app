<?php

namespace Tests\Unit\Services;

use App\Services\MobileTokenService;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class MobileTokenExtractTest extends TestCase
{
    public function test_le_token_do_cabecalho_bearer(): void
    {
        $request = Request::create('/api/mobile/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer abc123']);

        $this->assertSame('abc123', MobileTokenService::extractToken($request));
    }

    public function test_ignora_token_na_query_por_padrao(): void
    {
        $request = Request::create('/api/mobile/me?token=abc123', 'GET');

        $this->assertSame('', MobileTokenService::extractToken($request));
    }

    public function test_aceita_token_na_query_quando_permitido(): void
    {
        $request = Request::create('/api/mobile/attachments/1/preview?token=abc123', 'GET');

        $this->assertSame('abc123', MobileTokenService::extractToken($request, true));
    }

    public function test_cabecalho_tem_prioridade_sobre_query(): void
    {
        $request = Request::create('/x?token=query', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer header']);

        $this->assertSame('header', MobileTokenService::extractToken($request, true));
    }
}
