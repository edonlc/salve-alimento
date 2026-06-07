<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ── Headers de segurança em toda resposta ──────────────────────────────────
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

match (true) {
    // Página inicial — landing page estática
    $uri === '/' || $uri === '/index.php'
        => include __DIR__ . '/landing.html',

    // Rotas de autenticação (implementadas na Fase 5)
    $uri === '/entrar'              => include __DIR__ . '/../src/Views/auth/login.php',
    $uri === '/cadastrar'           => include __DIR__ . '/../src/Views/auth/cadastro.php',
    $uri === '/recuperar-senha'     => include __DIR__ . '/../src/Views/auth/recuperar-senha.php',
    $uri === '/sair'                => include __DIR__ . '/../src/Views/auth/logout.php',

    // Rotas do doador (implementadas na Fase 6)
    $uri === '/painel'              => include __DIR__ . '/../src/Views/doador/painel.php',
    $uri === '/doacoes'             => include __DIR__ . '/../src/Views/doacoes/listagem.php',
    $uri === '/reservar'            => include __DIR__ . '/../src/Views/doacoes/reservar.php',

    // Painel admin (implementado na Fase 6)
    $uri === '/admin'               => include __DIR__ . '/../src/Views/admin/painel.php',

    // Perfil do usuário
    $uri === '/perfil'              => include __DIR__ . '/../src/Views/usuario/perfil.php',

    // API — chave pública RSA (Fase 8)
    $uri === '/api/chave-publica'   => servir_chave_publica(),

    // 404
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
