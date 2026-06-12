<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Confia no proxy reverso nginx para HTTPS
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// ── Headers em toda resposta ───────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── Roteador ───────────────────────────────────────────────────────────────
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];

use SalveAlimento\Controllers\AuthController;
use SalveAlimento\Controllers\DoacaoController;
use SalveAlimento\Controllers\SolicitacaoController;
use SalveAlimento\Controllers\AdminController;
use SalveAlimento\Controllers\PerfilController;

match (true) {
    $uri === '/' || $uri === '/index.php'
        => include __DIR__ . '/landing.html',

    // Autenticação
    $uri === '/entrar'           => AuthController::login(),
    $uri === '/cadastrar'        => AuthController::registrar(),
    $uri === '/confirmar-email'  => AuthController::confirmarEmail(),
    $uri === '/verificar-2fa'    => AuthController::verificar2fa(),
    $uri === '/configurar-2fa'  => AuthController::configurar2fa(),
    $uri === '/sair'            => AuthController::logout(),
    $uri === '/recuperar-senha' => AuthController::recuperarSenha(),
    $uri === '/redefinir-senha' => AuthController::redefinirSenha(),

    // Doações — listagem e CRUD
    $uri === '/doacoes'           => DoacaoController::listar(),
    $uri === '/painel'            => DoacaoController::painel(),
    $uri === '/doacoes/criar'     => DoacaoController::criar(),
    $uri === '/doacoes/editar'    => DoacaoController::editar(),
    $uri === '/doacoes/excluir'   => DoacaoController::excluir(),

    // Solicitações / reservas
    $uri === '/doacoes/reservar'         => SolicitacaoController::reservar(),
    $uri === '/minhas-solicitacoes'      => SolicitacaoController::minhasSolicitacoes(),
    $uri === '/solicitacoes/aprovar'     => SolicitacaoController::aprovar(),
    $uri === '/solicitacoes/recusar'     => SolicitacaoController::recusar(),
    $uri === '/solicitacoes/concluir'    => SolicitacaoController::concluir(),

    // Admin
    $uri === '/admin'             => AdminController::painel(),
    $uri === '/admin/usuarios'    => AdminController::usuarios(),
    $uri === '/admin/ativar'      => AdminController::ativarUsuario(),
    $uri === '/admin/bloquear'    => AdminController::bloquearUsuario(),
    $uri === '/admin/doacoes'     => AdminController::doacoes(),
    $uri === '/admin/encerrar'    => AdminController::encerrarDoacao(),
    $uri === '/admin/logs'        => AdminController::logs(),
    $uri === '/admin/relatorio'   => AdminController::relatorio(),

    // Perfil — dados pessoais com criptografia híbrida
    $uri === '/perfil'               => PerfilController::exibir(),
    $uri === '/perfil/salvar'        => PerfilController::salvar(),
    $uri === '/perfil/trocar-senha'  => PerfilController::trocarSenha(),

    // API — chave pública RSA
    $uri === '/api/chave-publica' => servir_chave_publica(),

    default => pagina_nao_encontrada(),
};

// ── Helpers de rota ────────────────────────────────────────────────────────

function servir_chave_publica(): void
{
    $caminho = $_ENV['RSA_PUBLIC_KEY_PATH'] ?? __DIR__ . '/../keys/public.pem';
    if (!file_exists($caminho)) {
        http_response_code(503);
        echo json_encode(['erro' => 'Chave pública não configurada']);
        return;
    }
    header('Content-Type: application/json');
    echo json_encode(['chavePublica' => file_get_contents($caminho)]);
}

function pagina_nao_encontrada(): void
{
    http_response_code(404);
    $view = __DIR__ . '/../src/Views/erros/404.php';
    if (file_exists($view)) {
        include $view;
    } else {
        echo '<h1>404 — Página não encontrada</h1>';
    }
}
