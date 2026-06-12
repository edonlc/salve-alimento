<?php

declare(strict_types=1);

namespace SalveAlimento\Middleware;

use SalveAlimento\Services\JwtService;

class AuthMiddleware
{
    /**
     * Valida o Bearer token JWT em toda rota protegida.
     * Retorna o payload do token ou encerra com HTTP 401.
     */
    public static function verificar(): array
    {
        $token = self::extrairToken();

        if ($token === null) {
            http_response_code(401);
            echo json_encode(['erro' => 'Token de autenticação ausente', 'codigo' => 'AUTH_401']);
            exit;
        }

        try {
            return JwtService::validar($token);
        } catch (\RuntimeException) {
            http_response_code(401);
            echo json_encode(['erro' => 'Token inválido ou expirado', 'codigo' => 'AUTH_401']);
            exit;
        }
    }

    /**
     * Retorna o payload do usuário logado via cookie (rotas web).
     * Redireciona para /entrar se não autenticado.
     */
    private const TIMEOUT_INATIVIDADE = 1800; // 30 minutos em segundos

    public static function verificarSessao(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
        }

        // Encerra sessão por inatividade
        if (self::sessaoExpirada()) {
            session_destroy();
            self::limparCookiesAuth();
            header('Location: /entrar?sessao=expirada');
            exit;
        }
        $_SESSION['ultima_atividade'] = time();

        // IdToken contém custom:perfil — necessário para RBAC
        $token = $_COOKIE['id_token'] ?? $_COOKIE['access_token'] ?? null;

        if ($token === null) {
            header('Location: /entrar');
            exit;
        }

        try {
            return JwtService::validar($token);
        } catch (\RuntimeException) {
            header('Location: /entrar');
            exit;
        }
    }

    public static function sessaoExpirada(): bool
    {
        if (!isset($_SESSION['ultima_atividade'])) {
            return false;
        }
        return time() - $_SESSION['ultima_atividade'] > self::TIMEOUT_INATIVIDADE;
    }

    public static function limparCookiesAuth(): void
    {
        foreach (['access_token', 'id_token', 'refresh_token'] as $cookie) {
            setcookie($cookie, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
        }
    }

    private static function extrairToken(): ?string
    {
        $cabecalho = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (str_starts_with($cabecalho, 'Bearer ')) {
            return substr($cabecalho, 7);
        }

        // Fallback para cookie (requisições web)
        return $_COOKIE['access_token'] ?? null;
    }
}
