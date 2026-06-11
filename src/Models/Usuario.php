<?php

declare(strict_types=1);

namespace SalveAlimento\Models;

use SalveAlimento\Config\Database;

class Usuario
{
    public static function buscarPorEmail(string $email): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT * FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function buscarPorSub(string $sub): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT * FROM usuarios WHERE cognito_sub = ? LIMIT 1'
        );
        $stmt->execute([$sub]);
        return $stmt->fetch() ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT * FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): int
    {
        $pdo  = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios
             (cognito_sub, nome, email, perfil, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['cognito_sub'],
            $dados['nome'],
            $dados['email'],
            $dados['perfil'],
            $dados['status'] ?? 'pendente',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        Database::conexao()->prepare(
            'UPDATE usuarios SET status = ? WHERE id = ?'
        )->execute([$status, $id]);
    }

    public static function ativarPorEmail(string $email): void
    {
        Database::conexao()->prepare(
            "UPDATE usuarios SET status = 'ativo' WHERE email = ? AND status = 'pendente'"
        )->execute([$email]);
    }

    /**
     * Salva dados cifrados (CPF e endereço) e a chave AES protegida por RSA.
     */
    public static function salvarDadosCifrados(int $id, string $cpfEnc, string $enderecoEnc, string $chaveEnc): void
    {
        Database::conexao()->prepare(
            'UPDATE usuarios SET cpf_enc = ?, endereco_enc = ?, chave_enc = ? WHERE id = ?'
        )->execute([$cpfEnc, $enderecoEnc, $chaveEnc, $id]);
    }

    public static function atualizarNome(int $id, string $nome): void
    {
        Database::conexao()->prepare(
            'UPDATE usuarios SET nome = ? WHERE id = ?'
        )->execute([$nome, $id]);
    }

    public static function listarTodos(): array
    {
        return Database::conexao()
            ->query('SELECT id, nome, email, perfil, status, dt_criacao FROM usuarios ORDER BY dt_criacao DESC')
            ->fetchAll();
    }

    public static function existeEmail(string $email): bool
    {
        $stmt = Database::conexao()->prepare(
            'SELECT 1 FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }
}
