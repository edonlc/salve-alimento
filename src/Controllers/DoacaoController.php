<?php

declare(strict_types=1);

namespace SalveAlimento\Controllers;

use SalveAlimento\Middleware\AuthMiddleware;
use SalveAlimento\Middleware\RbacMiddleware;
use SalveAlimento\Middleware\CsrfMiddleware;
use SalveAlimento\Models\Doacao;
use SalveAlimento\Models\Usuario;
use SalveAlimento\Services\AuditService;

class DoacaoController
{
    // ── LISTAGEM PÚBLICA ──────────────────────────────────────────────────────

    public static function listar(): void
    {
        $payload = AuthMiddleware::verificarSessao();

        $filtros = [
            'regiao' => trim($_GET['regiao'] ?? ''),
            'limite' => 20,
        ];

        $doacoes = Doacao::listarDisponiveis($filtros);

        include __DIR__ . '/../Views/doacoes/listagem.php';
    }

    // ── PAINEL DO DOADOR ──────────────────────────────────────────────────────

    public static function painel(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);

        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');
        $doacoes = $usuario ? Doacao::listarPorDoador($usuario['id']) : [];

        include __DIR__ . '/../Views/doador/painel.php';
    }

    // ── CRIAR DOAÇÃO ──────────────────────────────────────────────────────────

    public static function criar(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $csrfCampo = CsrfMiddleware::campoOculto();
            include __DIR__ . '/../Views/doador/criar-doacao.php';
            return;
        }

        CsrfMiddleware::verificar();

        $titulo            = trim($_POST['titulo']             ?? '');
        $descricao         = trim($_POST['descricao']          ?? '');
        $dtLimiteRetirada  = trim($_POST['dt_limite_retirada'] ?? '');
        $enderecoRetirada  = trim($_POST['endereco_retirada']  ?? '');

        if (!$titulo || !$dtLimiteRetirada || !$enderecoRetirada) {
            self::erro('Preencha todos os campos obrigatórios.', '/doacoes/criar');
            return;
        }

        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');
        if (!$usuario) {
            self::erro('Usuário não encontrado.', '/painel');
            return;
        }

        $idDoacao = Doacao::criar([
            'titulo'            => $titulo,
            'descricao'         => $descricao,
            'dt_limite_retirada' => $dtLimiteRetirada,
            'endereco_retirada' => $enderecoRetirada,
        ], $usuario['id']);

        AuditService::registrar('DOACAO_CRIADA', 'doacoes', $idDoacao, null, $usuario['id']);

        self::redirecionar('/painel?criada=ok');
    }

    // ── EDITAR DOAÇÃO ─────────────────────────────────────────────────────────

    public static function editar(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);

        $idDoacao = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $usuario  = Usuario::buscarPorEmail($payload['email'] ?? '');

        if (!$usuario || !$idDoacao) {
            self::erro('Doação não encontrada.', '/painel');
            return;
        }

        // Verificação de ownership — A01 IDOR
        if (!Doacao::pertenceAoDoador($idDoacao, $usuario['id'])) {
            http_response_code(403);
            self::erro('Acesso negado.', '/painel');
            return;
        }

        $doacao = Doacao::buscarPorId($idDoacao);

        if (!$doacao || $doacao['status'] !== 'disponivel') {
            self::erro('Esta doação não pode ser editada.', '/painel');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $csrfCampo = CsrfMiddleware::campoOculto();
            include __DIR__ . '/../Views/doador/editar-doacao.php';
            return;
        }

        CsrfMiddleware::verificar();

        $dados = [
            'titulo'             => trim($_POST['titulo']             ?? ''),
            'descricao'          => trim($_POST['descricao']          ?? ''),
            'dt_limite_retirada' => trim($_POST['dt_limite_retirada'] ?? ''),
            'endereco_retirada'  => trim($_POST['endereco_retirada']  ?? ''),
        ];

        if (!$dados['titulo'] || !$dados['dt_limite_retirada'] || !$dados['endereco_retirada']) {
            self::erro('Preencha todos os campos obrigatórios.', '/doacoes/editar?id=' . $idDoacao);
            return;
        }

        $atualizado = Doacao::atualizar($idDoacao, $usuario['id'], $dados);

        if ($atualizado) {
            AuditService::registrar('DOACAO_EDITADA', 'doacoes', $idDoacao, $doacao, $usuario['id']);
        }

        self::redirecionar('/painel?editada=ok');
    }

    // ── EXCLUIR DOAÇÃO ────────────────────────────────────────────────────────

    public static function excluir(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);
        CsrfMiddleware::verificar();

        $idDoacao = (int) ($_POST['id'] ?? 0);
        $usuario  = Usuario::buscarPorEmail($payload['email'] ?? '');

        if (!$usuario || !$idDoacao) {
            self::erro('Doação não encontrada.', '/painel');
            return;
        }

        $doacao = Doacao::buscarPorId($idDoacao);

        // Verificação de ownership — A01 IDOR
        $excluido = Doacao::excluir($idDoacao, $usuario['id']);

        if (!$excluido) {
            self::erro('Não foi possível excluir. Verifique se a doação está disponível.', '/painel');
            return;
        }

        AuditService::registrar('DOACAO_EXCLUIDA', 'doacoes', $idDoacao, $doacao, $usuario['id']);
        self::redirecionar('/painel?excluida=ok');
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private static function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private static function erro(string $mensagem, string $destino): void
    {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
        $_SESSION['erro'] = $mensagem;
        self::redirecionar($destino);
    }
}
