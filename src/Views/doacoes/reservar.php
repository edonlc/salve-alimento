<?php
$tituloPagina = 'Solicitar reserva — Salve Alimento';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Solicitar reserva</h1>
    <a href="/doacoes" class="btn btn-secundario">← Voltar</a>
</div>

<div class="card mt-2" style="max-width:560px">
    <div class="card-titulo"><?= htmlspecialchars($doacao['titulo'] ?? '') ?></div>

    <?php if (!empty($doacao['descricao'])): ?>
        <p class="texto-cinza mb-2"><?= htmlspecialchars($doacao['descricao']) ?></p>
    <?php endif; ?>

    <p class="texto-pequeno texto-cinza mb-2">
        📍 <?= htmlspecialchars($doacao['endereco_retirada'] ?? '') ?> &nbsp;·&nbsp;
        📅 Até <?= htmlspecialchars(isset($doacao['dt_limite_retirada']) ? date('d/m/Y', strtotime($doacao['dt_limite_retirada'])) : '') ?>
    </p>

    <form method="POST" action="/doacoes/reservar">
        <?= $csrfCampo ?>
        <input type="hidden" name="id" value="<?= (int)($doacao['id'] ?? 0) ?>">

        <div class="form-grupo">
            <label for="obs">Observação (opcional)</label>
            <textarea id="obs" name="obs" maxlength="300"
                      placeholder="Informe detalhes relevantes para o doador, como horário de disponibilidade para retirada."></textarea>
        </div>

        <div class="alerta alerta-aviso">
            Ao confirmar, a doação ficará reservada para você. O doador precisará aprovar a solicitação.
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1rem">
            <button type="submit" class="btn btn-primario">Confirmar solicitação</button>
            <a href="/doacoes" class="btn btn-secundario">Cancelar</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
