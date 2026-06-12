<?php

declare(strict_types=1);

namespace SalveAlimento\Tests\Security;

use PHPUnit\Framework\TestCase;
use SalveAlimento\Middleware\AuthMiddleware;

class SessionTimeoutTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Garante sessão limpa a cada teste
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testSessaoSemTimestampNaoExpirada(): void
    {
        // Primeiro acesso — sem ultima_atividade na sessão
        unset($_SESSION['ultima_atividade']);

        $this->assertFalse(AuthMiddleware::sessaoExpirada());
    }

    public function testSessaoRecenteNaoExpirada(): void
    {
        $_SESSION['ultima_atividade'] = time() - 60; // 1 minuto atrás

        $this->assertFalse(AuthMiddleware::sessaoExpirada());
    }

    public function testSessaoNoLimiteNaoExpirada(): void
    {
        $_SESSION['ultima_atividade'] = time() - 1799; // 1 segundo antes do limite

        $this->assertFalse(AuthMiddleware::sessaoExpirada());
    }

    public function testSessaoAlemDoLimiteExpirada(): void
    {
        $_SESSION['ultima_atividade'] = time() - 1801; // 1 segundo além dos 30 min

        $this->assertTrue(AuthMiddleware::sessaoExpirada());
    }

    public function testSessaoMuitoAntigaExpirada(): void
    {
        $_SESSION['ultima_atividade'] = time() - 7200; // 2 horas atrás

        $this->assertTrue(AuthMiddleware::sessaoExpirada());
    }
}
