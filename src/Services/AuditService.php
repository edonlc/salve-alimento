<?php

declare(strict_types=1);

namespace SalveAlimento\Services;

use SalveAlimento\Config\Database;

class AuditService
{
    /**
     * Registra um evento no log de auditoria com hash encadeado (SHA-256).
     * app_user não tem permissão de UPDATE/DELETE nessa tabela — imutabilidade garantida pelo banco.
     */
    public static function registrar(
        string  $acao,
        string  $tabelaAfetada,
        ?int    $idRegistro     = null,
        ?array  $dadosAnteriores = null,
        ?int    $idUsuario      = null
    ): void {
        $pdo = Database::conexao();

        $stmt         = $pdo->query('SELECT hash_atual FROM logs_auditoria ORDER BY id DESC LIMIT 1');
        $hashAnterior = $stmt->fetchColumn() ?: null;

        $ip   = self::resolverIp();
        $agora = date('Y-m-d H:i:s');

        $hashAtual = hash('sha256', implode('|', [
            $idUsuario   ?? 'sistema',
            $acao,
            $tabelaAfetada,
            $idRegistro  ?? '',
            $agora,
            $ip,
            $hashAnterior ?? '',
        ]));

        $pdo->prepare(
            'INSERT INTO logs_auditoria
             (id_usuario, acao, tabela_afetada, id_registro, dados_anteriores,
              dt_evento, ip_origem, hash_anterior, hash_atual)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $idUsuario,
            $acao,
            $tabelaAfetada,
            $idRegistro,
            $dadosAnteriores !== null
                ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE)
                : null,
            $agora,
            $ip,
            $hashAnterior,
            $hashAtual,
        ]);
    }

    private static function resolverIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        return trim(explode(',', $ip)[0]);
    }
}
