<?php

declare(strict_types=1);

namespace SalveAlimento\Controllers;

use SalveAlimento\Middleware\AuthMiddleware;
use SalveAlimento\Middleware\RbacMiddleware;
use SalveAlimento\Middleware\CsrfMiddleware;
use SalveAlimento\Models\Doacao;
use SalveAlimento\Models\Solicitacao;
use SalveAlimento\Models\Usuario;
use SalveAlimento\Services\AuditService;

class SolicitacaoController
{
    // ── SOLICITAR RESERVA (receptor) ──────────────────────────────────────────

    public static function reservar(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirReceptor($payload);

        $idDoacao = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $doacao   = $idDoacao ? Doacao::buscarPorId($idDoacao) : null;

        if (!$doacao || $doacao['status'] !== 'disponivel') {
            self::erro('Doação não disponível.', '/doacoes');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $csrfCampo = CsrfMiddleware::campoOculto();
            include __DIR__ . '/../Views/doacoes/reservar.php';
            return;
        }

        CsrfMiddleware::verificar();

        $obs     = trim($_POST['obs'] ?? '');
        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');

        if (!$usuario) {
            self::erro('Usuário não encontrado.', '/doacoes');
            return;
        }

        try {
            $idSolicitacao = Solicitacao::criar($idDoacao, $usuario['id'], $obs ?: null);

            // Atualiza status da doação para reservado
            Doacao::atualizarStatus($idDoacao, 'reservado');

            AuditService::registrar('RESERVA_CRIADA', 'solicitacoes', $idSolicitacao, null, $usuario['id']);

            self::redirecionar('/minhas-solicitacoes?reservada=ok');
        } catch (\RuntimeException $e) {
            self::erro($e->getMessage(), '/doacoes');
        }
    }

    // ── MINHAS SOLICITAÇÕES (receptor) ────────────────────────────────────────

    public static function minhasSolicitacoes(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirReceptor($payload);

        $usuario       = Usuario::buscarPorEmail($payload['email'] ?? '');
        $solicitacoes  = $usuario ? Solicitacao::listarPorReceptor($usuario['id']) : [];

        include __DIR__ . '/../Views/doacoes/minhas-solicitacoes.php';
    }

    // ── APROVAR RESERVA (doador) ──────────────────────────────────────────────

    public static function aprovar(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);
        CsrfMiddleware::verificar();

        $idSolicitacao = (int) ($_POST['id'] ?? 0);
        $solicitacao   = $idSolicitacao ? Solicitacao::buscarPorId($idSolicitacao) : null;

        if (!$solicitacao) {
            self::erro('Solicitação não encontrada.', '/painel');
            return;
        }

        // Verifica se o doador é dono da doação — A01 IDOR
        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');
        if (!$usuario || !Doacao::pertenceAoDoador($solicitacao['id_doacao'], $usuario['id'])) {
            http_response_code(403);
            self::erro('Acesso negado.', '/painel');
            return;
        }

        Solicitacao::atualizarStatus($idSolicitacao, 'aprovada');
        AuditService::registrar('RESERVA_APROVADA', 'solicitacoes', $idSolicitacao, $solicitacao, $usuario['id']);

        self::redirecionar('/painel?aprovada=ok');
    }

    // ── RECUSAR RESERVA (doador) ──────────────────────────────────────────────

    public static function recusar(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);
        CsrfMiddleware::verificar();

        $idSolicitacao = (int) ($_POST['id'] ?? 0);
        $solicitacao   = $idSolicitacao ? Solicitacao::buscarPorId($idSolicitacao) : null;

        if (!$solicitacao) {
            self::erro('Solicitação não encontrada.', '/painel');
            return;
        }

        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');
        if (!$usuario || !Doacao::pertenceAoDoador($solicitacao['id_doacao'], $usuario['id'])) {
            http_response_code(403);
            self::erro('Acesso negado.', '/painel');
            return;
        }

        Solicitacao::atualizarStatus($idSolicitacao, 'recusada');

        // Libera a doação novamente
        Doacao::atualizarStatus($solicitacao['id_doacao'], 'disponivel');

        AuditService::registrar('RESERVA_RECUSADA', 'solicitacoes', $idSolicitacao, $solicitacao, $usuario['id']);

        self::redirecionar('/painel?recusada=ok');
    }

    // ── CONCLUIR DOAÇÃO (doador confirma retirada) ────────────────────────────

    public static function concluir(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirDoador($payload);
        CsrfMiddleware::verificar();

        $idSolicitacao = (int) ($_POST['id'] ?? 0);
        $solicitacao   = $idSolicitacao ? Solicitacao::buscarPorId($idSolicitacao) : null;

        if (!$solicitacao || $solicitacao['status'] !== 'aprovada') {
            self::erro('Solicitação inválida para conclusão.', '/painel');
            return;
        }

        $usuario = Usuario::buscarPorEmail($payload['email'] ?? '');
        if (!$usuario || !Doacao::pertenceAoDoador($solicitacao['id_doacao'], $usuario['id'])) {
            http_response_code(403);
            self::erro('Acesso negado.', '/painel');
            return;
        }

        Solicitacao::atualizarStatus($idSolicitacao, 'concluida');
        Doacao::atualizarStatus($solicitacao['id_doacao'], 'concluido');

        AuditService::registrar('DOACAO_CONCLUIDA', 'solicitacoes', $idSolicitacao, $solicitacao, $usuario['id']);

        self::redirecionar('/painel?concluida=ok');
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
