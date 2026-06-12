<?php

declare(strict_types=1);

namespace SalveAlimento\Tests\Security;

use PHPUnit\Framework\TestCase;
use SalveAlimento\Middleware\CsrfMiddleware;

class CsrfTest extends TestCase
{
    public function testTokenGeradoTemFormatoCorreto(): void
    {
        $token = CsrfMiddleware::gerarToken();

        $this->assertSame(64, strlen($token), 'Token deve ter 64 caracteres hex (256 bits)');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testTokenConsistenteNaMesmaSessao(): void
    {
        $token1 = CsrfMiddleware::gerarToken();
        $token2 = CsrfMiddleware::gerarToken();

        $this->assertSame($token1, $token2, 'Mesma sessão deve retornar o mesmo token');
    }

    public function testCampoOcultoContemToken(): void
    {
        $token = CsrfMiddleware::gerarToken();
        $campo = CsrfMiddleware::campoOculto();

        $this->assertStringContainsString('type="hidden"', $campo);
        $this->assertStringContainsString($token, $campo);
        $this->assertStringContainsString('_csrf_token', $campo);
    }

    public function testTokenValidoAceitoPeloMiddleware(): void
    {
        $token = CsrfMiddleware::gerarToken();

        $this->assertTrue(CsrfMiddleware::tokenValido($token, $token));
    }

    public function testTokenFalsoRejeitadoPeloMiddleware(): void
    {
        $tokenReal  = CsrfMiddleware::gerarToken();
        $tokenFalso = str_repeat('a', 64);

        $this->assertFalse(CsrfMiddleware::tokenValido($tokenReal, $tokenFalso));
    }

    public function testTokenVazioRejeitadoPeloMiddleware(): void
    {
        $token = CsrfMiddleware::gerarToken();

        $this->assertFalse(CsrfMiddleware::tokenValido($token, ''));
    }

    public function testTokenSessaoVazioRejeitado(): void
    {
        $this->assertFalse(CsrfMiddleware::tokenValido('', 'qualquer-coisa'));
    }
}
