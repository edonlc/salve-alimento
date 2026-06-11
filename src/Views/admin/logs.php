<?php
$tituloPagina = 'Logs de auditoria — Admin';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Logs de auditoria</h1>
    <a href="/admin" class="btn btn-secundario">← Painel</a>
</div>

<div class="alerta <?= $integro ? 'alerta-sucesso' : 'alerta-erro' ?> mt-2">
    <?php if ($integro): ?>
        Cadeia de hash íntegra — nenhuma adulteração detectada.
    <?php else: ?>
        <strong>Atenção:</strong> A cadeia de hash foi violada. Os dados podem ter sido adulterados.
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Data/Hora</th>
                    <th>Ação</th>
                    <th>Tabela</th>
                    <th>ID Registro</th>
                    <th>Usuário ID</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="texto-center texto-cinza">Nenhum evento registrado.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= (int)$log['id'] ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['dt_evento']))) ?></td>
                    <td><strong><?= htmlspecialchars($log['acao']) ?></strong></td>
                    <td><?= htmlspecialchars($log['tabela'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['id_registro'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['id_usuario'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['ip'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
<div class="paginacao">
    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
        <?php if ($p === $pagina): ?>
            <span class="atual"><?= $p ?></span>
        <?php else: ?>
            <a href="/admin/logs?pagina=<?= $p ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<p class="texto-cinza texto-pequeno texto-center mt-1">
    Total: <?= (int)$total ?> evento(s)
</p>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
