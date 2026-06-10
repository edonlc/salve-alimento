<?php
$tituloPagina = 'Relatório — Admin';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Relatório geral</h1>
    <a href="/admin" class="btn btn-secundario">← Painel</a>
</div>

<div class="stats-grade mt-2">
    <div class="stat-card">
        <div class="numero"><?= (int)$stats['total_usuarios'] ?></div>
        <div class="rotulo">Total de usuários</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:var(--sucesso)"><?= (int)$stats['usuarios_ativos'] ?></div>
        <div class="rotulo">Ativos</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:#856404"><?= (int)$stats['usuarios_pendentes'] ?></div>
        <div class="rotulo">Pendentes</div>
    </div>
    <div class="stat-card">
        <div class="numero"><?= (int)$stats['total_doacoes'] ?></div>
        <div class="rotulo">Total de doações</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:var(--verde)"><?= (int)$stats['doacoes_disponiveis'] ?></div>
        <div class="rotulo">Disponíveis</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:#055160"><?= (int)$stats['doacoes_concluidas'] ?></div>
        <div class="rotulo">Concluídas</div>
    </div>
    <div class="stat-card">
        <div class="numero"><?= (int)$stats['total_solicitacoes'] ?></div>
        <div class="rotulo">Solicitações</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:var(--sucesso)"><?= (int)$stats['solicitacoes_aprovadas'] ?></div>
        <div class="rotulo">Aprovadas</div>
    </div>
    <div class="stat-card">
        <div class="numero" style="color:var(--cinza)"><?= (int)$stats['total_logs'] ?></div>
        <div class="rotulo">Eventos auditados</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

    <div class="card">
        <div class="card-titulo">Usuários por perfil</div>
        <?php
        $porPerfil = [];
        foreach ($usuarios as $u) {
            $porPerfil[$u['perfil']] = ($porPerfil[$u['perfil']] ?? 0) + 1;
        }
        ?>
        <table>
            <tr><th>Perfil</th><th>Qtd</th></tr>
            <?php foreach ($porPerfil as $perfil => $qtd): ?>
            <tr>
                <td><?= htmlspecialchars($perfil) ?></td>
                <td><strong><?= (int)$qtd ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-titulo">Doações por status</div>
        <?php
        $porStatus = [];
        foreach ($doacoes as $d) {
            $porStatus[$d['status']] = ($porStatus[$d['status']] ?? 0) + 1;
        }
        ?>
        <table>
            <tr><th>Status</th><th>Qtd</th></tr>
            <?php foreach ($porStatus as $status => $qtd): ?>
            <tr>
                <td><span class="badge badge-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></span></td>
                <td><strong><?= (int)$qtd ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
