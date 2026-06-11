<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
$erro    = $_SESSION['erro'] ?? null;
$sucesso = null;
if (isset($_GET['cadastro']))        $sucesso = 'Cadastro realizado! Faça login para continuar.';
if (isset($_GET['senha']))           $sucesso = 'Senha redefinida com sucesso! Faça login.';
if (isset($_GET['sessao']))          $erro    = 'Sua sessão expirou por inatividade. Faça login novamente.';
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
            <small>Conectando quem tem com quem precisa</small>
        </div>

        <h1 class="auth-titulo">Entrar na conta</h1>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" action="/entrar">
            <div class="form-grupo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       placeholder="seu@email.com">
            </div>
            <div class="form-grupo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password"
                       placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primario">Entrar</button>
        </form>

        <div class="auth-rodape">
            <a href="/recuperar-senha">Esqueci minha senha</a>
            &nbsp;·&nbsp;
            <a href="/cadastrar">Criar conta</a>
        </div>
    </div>
</div>

</body>
</html>
