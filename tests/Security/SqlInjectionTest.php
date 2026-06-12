<?php

declare(strict_types=1);

namespace SalveAlimento\Tests\Security;

use PHPUnit\Framework\TestCase;
use SalveAlimento\Config\Database;
use SalveAlimento\Models\Usuario;

class SqlInjectionTest extends TestCase
{
    protected function setUp(): void
    {
        Database::conexao()->beginTransaction();

        Database::conexao()->prepare("
            INSERT INTO usuarios (cognito_sub, nome, email, perfil, status)
            VALUES (?, ?, ?, ?, ?)
        ")->execute(['sub-test-sql', 'Teste SQL', 'sql@test.com', 'doador', 'ativo']);
    }

    protected function tearDown(): void
    {
        Database::conexao()->rollBack();
    }

    public function testBuscarPorEmailComInjectionNaoRetornaUsuario(): void
    {
        $resultado = Usuario::buscarPorEmail("' OR '1'='1");

        $this->assertNull($resultado, 'SQL injection clássico deve retornar null com prepared statement');
    }

    public function testBuscarPorEmailComUnionInjectionNaoRetornaUsuario(): void
    {
        $resultado = Usuario::buscarPorEmail("x' UNION SELECT 1,'x','x','x','x','x',null,null,null,0,null,null--");

        $this->assertNull($resultado, 'UNION injection deve ser bloqueado');
    }

    public function testExisteEmailComInjectionNaoRetornaTrue(): void
    {
        $resultado = Usuario::existeEmail("' OR '1'='1");

        $this->assertFalse($resultado, 'SQL injection em existeEmail deve retornar false');
    }

    public function testEmailLegitivoFuncionaNormalmente(): void
    {
        $resultado = Usuario::buscarPorEmail('sql@test.com');

        $this->assertNotNull($resultado);
        $this->assertSame('sql@test.com', $resultado['email']);
    }

    public function testEmailInexistenteRetornaNull(): void
    {
        $this->assertNull(Usuario::buscarPorEmail('naoexiste@test.com'));
    }
}
