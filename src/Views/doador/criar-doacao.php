<?php
$tituloPagina = 'Nova doação — Salve Alimento';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Nova doação</h1>
    <a href="/painel" class="btn btn-secundario">← Voltar</a>
</div>

<div class="card mt-2" style="max-width:600px">
    <form method="POST" action="/doacoes/criar">
        <?= $csrfCampo ?>

        <div class="form-grupo">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" required maxlength="120"
                   placeholder="Ex: 5kg de arroz tipo 1">
        </div>

        <div class="form-grupo">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" maxlength="500"
                      placeholder="Detalhes sobre os alimentos, quantidade, validade etc."></textarea>
        </div>

        <div class="form-grupo">
            <label for="dt_limite_retirada">Data limite para retirada *</label>
            <input type="date" id="dt_limite_retirada" name="dt_limite_retirada" required
                   min="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-grupo">
            <label for="endereco_retirada">Endereço de retirada *</label>
            <input type="text" id="endereco_retirada" name="endereco_retirada" required maxlength="255"
                   placeholder="Rua, número, bairro, cidade">
        </div>

        <div style="display:flex;gap:.75rem;margin-top:.5rem">
            <button type="submit" class="btn btn-primario">Publicar doação</button>
            <a href="/painel" class="btn btn-secundario">Cancelar</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
