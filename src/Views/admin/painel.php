<?php
$tituloPagina = 'Painel Admin — Salve Alimento';
include __DIR__ . '/../layout/cabecalho.php';
?>

<h1 class="pagina-titulo">Painel administrativo</h1>

<div class="stats-grade">
    <div class="stat-card">
        <div class="numero"><?= (int)$totalUsuarios ?></div>
        <div class="rotulo">Usuários</div>
    </div>
    <div class="stat-card">
        <div class="numero"><?= (int)$totalDoacoes ?></div>
        <div class="rotulo">Doações</div>
    </div>
    <div class="stat-card">
        <div class="numero"><?= (int)$totalLogs ?></div>
        <div class="rotulo">Eventos auditados</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:<?= $integridadeLog ? 'var(--sucesso)' : 'var(--erro)' ?>">
            <?= $integridadeLog ? '✓' : '✗' ?>
        </div>
        <div class="rotulo">Integridade dos logs</div>
    </div>
</div>

<?php if (!$integridadeLog): ?>
<div class="alerta alerta-erro">
    <strong>Atenção:</strong> A cadeia de hash dos logs de auditoria foi violada. Investigue imediatamente.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;flex-wrap:wrap">

    <div class="card">
        <div class="card-titulo">Acesso rápido</div>
        <div style="display:flex;flex-direction:column;gap:.6rem">
            <a href="/admin/usuarios" class="btn btn-secundario">Gerenciar usuários</a>
            <a href="/admin/doacoes" class="btn btn-secundario">Moderar doações</a>
            <a href="/admin/logs" class="btn btn-secundario">Logs de auditoria</a>
            <a href="/admin/relatorio" class="btn btn-secundario">Relatório geral</a>
        </div>
    </div>

    <div class="card">
        <div class="card-titulo">Últimos eventos auditados</div>
        <?php if (empty($ultimosLogs)): ?>
            <p class="texto-cinza">Nenhum evento registrado.</p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:.5rem">
            <?php foreach ($ultimosLogs as $log): ?>
                <div style="font-size:.8rem;padding:.4rem 0;border-bottom:1px solid var(--borda)">
                    <span style="color:var(--cinza)"><?= htmlspecialchars(date('d/m H:i', strtotime($log['dt_evento']))) ?></span>
                    <strong> <?= htmlspecialchars($log['acao']) ?></strong>
                    <span class="texto-cinza"> — <?= htmlspecialchars($log['tabela']) ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
