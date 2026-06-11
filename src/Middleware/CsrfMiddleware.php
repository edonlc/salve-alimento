<?php

declare(strict_types=1);

namespace SalveAlimento\Middleware;

class CsrfMiddleware
{
    private const CAMPO_FORM  = '_csrf_token';
    private const CHAVE_SESSAO = 'csrf_token';

    public static function gerarToken(): string
    {
        self::iniciarSessao();

        if (empty($_SESSION[self::CHAVE_SESSAO])) {
            $_SESSION[self::CHAVE_SESSAO] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CHAVE_SESSAO];
    }

    public static function campoOculto(): string
    {
        $token = self::gerarToken();

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::CAMPO_FORM,
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Valida o token CSRF em requisições de mutação.
     * Encerra com HTTP 403 se inválido.
     */
    public static function verificar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        self::iniciarSessao();

        $tokenSessao  = $_SESSION[self::CHAVE_SESSAO] ?? '';
        $tokenEnviado = $_POST[self::CAMPO_FORM]
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (!self::tokenValido($tokenSessao, $tokenEnviado)) {
            http_response_code(403);
            echo json_encode(['erro' => 'Token CSRF inválido ou ausente', 'codigo' => 'CSRF_403']);
            exit;
        }

        // Rotaciona o token a cada uso para prevenir replay
        $_SESSION[self::CHAVE_SESSAO] = bin2hex(random_bytes(32));
    }

    public static function tokenValido(string $tokenSessao, string $tokenEnviado): bool
    {
        return $tokenSessao !== '' && hash_equals($tokenSessao, $tokenEnviado);
    }

    private static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure'   => true,
                'cookie_httponly'  => true,
                'cookie_samesite' => 'Strict',
            ]);
        }
    }
}
