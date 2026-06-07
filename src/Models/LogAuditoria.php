<?php

declare(strict_types=1);

namespace SalveAlimento\Models;

use SalveAlimento\Config\Database;

class LogAuditoria
{
    public static function listar(int $limite = 100, int $offset = 0): array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT l.*, u.nome AS nome_usuario
             FROM logs_auditoria l
             LEFT JOIN usuarios u ON u.id = l.id_usuario
             ORDER BY l.dt_evento DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limite, $offset]);
        return $stmt->fetchAll();
    }

    public static function listarPorUsuario(int $idUsuario, int $limite = 50): array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT * FROM logs_auditoria
             WHERE id_usuario = ?
             ORDER BY dt_evento DESC
             LIMIT ?'
        );
        $stmt->execute([$idUsuario, $limite]);
        return $stmt->fetchAll();
    }

    public static function listarPorTabela(string $tabela): array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT * FROM logs_auditoria
             WHERE tabela_afetada = ?
             ORDER BY dt_evento DESC'
        );
        $stmt->execute([$tabela]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica a integridade da cadeia de hashes SHA-256.
     * Retorna true se todos os registros estão íntegros.
     */
    public static function verificarIntegridade(): bool
    {
        $registros = Database::conexao()
            ->query('SELECT * FROM logs_auditoria ORDER BY id ASC')
            ->fetchAll();

        $hashAnterior = null;

        foreach ($registros as $registro) {
            $conteudoEsperado = implode('|', [
                $registro['id_usuario']   ?? 'sistema',
                $registro['acao'],
                $registro['tabela_afetada'],
                $registro['id_registro']  ?? '',
                $registro['dt_evento'],
                $registro['ip_origem'],
                $hashAnterior             ?? '',
            ]);

            $hashEsperado = hash('sha256', $conteudoEsperado);

            if ($hashEsperado !== $registro['hash_atual']) {
                return false;
            }

            if ($registro['hash_anterior'] !== $hashAnterior) {
                return false;
            }

            $hashAnterior = $registro['hash_atual'];
        }

        return true;
    }

    public static function total(): int
    {
        return (int) Database::conexao()
            ->query('SELECT COUNT(*) FROM logs_auditoria')
            ->fetchColumn();
    }
}
