<?php

declare(strict_types=1);

namespace SalveAlimento\Controllers;

use SalveAlimento\Middleware\AuthMiddleware;
use SalveAlimento\Middleware\CsrfMiddleware;
use SalveAlimento\Models\Usuario;
use SalveAlimento\Services\CognitoService;
use SalveAlimento\Services\CryptoService;
use SalveAlimento\Services\AuditService;

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

    public static function trocarSenha(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        CsrfMiddleware::verificar();

        $senhaAtual  = $_POST['senha_atual']      ?? '';
        $novaSenha   = $_POST['nova_senha']        ?? '';
        $confirmar   = $_POST['confirmar_senha']   ?? '';

        if (!$senhaAtual || !$novaSenha || $novaSenha !== $confirmar) {
            $_SESSION['erro_senha'] = 'Preencha todos os campos e confirme a nova senha corretamente.';
            header('Location: /perfil');
            exit;
        }

        if (strlen($novaSenha) < 8) {
            $_SESSION['erro_senha'] = 'A nova senha deve ter pelo menos 8 caracteres.';
            header('Location: /perfil');
            exit;
        }

        $accessToken = $_COOKIE['access_token'] ?? '';

        if (!$accessToken) {
            $_SESSION['erro_senha'] = 'Sessão inválida. Faça login novamente.';
            header('Location: /entrar');
            exit;
        }

        try {
            CognitoService::trocarSenha($accessToken, $senhaAtual, $novaSenha);

            $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');
            AuditService::registrar('SENHA_ALTERADA', 'usuarios', $usuario['id'] ?? null, null, $usuario['id'] ?? null);

            $_SESSION['sucesso_senha'] = 'Senha alterada com sucesso.';
        } catch (\RuntimeException) {
            $_SESSION['erro_senha'] = 'Senha atual incorreta ou nova senha não atende aos requisitos.';
        }

        header('Location: /perfil');
        exit;
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
