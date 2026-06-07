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

    private static function extrairToken(): ?string
    {
        $cabecalho = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (str_starts_with($cabecalho, 'Bearer ')) {
            return substr($cabecalho, 7);
        }

        return null;
    }
}
