<?php

declare(strict_types=1);

namespace SalveAlimento\Controllers;

use SalveAlimento\Middleware\AuthMiddleware;
use SalveAlimento\Middleware\RbacMiddleware;
use SalveAlimento\Middleware\CsrfMiddleware;
use SalveAlimento\Models\Doacao;
use SalveAlimento\Models\LogAuditoria;
use SalveAlimento\Models\Solicitacao;
use SalveAlimento\Models\Usuario;
use SalveAlimento\Services\AuditService;

class AdminController
{
    // ── PAINEL PRINCIPAL ──────────────────────────────────────────────────────

    public static function painel(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);

        $totalUsuarios   = count(Usuario::listarTodos());
        $totalDoacoes    = count(Doacao::listarTodas());
        $totalLogs       = LogAuditoria::total();
        $integridadeLog  = LogAuditoria::verificarIntegridade();
        $ultimosDoacoes  = Doacao::listarTodas();
        $ultimosLogs     = LogAuditoria::listar(10);

        include __DIR__ . '/../Views/admin/painel.php';
    }

    // ── GESTÃO DE USUÁRIOS ────────────────────────────────────────────────────

    public static function usuarios(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);

        $usuarios = Usuario::listarTodos();

        include __DIR__ . '/../Views/admin/usuarios.php';
    }

    public static function ativarUsuario(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);
        CsrfMiddleware::verificar();

        $idAlvo   = (int) ($_POST['id'] ?? 0);
        $adminSub = $payload['sub'] ?? '';
        $admin    = Usuario::buscarPorSub($adminSub);

        if (!$idAlvo || !$admin) {
            self::erro('Usuário não encontrado.', '/admin/usuarios');
            return;
        }

        $antes = Usuario::buscarPorId($idAlvo);
        Usuario::atualizarStatus($idAlvo, 'ativo');
        AuditService::registrar('USUARIO_ATIVADO', 'usuarios', $idAlvo, $antes, $admin['id']);

        self::redirecionar('/admin/usuarios?ativado=ok');
    }

    public static function bloquearUsuario(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);
        CsrfMiddleware::verificar();

        $idAlvo = (int) ($_POST['id'] ?? 0);
        $admin  = Usuario::buscarPorSub($payload['sub'] ?? '');

        if (!$idAlvo || !$admin) {
            self::erro('Usuário não encontrado.', '/admin/usuarios');
            return;
        }

        // Admin não pode bloquear a si mesmo
        if ($idAlvo === $admin['id']) {
            self::erro('Não é possível bloquear sua própria conta.', '/admin/usuarios');
            return;
        }

        $antes = Usuario::buscarPorId($idAlvo);
        Usuario::atualizarStatus($idAlvo, 'bloqueado');
        AuditService::registrar('USUARIO_BLOQUEADO', 'usuarios', $idAlvo, $antes, $admin['id']);

        self::redirecionar('/admin/usuarios?bloqueado=ok');
    }

    // ── MODERAÇÃO DE DOAÇÕES ──────────────────────────────────────────────────

    public static function doacoes(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);

        $doacoes = Doacao::listarTodas();

        include __DIR__ . '/../Views/admin/doacoes.php';
    }

    public static function encerrarDoacao(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);
        CsrfMiddleware::verificar();

        $idDoacao = (int) ($_POST['id'] ?? 0);
        $admin    = Usuario::buscarPorSub($payload['sub'] ?? '');

        if (!$idDoacao || !$admin) {
            self::erro('Doação não encontrada.', '/admin/doacoes');
            return;
        }

        $antes = Doacao::buscarPorId($idDoacao);
        Doacao::atualizarStatus($idDoacao, 'expirado');
        AuditService::registrar('DOACAO_ENCERRADA_ADMIN', 'doacoes', $idDoacao, $antes, $admin['id']);

        self::redirecionar('/admin/doacoes?encerrada=ok');
    }

    // ── LOGS DE AUDITORIA ─────────────────────────────────────────────────────

    public static function logs(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);

        $pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
        $limite  = 50;
        $offset  = ($pagina - 1) * $limite;

        $logs        = LogAuditoria::listar($limite, $offset);
        $total       = LogAuditoria::total();
        $totalPaginas = (int) ceil($total / $limite);
        $integro     = LogAuditoria::verificarIntegridade();

        include __DIR__ . '/../Views/admin/logs.php';
    }

    // ── RELATÓRIO ─────────────────────────────────────────────────────────────

    public static function relatorio(): void
    {
        $payload = AuthMiddleware::verificarSessao();
        RbacMiddleware::exigirAdmin($payload);

        $usuarios    = Usuario::listarTodos();
        $doacoes     = Doacao::listarTodas();
        $solicitacoes = Solicitacao::listarTodas();

        $stats = [
            'total_usuarios'        => count($usuarios),
            'usuarios_ativos'       => count(array_filter($usuarios, fn($u) => $u['status'] === 'ativo')),
            'usuarios_pendentes'    => count(array_filter($usuarios, fn($u) => $u['status'] === 'pendente')),
            'total_doacoes'         => count($doacoes),
            'doacoes_disponiveis'   => count(array_filter($doacoes, fn($d) => $d['status'] === 'disponivel')),
            'doacoes_concluidas'    => count(array_filter($doacoes, fn($d) => $d['status'] === 'concluido')),
            'total_solicitacoes'    => count($solicitacoes),
            'solicitacoes_aprovadas' => count(array_filter($solicitacoes, fn($s) => $s['status'] === 'aprovada')),
            'total_logs'            => LogAuditoria::total(),
        ];

        include __DIR__ . '/../Views/admin/relatorio.php';
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
