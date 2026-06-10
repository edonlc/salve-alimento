<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
$erro    = $_SESSION['erro'] ?? null;
$enviado = isset($_GET['enviado']);
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
        </div>

        <h1 class="auth-titulo">Recuperar senha</h1>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($enviado): ?>
            <div class="alerta alerta-sucesso">
                Se este e-mail estiver cadastrado, você receberá um código de recuperação em breve.
                <br><a href="/redefinir-senha" style="color:var(--sucesso);font-weight:600">Inserir código →</a>
            </div>
        <?php else: ?>
            <p class="texto-cinza mb-2">
                Informe seu e-mail e enviaremos um código para redefinir sua senha.
            </p>
            <form method="POST" action="/recuperar-senha">
                <div class="form-grupo">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required
                           autocomplete="email" placeholder="seu@email.com">
                </div>
                <button type="submit" class="btn btn-primario">Enviar código</button>
            </form>
        <?php endif; ?>

        <div class="auth-rodape">
            <a href="/entrar">Voltar ao login</a>
        </div>
    </div>
</div>

</body>
</html>
