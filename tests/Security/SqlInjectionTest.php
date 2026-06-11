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
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec("
            CREATE TABLE usuarios (
                id INTEGER PRIMARY KEY,
                cognito_sub TEXT,
                nome TEXT,
                email TEXT,
                perfil TEXT,
                status TEXT,
                tentativas_login INTEGER DEFAULT 0,
                bloqueado_ate TEXT,
                cpf_enc BLOB,
                endereco_enc BLOB,
                chave_enc BLOB,
                dt_cadastro TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("INSERT INTO usuarios (id, email, nome, perfil, status) VALUES (1, 'admin@test.com', 'Admin', 'doador', 'ativo')");

        Database::definirConexao($pdo);
    }

    protected function tearDown(): void
    {
        Database::resetar();
    }

    public function testBuscarPorEmailComInjectionNaoRetornaUsuario(): void
    {
        // Payload clássico: tenta retornar todos os usuários ignorando o WHERE
        $resultado = Usuario::buscarPorEmail("' OR '1'='1");

        $this->assertNull($resultado, 'SQL injection deve retornar null com prepared statement');
    }

    public function testBuscarPorEmailComUnionInjectionNaoRetornaUsuario(): void
    {
        $resultado = Usuario::buscarPorEmail("x' UNION SELECT 1,'x','x','x','x','x',0,null,null,null,null,'x'--");

        $this->assertNull($resultado, 'UNION injection deve ser bloqueado');
    }

    public function testExisteEmailComInjectionNaoRetornaTrue(): void
    {
        // Sem injeção, email inexistente retorna false
        $resultado = Usuario::existeEmail("' OR '1'='1");

        $this->assertFalse($resultado, 'SQL injection em existeEmail deve retornar false');
    }

    public function testEmailLegitivoFuncionaNormalmente(): void
    {
        $resultado = Usuario::buscarPorEmail('admin@test.com');

        $this->assertNotNull($resultado);
        $this->assertSame('admin@test.com', $resultado['email']);
    }

    public function testEmailInexistenteRetornaNull(): void
    {
        $this->assertNull(Usuario::buscarPorEmail('naoexiste@test.com'));
    }
}
