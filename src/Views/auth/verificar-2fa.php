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
    <title>Verificação 2FA — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
        </div>

        <h1 class="auth-titulo">Verificação em duas etapas</h1>

        <p class="texto-cinza mb-2">
            Abra seu aplicativo autenticador (Google Authenticator, Authy etc.)
            e insira o código de 6 dígitos.
        </p>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="/verificar-2fa">
            <div class="form-grupo">
                <label for="codigo">Código de verificação</label>
                <input type="text" id="codigo" name="codigo" required
                       pattern="\d{6}" maxlength="6" inputmode="numeric"
                       autocomplete="one-time-code"
                       placeholder="000000"
                       style="font-size:1.5rem;letter-spacing:4px;text-align:center">
            </div>
            <button type="submit" class="btn btn-primario">Verificar</button>
        </form>

        <div class="auth-rodape">
            <a href="/entrar">Voltar ao login</a>
        </div>
    </div>
</div>

</body>
</html>
