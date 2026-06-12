<?php

declare(strict_types=1);

namespace SalveAlimento\Tests\Security;

use PHPUnit\Framework\TestCase;
use SalveAlimento\Config\Database;
use SalveAlimento\Models\Doacao;

class IdorTest extends TestCase
{
    private int $idDoador1;
    private int $idDoador2;
    private int $idDoacao1;
    private int $idDoacao2;

    protected function setUp(): void
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO usuarios (cognito_sub, nome, email, perfil, status)
            VALUES (?, ?, ?, ?, ?)
        ")->execute(['sub-idor-1', 'Doador Um', 'doador1@test.com', 'doador', 'ativo']);
        $this->idDoador1 = (int) $pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO usuarios (cognito_sub, nome, email, perfil, status)
            VALUES (?, ?, ?, ?, ?)
        ")->execute(['sub-idor-2', 'Doador Dois', 'doador2@test.com', 'doador', 'ativo']);
        $this->idDoador2 = (int) $pdo->lastInsertId();

        $amanha = date('Y-m-d H:i:s', strtotime('+1 day'));

        $pdo->prepare("
            INSERT INTO doacoes (id_doador, titulo, endereco_retirada, dt_limite_retirada)
            VALUES (?, ?, ?, ?)
        ")->execute([$this->idDoador1, 'Arroz 5kg', 'Rua A, 1', $amanha]);
        $this->idDoacao1 = (int) $pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO doacoes (id_doador, titulo, endereco_retirada, dt_limite_retirada)
            VALUES (?, ?, ?, ?)
        ")->execute([$this->idDoador2, 'Feijão 2kg', 'Rua B, 2', $amanha]);
        $this->idDoacao2 = (int) $pdo->lastInsertId();
    }

    protected function tearDown(): void
    {
        Database::conexao()->rollBack();
    }

    public function testDonoAcessaPropriaDoacao(): void
    {
        $this->assertTrue(Doacao::pertenceAoDoador($this->idDoacao1, $this->idDoador1));
    }

    public function testUsuarioNaoAcessaDoacaoDeOutro(): void
    {
        // Doador 2 tenta acessar doação do doador 1 — IDOR bloqueado
        $this->assertFalse(Doacao::pertenceAoDoador($this->idDoacao1, $this->idDoador2));
    }

    public function testIdInexistenteRetornaFalse(): void
    {
        $this->assertFalse(Doacao::pertenceAoDoador(999999, $this->idDoador1));
    }

    public function testIdNegativoRetornaFalse(): void
    {
        $this->assertFalse(Doacao::pertenceAoDoador(-1, $this->idDoador1));
    }

    public function testExcluirDoacaoDeOutroUsuarioFalha(): void
    {
        $resultado = Doacao::excluir($this->idDoacao1, $this->idDoador2);

        $this->assertFalse($resultado, 'Exclusão de doação alheia deve retornar false');
    }
}
