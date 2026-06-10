<?php
$tituloPagina = 'Doações — Admin';

if (isset($_GET['encerrada'])) $_SESSION['sucesso'] = 'Doação encerrada.';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Moderar doações</h1>
    <a href="/admin" class="btn btn-secundario">← Painel</a>
</div>

<div class="card mt-2">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Doador (ID)</th>
                    <th>Retirada até</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doacoes as $d): ?>
                <tr>
                    <td><?= (int)$d['id'] ?></td>
                    <td><?= htmlspecialchars($d['titulo']) ?></td>
                    <td><?= (int)$d['id_doador'] ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($d['dt_limite_retirada']))) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($d['status']) ?>"><?= htmlspecialchars($d['status']) ?></span></td>
                    <td>
                        <?php if ($d['status'] === 'disponivel' || $d['status'] === 'reservado'): ?>
                        <form method="POST" action="/admin/encerrar" style="display:inline"
                              onsubmit="return confirm('Encerrar esta doação?')">
                            <?= $csrfCampo ?>
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button class="btn btn-perigo btn-sm">Encerrar</button>
                        </form>
                        <?php else: ?>
                            <span class="texto-cinza texto-pequeno">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
