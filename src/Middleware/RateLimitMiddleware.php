<?php

declare(strict_types=1);

namespace SalveAlimento\Middleware;

use SalveAlimento\Config\Database;
use SalveAlimento\Services\EmailService;

class RateLimitMiddleware
{
    private const MAX_TENTATIVAS   = 5;
    private const BLOQUEIO_MINUTOS = 15;

    /**
     * Verifica se a conta está bloqueada antes de processar o login.
     * Encerra com HTTP 429 se ainda dentro do período de bloqueio.
     */
    public static function verificarLogin(string $email): void
    {
        $pdo  = Database::conexao();
        $stmt = $pdo->prepare('SELECT bloqueado_ate FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario || $usuario['bloqueado_ate'] === null) {
            return;
        }

        $bloqueadoAte = new \DateTimeImmutable($usuario['bloqueado_ate']);
        $agora        = new \DateTimeImmutable();

        if ($agora < $bloqueadoAte) {
            $minutos = (int) ceil(($bloqueadoAte->getTimestamp() - $agora->getTimestamp()) / 60);
            http_response_code(429);
            echo json_encode([
                'erro'   => "Conta bloqueada temporariamente. Tente novamente em {$minutos} minuto(s).",
                'codigo' => 'RATE_429',
            ]);
            exit;
        }

        // Bloqueio expirado — limpa contadores
        $pdo->prepare(
            'UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE email = ?'
        )->execute([$email]);
    }

    /**
     * Incrementa o contador de falhas. Bloqueia a conta ao atingir o limite.
     */
    public static function registrarFalha(string $email): void
    {
        $pdo  = Database::conexao();
        $stmt = $pdo->prepare('SELECT id, nome, tentativas_login FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return;
        }

        $novas = $usuario['tentativas_login'] + 1;

        if ($novas >= self::MAX_TENTATIVAS) {
            $bloqueadoAte = (new \DateTimeImmutable())
                ->modify('+' . self::BLOQUEIO_MINUTOS . ' minutes')
                ->format('Y-m-d H:i:s');

            $pdo->prepare(
                'UPDATE usuarios SET tentativas_login = ?, bloqueado_ate = ? WHERE email = ?'
            )->execute([$novas, $bloqueadoAte, $email]);

            EmailService::enviarAlertaBloqueio($email, $usuario['nome']);
        } else {
            $pdo->prepare(
                'UPDATE usuarios SET tentativas_login = ? WHERE email = ?'
            )->execute([$novas, $email]);
        }
    }

    /**
     * Zera tentativas após login bem-sucedido.
     */
    public static function resetarTentativas(string $email): void
    {
        Database::conexao()->prepare(
            'UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE email = ?'
        )->execute([$email]);
    }
}
