<?php

declare(strict_types=1);

namespace SalveAlimento\Models;

use SalveAlimento\Config\Database;

class Solicitacao
{
    public static function buscarPorId(int $id): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT s.*, u.nome AS nome_receptor, d.titulo AS titulo_doacao
             FROM solicitacoes s
             JOIN usuarios u ON u.id = s.id_receptor
             JOIN doacoes  d ON d.id = s.id_doacao
             WHERE s.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retorna a reserva ativa (pendente ou aprovada) de uma doação.
     * Garante a regra: apenas uma reserva ativa por doação (A08 — Insecure Design).
     */
    public static function buscarAtivaParaDoacao(int $idDoacao): ?array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT * FROM solicitacoes
             WHERE id_doacao = ? AND status IN ('pendente','aprovada')
             LIMIT 1"
        );
        $stmt->execute([$idDoacao]);
        return $stmt->fetch() ?: null;
    }

    public static function listarPorReceptor(int $idReceptor): array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT s.*, d.titulo AS titulo_doacao, d.endereco_retirada, d.status AS status_doacao
             FROM solicitacoes s
             JOIN doacoes d ON d.id = s.id_doacao
             WHERE s.id_receptor = ?
             ORDER BY s.dt_solicitacao DESC'
        );
        $stmt->execute([$idReceptor]);
        return $stmt->fetchAll();
    }

    public static function listarPorDoacao(int $idDoacao): array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT s.*, u.nome AS nome_receptor, u.perfil AS perfil_receptor
             FROM solicitacoes s
             JOIN usuarios u ON u.id = s.id_receptor
             WHERE s.id_doacao = ?
             ORDER BY s.dt_solicitacao ASC'
        );
        $stmt->execute([$idDoacao]);
        return $stmt->fetchAll();
    }

    /**
     * Cria solicitação verificando que não existe outra ativa para a mesma doação.
     * Retorna o ID criado ou lança exceção se já houver reserva ativa.
     */
    public static function criar(int $idDoacao, int $idReceptor, ?string $obs = null): int
    {
        if (self::buscarAtivaParaDoacao($idDoacao) !== null) {
            throw new \RuntimeException('Esta doação já possui uma reserva ativa.');
        }

        $pdo  = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO solicitacoes (id_doacao, id_receptor, obs) VALUES (?, ?, ?)'
        );
        $stmt->execute([$idDoacao, $idReceptor, $obs]);
        return (int) $pdo->lastInsertId();
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        Database::conexao()->prepare(
            'UPDATE solicitacoes SET status = ? WHERE id = ?'
        )->execute([$status, $id]);
    }

    /**
     * Verifica ownership do receptor antes de qualquer ação (A01 — IDOR).
     */
    public static function pertenceAoReceptor(int $id, int $idReceptor): bool
    {
        $stmt = Database::conexao()->prepare(
            'SELECT 1 FROM solicitacoes WHERE id = ? AND id_receptor = ? LIMIT 1'
        );
        $stmt->execute([$id, $idReceptor]);
        return (bool) $stmt->fetchColumn();
    }

    public static function listarTodas(): array
    {
        return Database::conexao()
            ->query(
                'SELECT s.*, u.nome AS nome_receptor, d.titulo AS titulo_doacao
                 FROM solicitacoes s
                 JOIN usuarios u ON u.id = s.id_receptor
                 JOIN doacoes  d ON d.id = s.id_doacao
                 ORDER BY s.dt_solicitacao DESC'
            )->fetchAll();
    }
}
