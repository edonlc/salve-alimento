<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
$erro    = $_SESSION['erro'] ?? null;
$email   = htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8');
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar e-mail — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
        </div>

        <h1 class="auth-titulo">Confirme seu e-mail</h1>

        <div class="alerta alerta-info" style="font-size:.875rem">
            Enviamos um código de 6 dígitos para <strong><?= $email ?></strong>.<br>
            Verifique sua caixa de entrada e cole o código abaixo.
        </div>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="/confirmar-email">
            <input type="hidden" name="email" value="<?= $email ?>">

            <div class="form-grupo">
                <label for="codigo">Código de confirmação</label>
                <input type="text" id="codigo" name="codigo" required
                       pattern="\d{6}" maxlength="6" inputmode="numeric"
                       autocomplete="one-time-code"
                       placeholder="000000"
                       style="font-size:1.4rem;letter-spacing:4px;text-align:center"
                       autofocus>
            </div>

            <button type="submit" class="btn btn-primario">Confirmar conta</button>
        </form>

        <p class="texto-cinza texto-pequeno" style="text-align:center;margin-top:1rem">
            Não recebeu o código?
            <a href="/cadastrar">Tente cadastrar novamente</a>
        </p>
    </div>
</div>

</body>
</html>
