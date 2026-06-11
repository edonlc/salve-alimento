<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
// $segredo e $uriTotp são passados pelo AuthController::configurar2fa()
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar 2FA — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card" style="max-width:480px">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
        </div>

        <h1 class="auth-titulo">Configure a autenticação em duas etapas</h1>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="alerta alerta-info" style="font-size:.85rem">
            Escaneie o QR code com o <strong>Google Authenticator</strong> ou <strong>Authy</strong>.<br>
            Não consegue escanear? Expanda "chave manual" abaixo.
        </div>

        <div id="qrcode"
             data-uri="<?= htmlspecialchars($uriTotp ?? '', ENT_QUOTES, 'UTF-8') ?>"
             style="display:flex;justify-content:center;margin:1.25rem 0"></div>

        <details style="margin-bottom:1rem">
            <summary style="cursor:pointer;font-size:.85rem;color:var(--cinza);margin-bottom:.5rem">
                Inserir chave manualmente
            </summary>
            <p class="texto-cinza" style="font-size:.8rem;margin-bottom:.25rem">Chave secreta (Base32):</p>
            <div class="totp-secret" style="font-size:.9rem"><?= htmlspecialchars(chunk_split($segredo ?? '', 4, ' ')) ?></div>
            <p class="texto-pequeno texto-cinza">Nome da conta: <strong>SalveAlimento</strong></p>
        </details>

        <form method="POST" action="/configurar-2fa">
            <div class="form-grupo">
                <label for="codigo">Código de verificação (6 dígitos)</label>
                <input type="text" id="codigo" name="codigo" required
                       pattern="\d{6}" maxlength="6" inputmode="numeric"
                       autocomplete="one-time-code"
                       placeholder="000000"
                       style="font-size:1.4rem;letter-spacing:4px;text-align:center"
                       autofocus>
            </div>
            <button type="submit" class="btn btn-primario">Confirmar e entrar</button>
        </form>
    </div>
</div>

<script src="/assets/js/qrcode.min.js"></script>
<script src="/assets/js/configurar-2fa.js"></script>

</body>
</html>
