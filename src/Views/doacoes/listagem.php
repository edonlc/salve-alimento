<?php
$tituloPagina = 'Doações disponíveis — Salve Alimento';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Doações disponíveis</h1>
    <form method="GET" action="/doacoes" style="display:flex;gap:.5rem;align-items:center">
        <input type="text" name="regiao" placeholder="Filtrar por bairro/cidade"
               value="<?= htmlspecialchars($_GET['regiao'] ?? '') ?>"
               style="padding:.45rem .8rem;border:1px solid var(--borda);border-radius:7px;font-size:.875rem">
        <button type="submit" class="btn btn-secundario btn-sm">Filtrar</button>
        <?php if (!empty($_GET['regiao'])): ?>
            <a href="/doacoes" class="btn btn-secundario btn-sm">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($doacoes)): ?>
    <div class="vazio mt-2">
        <p>Nenhuma doação disponível no momento.</p>
    </div>
<?php else: ?>
    <div class="grade-doacoes mt-2">
        <?php foreach ($doacoes as $d): ?>
        <div class="card-doacao">
            <span class="badge badge-disponivel">disponível</span>
            <h3><?= htmlspecialchars($d['titulo']) ?></h3>
            <p><?= htmlspecialchars($d['descricao'] ?? 'Sem descrição adicional.') ?></p>
            <div class="meta">
                📍 <?= htmlspecialchars($d['endereco_retirada']) ?><br>
                📅 Até <?= htmlspecialchars(date('d/m/Y', strtotime($d['dt_limite_retirada']))) ?>
            </div>
            <a href="/doacoes/reservar?id=<?= (int)$d['id'] ?>" class="btn btn-primario btn-sm mt-1">
                Solicitar reserva
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
