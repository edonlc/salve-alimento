<?php

declare(strict_types=1);

namespace SalveAlimento\Models;

use SalveAlimento\Config\Database;

class Doacao
{
    public static function buscarPorId(int $id): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT d.*, u.nome AS nome_doador
             FROM doacoes d
             JOIN usuarios u ON u.id = d.id_doador
             WHERE d.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function listarDisponiveis(array $filtros = []): array
    {
        $sql    = 'SELECT d.*, u.nome AS nome_doador
                   FROM doacoes d
                   JOIN usuarios u ON u.id = d.id_doador
                   WHERE d.status = \'disponivel\'
                     AND d.dt_limite_retirada > NOW()';
        $params = [];

        if (!empty($filtros['regiao'])) {
            $sql     .= ' AND d.endereco_retirada LIKE ?';
            $params[] = '%' . $filtros['regiao'] . '%';
        }

        $sql .= ' ORDER BY d.dt_publicacao DESC';

        if (!empty($filtros['limite'])) {
            $sql     .= ' LIMIT ?';
            $params[] = (int) $filtros['limite'];
        }

        $stmt = Database::conexao()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function listarPorDoador(int $idDoador): array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT * FROM doacoes WHERE id_doador = ? ORDER BY dt_publicacao DESC'
        );
        $stmt->execute([$idDoador]);
        return $stmt->fetchAll();
    }

    public static function criar(array $dados, int $idDoador): int
    {
        $pdo  = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO doacoes
             (id_doador, titulo, descricao, dt_limite_retirada, endereco_retirada)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $idDoador,
            $dados['titulo'],
            $dados['descricao'] ?? null,
            $dados['dt_limite_retirada'],
            $dados['endereco_retirada'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Atualiza doação verificando ownership (A01 — IDOR).
     * Só permite edição enquanto status = 'disponivel'.
     */
    public static function atualizar(int $id, int $idDoador, array $dados): bool
    {
        $stmt = Database::conexao()->prepare(
            'UPDATE doacoes
             SET titulo = ?, descricao = ?, dt_limite_retirada = ?, endereco_retirada = ?
             WHERE id = ? AND id_doador = ? AND status = \'disponivel\''
        );
        $stmt->execute([
            $dados['titulo'],
            $dados['descricao'] ?? null,
            $dados['dt_limite_retirada'],
            $dados['endereco_retirada'],
            $id,
            $idDoador,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui doação verificando ownership e status (A01 — IDOR).
     */
    public static function excluir(int $id, int $idDoador): bool
    {
        $stmt = Database::conexao()->prepare(
            'DELETE FROM doacoes WHERE id = ? AND id_doador = ? AND status = \'disponivel\''
        );
        $stmt->execute([$id, $idDoador]);
        return $stmt->rowCount() > 0;
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        Database::conexao()->prepare(
            'UPDATE doacoes SET status = ? WHERE id = ?'
        )->execute([$status, $id]);
    }

    public static function listarTodas(): array
    {
        return Database::conexao()
            ->query(
                'SELECT d.*, u.nome AS nome_doador
                 FROM doacoes d
                 JOIN usuarios u ON u.id = d.id_doador
                 ORDER BY d.dt_publicacao DESC'
            )->fetchAll();
    }

    /**
     * Verifica se o usuário é dono da doação (A01 — IDOR).
     */
    public static function pertenceAoDoador(int $id, int $idDoador): bool
    {
        $stmt = Database::conexao()->prepare(
            'SELECT 1 FROM doacoes WHERE id = ? AND id_doador = ? LIMIT 1'
        );
        $stmt->execute([$id, $idDoador]);
        return (bool) $stmt->fetchColumn();
    }
}
