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
    <title>Cadastro — Salve Alimento</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span>🌱 Salve Alimento</span>
            <small>Crie sua conta gratuitamente</small>
        </div>

        <h1 class="auth-titulo">Criar conta</h1>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="/cadastrar">
            <div class="form-grupo">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" required autocomplete="name"
                       placeholder="Seu nome">
            </div>
            <div class="form-grupo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       placeholder="seu@email.com">
            </div>
            <div class="form-grupo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres, maiúsculas, números e símbolos">
            </div>
            <div class="form-grupo">
                <label for="perfil">Tipo de conta</label>
                <select id="perfil" name="perfil" required>
                    <option value="" disabled selected>Selecione...</option>
                    <option value="doador">Doador — tenho alimentos para doar</option>
                    <option value="receptor_ong">ONG / Instituição — recebo doações</option>
                    <option value="receptor_familia">Família em vulnerabilidade — preciso de alimentos</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primario">Criar conta</button>
        </form>

        <div class="auth-rodape">
            Já tem conta? <a href="/entrar">Entrar</a>
        </div>
    </div>
</div>

</body>
</html>
