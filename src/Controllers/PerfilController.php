<?php

declare(strict_types=1);

namespace SalveAlimento\Controllers;

use SalveAlimento\Middleware\AuthMiddleware;
use SalveAlimento\Models\Usuario;
use SalveAlimento\Services\CryptoService;

class PerfilController
{
    public static function exibir(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');

        $cpfDecifrado      = null;
        $enderecoDecifrado = null;

        if ($usuario && !empty($usuario['chave_enc']) && !empty($usuario['cpf_enc'])) {
            try {
                $chaveAes          = CryptoService::decifrarChaveAes(base64_encode($usuario['chave_enc']));
                $cpfDecifrado      = CryptoService::decifrarAes(base64_encode($usuario['cpf_enc']), $chaveAes);
                if (!empty($usuario['endereco_enc'])) {
                    $enderecoDecifrado = CryptoService::decifrarAes(base64_encode($usuario['endereco_enc']), $chaveAes);
                }
            } catch (\RuntimeException) {
                // chave ou dados inválidos — exibe vazio
            }
        }

        include __DIR__ . '/../Views/perfil.php';
    }

    public static function salvar(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');

        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['erro' => 'Usuário não encontrado']);
            return;
        }

        $corpo = json_decode(file_get_contents('php://input'), true);

        $cpfEnc      = $corpo['cpf']      ?? '';
        $enderecoEnc = $corpo['endereco'] ?? '';
        $chaveEnc    = $corpo['chave']    ?? '';

        if (!$cpfEnc || !$chaveEnc) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados incompletos']);
            return;
        }

        try {
            // Valida que consegue decifrar antes de salvar
            $chaveAes = CryptoService::decifrarChaveAes($chaveEnc);
            CryptoService::decifrarAes($cpfEnc, $chaveAes);

            // Armazena os blobs cifrados (sem nunca expor o dado em plain no banco)
            Usuario::salvarDadosCifrados(
                $usuario['id'],
                base64_decode($cpfEnc),
                $enderecoEnc ? base64_decode($enderecoEnc) : '',
                base64_decode($chaveEnc)
            );

            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
        } catch (\RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['erro' => 'Falha ao processar criptografia: ' . $e->getMessage()]);
        }
    }
}
