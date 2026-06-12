<?php
$tituloPagina = 'Meu Perfil — Salve Alimento';
include __DIR__ . '/layout/cabecalho.php';
?>

<div style="max-width:640px;margin:0 auto">
    <h1 class="pagina-titulo">Meu Perfil</h1>

    <div class="card" style="margin-bottom:1.5rem;padding:1.5rem">
        <h2 style="font-size:1rem;color:#555;margin-bottom:1rem">Informações da conta</h2>
        <div style="display:flex;flex-direction:column;gap:.6rem">
            <p><strong>Nome:</strong> <?= htmlspecialchars($usuario['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>E-mail:</strong> <?= htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Perfil:</strong> <?= htmlspecialchars(ucfirst($usuario['perfil'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <div class="card" style="padding:1.5rem">
        <h2 style="font-size:1rem;color:#555;margin-bottom:1rem">Dados pessoais</h2>

        <?php if (!empty($cpfDecifrado)): ?>
        <div style="margin-bottom:1.25rem;padding:.75rem 1rem;background:#f5f5f5;border-radius:6px;display:flex;flex-direction:column;gap:.4rem">
            <p><strong>CPF:</strong> <?= htmlspecialchars($cpfDecifrado, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($enderecoDecifrado)): ?>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($enderecoDecifrado, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form id="form-perfil">
            <div class="form-grupo">
                <label for="cpf">CPF <span style="color:#c00">*</span></label>
                <input type="text" id="cpf" name="cpf" required
                       placeholder="000.000.000-00"
                       maxlength="14"
                       inputmode="numeric"
                       autocomplete="off">
            </div>

            <div class="form-grupo">
                <label for="endereco">Endereço <span style="color:#999;font-weight:400">(opcional)</span></label>
                <input type="text" id="endereco" name="endereco"
                       placeholder="Rua, número, cidade, estado"
                       autocomplete="off">
            </div>

            <button type="submit" class="btn btn-primario">
                <?= empty($cpfDecifrado) ? 'Salvar dados' : 'Atualizar dados' ?>
            </button>
        </form>

        <div id="status-cripto" style="margin-top:1rem;display:none"></div>
    </div>

    <div class="card" style="padding:1.5rem;margin-top:1.5rem">
        <h2 style="font-size:1rem;color:#555;margin-bottom:1rem">Alterar senha</h2>

        <?php
        $erroSenha    = $_SESSION['erro_senha']    ?? null;
        $sucessoSenha = $_SESSION['sucesso_senha'] ?? null;
        unset($_SESSION['erro_senha'], $_SESSION['sucesso_senha']);
        ?>

        <?php if ($erroSenha): ?>
            <div class="alerta alerta-erro" style="margin-bottom:1rem"><?= htmlspecialchars($erroSenha, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($sucessoSenha): ?>
            <div class="alerta alerta-sucesso" style="margin-bottom:1rem"><?= htmlspecialchars($sucessoSenha, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="/perfil/trocar-senha">
            <?= $csrfCampo ?>
            <div class="form-grupo">
                <label for="senha_atual">Senha atual</label>
                <input type="password" id="senha_atual" name="senha_atual" required autocomplete="current-password">
            </div>
            <div class="form-grupo">
                <label for="nova_senha">Nova senha</label>
                <input type="password" id="nova_senha" name="nova_senha" required
                       autocomplete="new-password" minlength="8">
            </div>
            <div class="form-grupo">
                <label for="confirmar_senha">Confirmar nova senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required
                       autocomplete="new-password" minlength="8">
            </div>
            <button type="submit" class="btn btn-secundario">Alterar senha</button>
        </form>
    </div>
</div>

<script src="/assets/js/perfil.js"></script>

<?php include __DIR__ . '/layout/rodape.php'; ?>
