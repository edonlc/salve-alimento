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

        <form method="POST" action="/cadastrar" id="form-cadastro">
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

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:1rem 0">
            <p style="font-size:.8rem;color:#666;margin-bottom:.75rem">
                CPF e endereço são cifrados <strong>no seu navegador</strong> antes de serem enviados — o servidor nunca os vê em texto puro.
            </p>

            <div class="form-grupo">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="_cpf_plain" required
                       placeholder="000.000.000-00" maxlength="14" inputmode="numeric"
                       autocomplete="off">
            </div>
            <div class="form-grupo">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="_endereco_plain" required
                       placeholder="Rua, número, cidade, estado"
                       autocomplete="off">
            </div>

            <!-- campos hidden preenchidos pelo JS com os blobs cifrados -->
            <input type="hidden" id="cpf_enc"      name="cpf_enc">
            <input type="hidden" id="endereco_enc" name="endereco_enc">
            <input type="hidden" id="chave_enc"    name="chave_enc">

            <div id="status-cadastro" style="display:none;margin-bottom:.75rem"></div>

            <button type="submit" class="btn btn-primario">Criar conta</button>
        </form>

        <div class="auth-rodape">
            Já tem conta? <a href="/entrar">Entrar</a>
        </div>
    </div>
</div>

<script src="/assets/js/cadastro.js"></script>
</body>
</html>
