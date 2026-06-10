<?php
$tituloPagina = 'Minhas reservas — Salve Alimento';

if (isset($_GET['reservada'])) $_SESSION['sucesso'] = 'Solicitação enviada! Aguarde a aprovação do doador.';
include __DIR__ . '/../layout/cabecalho.php';
?>

<h1 class="pagina-titulo">Minhas reservas</h1>

<?php if (empty($solicitacoes)): ?>
    <div class="vazio">
        <p>Você ainda não fez nenhuma solicitação de reserva.</p>
        <a href="/doacoes" class="btn btn-primario mt-2">Ver doações disponíveis</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="tabela-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Doação</th>
                        <th>Endereço</th>
                        <th>Retirada até</th>
                        <th>Observação</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitacoes as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['titulo_doacao'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($s['endereco_retirada'] ?? '—') ?></td>
                        <td><?= htmlspecialchars(isset($s['dt_limite_retirada']) ? date('d/m/Y', strtotime($s['dt_limite_retirada'])) : '—') ?></td>
                        <td><?= htmlspecialchars($s['obs'] ?? '—') ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
