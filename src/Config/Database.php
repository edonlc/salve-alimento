<?php

declare(strict_types=1);

namespace SalveAlimento\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instancia = null;

    private function __construct() {}
    private function __clone() {}

    public static function definirConexao(PDO $pdo): void
    {
        self::$instancia = $pdo;
    }

    public static function resetar(): void
    {
        self::$instancia = null;
    }

    public static function conexao(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'db',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'salve_alimento'
        );

        try {
            self::$instancia = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            error_log('Erro de conexão com o banco: ' . $e->getMessage());
            http_response_code(503);
            echo json_encode(['erro' => 'Serviço temporariamente indisponível', 'codigo' => 'DB_503']);
            exit;
        }

        return self::$instancia;
    }
}
