<?php

declare(strict_types=1);

namespace SalveAlimento\Tests\Security;

use PHPUnit\Framework\TestCase;
use SalveAlimento\Config\Database;
use SalveAlimento\Models\Doacao;

class IdorTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec("
            CREATE TABLE doacoes (
                id INTEGER PRIMARY KEY,
                id_doador INTEGER,
                titulo TEXT,
                descricao TEXT,
                quantidade TEXT,
                unidade TEXT,
                validade TEXT,
                regiao TEXT,
                status TEXT DEFAULT 'disponivel',
                dt_publicacao TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        // Usuário 1 é dono da doação 1; usuário 2 é dono da doação 2
        $pdo->exec("INSERT INTO doacoes (id, id_doador, titulo, status) VALUES (1, 1, 'Arroz 5kg', 'disponivel')");
        $pdo->exec("INSERT INTO doacoes (id, id_doador, titulo, status) VALUES (2, 2, 'Feijão 2kg', 'disponivel')");

        Database::definirConexao($pdo);
    }

    protected function tearDown(): void
    {
        Database::resetar();
    }

    public function testDonoAcessaPropriaDoacao(): void
    {
        // Doacao::pertenceAoDoador() é o ownership check real do projeto
        $this->assertTrue(Doacao::pertenceAoDoador(id: 1, idDoador: 1));
    }

    public function testUsuarioNaoAcessaDoacaoDeOutro(): void
    {
        // Usuário 2 tenta acessar doação do usuário 1 — IDOR bloqueado
        $this->assertFalse(Doacao::pertenceAoDoador(id: 1, idDoador: 2));
    }

    public function testIdInexistenteRetornaFalse(): void
    {
        $this->assertFalse(Doacao::pertenceAoDoador(id: 999, idDoador: 1));
    }

    public function testIdNegativoRetornaFalse(): void
    {
        $this->assertFalse(Doacao::pertenceAoDoador(id: -1, idDoador: 1));
    }

    public function testExcluirDoacaoDeOutroUsuarioFalha(): void
    {
        // Doacao::excluir() internamente verifica ownership: WHERE id = ? AND id_doador = ?
        $resultado = Doacao::excluir(id: 1, idDoador: 2);

        $this->assertFalse($resultado, 'Exclusão de doação alheia deve retornar false');
    }
}
