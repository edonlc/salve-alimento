<?php
$tituloPagina = 'Meu Perfil — Salve Alimento';
include __DIR__ . '/layout/cabecalho.php';
?>

<div style="max-width:640px;margin:0 auto">
    <h1 class="pagina-titulo">Meu Perfil</h1>

    <?php if (!empty($cpfDecifrado)): ?>
    <div class="card" style="margin-bottom:1.5rem;padding:1.25rem">
        <h2 style="font-size:1rem;color:#555;margin-bottom:.75rem">Dados registrados</h2>
        <p><strong>CPF:</strong> <?= htmlspecialchars($cpfDecifrado, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($enderecoDecifrado)): ?>
        <p style="margin-top:.5rem"><strong>Endereço:</strong> <?= htmlspecialchars($enderecoDecifrado, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p style="margin-top:.75rem;font-size:.8rem;color:#888">
            Armazenado com criptografia híbrida AES-256-GCM + RSA-OAEP — apenas o servidor com a chave privada pode decifrar.
        </p>
    </div>
    <?php endif; ?>

    <div class="card" style="padding:1.5rem">
        <h2 style="font-size:1rem;color:#555;margin-bottom:1rem">
            <?= empty($cpfDecifrado) ? 'Adicionar dados pessoais' : 'Atualizar dados' ?>
        </h2>

        <p style="font-size:.875rem;color:#666;margin-bottom:1.25rem">
            Seu CPF e endereço são cifrados <strong>no seu navegador</strong> antes de serem enviados.
            O servidor nunca recebe nem armazena esses dados em texto puro.
        </p>

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

            <button type="submit" class="btn btn-primario">Salvar com criptografia</button>
        </form>

        <div id="status-cripto" style="margin-top:1rem;display:none"></div>
    </div>
</div>

<script src="/assets/js/perfil.js"></script>

<?php include __DIR__ . '/layout/rodape.php'; ?>
