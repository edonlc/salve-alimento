<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
        </div>

        <h1 class="auth-titulo">Redefinir senha</h1>

        <p class="texto-cinza mb-2">
            Insira o código recebido por e-mail e escolha uma nova senha.
        </p>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="/redefinir-senha">
            <div class="form-grupo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required
                       autocomplete="email" placeholder="seu@email.com">
            </div>
            <div class="form-grupo">
                <label for="codigo">Código de verificação</label>
                <input type="text" id="codigo" name="codigo" required
                       placeholder="Código recebido por e-mail">
            </div>
            <div class="form-grupo">
                <label for="nova_senha">Nova senha</label>
                <input type="password" id="nova_senha" name="nova_senha" required
                       autocomplete="new-password" placeholder="Mínimo 8 caracteres">
            </div>
            <div class="form-grupo">
                <label for="confirmar_senha">Confirmar nova senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required
                       autocomplete="new-password" placeholder="Repita a nova senha">
            </div>
            <button type="submit" class="btn btn-primario">Redefinir senha</button>
        </form>

        <div class="auth-rodape">
            <a href="/entrar">Voltar ao login</a>
        </div>
    </div>
</div>

</body>
</html>
