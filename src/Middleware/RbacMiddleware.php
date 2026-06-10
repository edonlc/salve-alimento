<?php

declare(strict_types=1);

namespace SalveAlimento\Middleware;

class RbacMiddleware
{
    /**
     * Verifica se o perfil do usuário autenticado está entre os permitidos.
     * Encerra com HTTP 403 caso contrário.
     */
    public static function exigirPerfil(array $payload, string ...$perfisPermitidos): void
    {
        $perfilUsuario = $payload['custom:perfil'] ?? '';

        if (!in_array($perfilUsuario, $perfisPermitidos, true)) {
            http_response_code(403);
            echo json_encode(['erro' => 'Acesso negado para este perfil', 'codigo' => 'RBAC_403']);
            exit;
        }
    }

    public static function exigirAdmin(array $payload): void
    {
        self::exigirPerfil($payload, 'admin');
    }

    public static function exigirDoador(array $payload): void
    {
        self::exigirPerfil($payload, 'doador', 'admin');
    }

    public static function exigirReceptor(array $payload): void
    {
        self::exigirPerfil($payload, 'receptor_ong', 'receptor_familia', 'admin');
    }
}
