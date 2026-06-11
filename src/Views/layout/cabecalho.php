<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}

$erro    = $_SESSION['erro']    ?? null;
$sucesso = $_SESSION['sucesso'] ?? null;
unset($_SESSION['erro'], $_SESSION['sucesso']);

$csrfCampo = \SalveAlimento\Middleware\CsrfMiddleware::campoOculto();

$perfil   = $_SESSION['usuario_perfil'] ?? '';
$uriAtual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Salve Alimento') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<header class="topbar">
    <a href="/" class="topbar-logo">🌱 Salve Alimento</a>
    <nav class="topbar-nav">
        <?php if ($perfil === 'doador'): ?>
            <a href="/painel" class="<?= $uriAtual === '/painel' ? 'ativo' : '' ?>">Meu Painel</a>
            <a href="/doacoes/criar">Nova Doação</a>
        <?php elseif ($perfil === 'receptor_ong' || $perfil === 'receptor_familia'): ?>
            <a href="/doacoes" class="<?= $uriAtual === '/doacoes' ? 'ativo' : '' ?>">Doações</a>
            <a href="/minhas-solicitacoes" class="<?= $uriAtual === '/minhas-solicitacoes' ? 'ativo' : '' ?>">Minhas Reservas</a>
        <?php elseif ($perfil === 'admin'): ?>
            <a href="/admin" class="<?= str_starts_with($uriAtual, '/admin') ? 'ativo' : '' ?>">Painel Admin</a>
        <?php endif; ?>
        <?php if ($perfil): ?>
            <a href="/perfil" class="<?= $uriAtual === '/perfil' ? 'ativo' : '' ?>">Meu Perfil</a>
        <?php endif; ?>
        <form method="POST" action="/sair" style="margin:0">
            <button type="submit" class="sair-btn">Sair</button>
        </form>
    </nav>
</header>

<main>
<div class="container">

<?php if ($erro): ?>
    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>
