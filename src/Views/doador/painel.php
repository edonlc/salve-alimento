<?php
$tituloPagina = 'Meu Painel — Salve Alimento';

// Flash messages via query string
if (isset($_GET['criada']))   $_SESSION['sucesso'] = 'Doação cadastrada com sucesso!';
if (isset($_GET['editada']))  $_SESSION['sucesso'] = 'Doação atualizada com sucesso!';
if (isset($_GET['excluida'])) $_SESSION['sucesso'] = 'Doação excluída.';
if (isset($_GET['aprovada'])) $_SESSION['sucesso'] = 'Reserva aprovada!';
if (isset($_GET['recusada'])) $_SESSION['sucesso'] = 'Reserva recusada. A doação voltou a ficar disponível.';
if (isset($_GET['concluida'])) $_SESSION['sucesso'] = 'Doação concluída com sucesso!';

include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Minhas doações</h1>
    <a href="/doacoes/criar" class="btn btn-primario">+ Nova doação</a>
</div>

<?php if (empty($doacoes)): ?>
    <div class="card vazio mt-2">
        <p>Você ainda não cadastrou nenhuma doação.</p>
        <a href="/doacoes/criar" class="btn btn-primario mt-2">Cadastrar primeira doação</a>
    </div>
<?php else: ?>
    <div class="card mt-2">
        <div class="tabela-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Retirada até</th>
                        <th>Endereço</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($doacoes as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['titulo']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($d['dt_limite_retirada']))) ?></td>
                        <td><?= htmlspecialchars($d['endereco_retirada']) ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($d['status']) ?>"><?= htmlspecialchars($d['status']) ?></span></td>
                        <td>
                            <?php if ($d['status'] === 'disponivel'): ?>
                                <a href="/doacoes/editar?id=<?= (int)$d['id'] ?>" class="btn btn-secundario btn-sm">Editar</a>
                                <form method="POST" action="/doacoes/excluir" style="display:inline"
                                      onsubmit="return confirm('Excluir esta doação?')">
                                    <?= $csrfCampo ?? '' ?>
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <button type="submit" class="btn btn-perigo btn-sm">Excluir</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($d['status'] === 'reservado'): ?>
                                <a href="#solicitacoes-<?= (int)$d['id'] ?>" class="btn btn-aviso btn-sm">Ver reserva ↓</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($d['status'] === 'reservado'): ?>
                    <?php
                        $solicitacoesDaDoacao = \SalveAlimento\Models\Solicitacao::listarPorDoacao($d['id']);
                    ?>
                    <?php if ($solicitacoesDaDoacao): ?>
                    <tr id="solicitacoes-<?= (int)$d['id'] ?>">
                        <td colspan="5" style="background:#fffbf0;padding:1rem">
                            <strong style="font-size:.85rem">Reservas desta doação:</strong>
                            <?php foreach ($solicitacoesDaDoacao as $s): ?>
                            <?php if ($s['status'] === 'pendente' || $s['status'] === 'aprovada'): ?>
                            <div style="display:flex;align-items:center;gap:.75rem;margin-top:.5rem;flex-wrap:wrap">
                                <span class="badge badge-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span>
                                <span class="texto-pequeno"><?= htmlspecialchars($s['obs'] ?? '(sem observação)') ?></span>
                                <?php if ($s['status'] === 'pendente'): ?>
                                <form method="POST" action="/solicitacoes/aprovar" style="display:inline">
                                    <?= $csrfCampo ?? '' ?>
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button class="btn btn-primario btn-sm">Aprovar</button>
                                </form>
                                <form method="POST" action="/solicitacoes/recusar" style="display:inline">
                                    <?= $csrfCampo ?? '' ?>
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button class="btn btn-perigo btn-sm">Recusar</button>
                                </form>
                                <?php elseif ($s['status'] === 'aprovada'): ?>
                                <form method="POST" action="/solicitacoes/concluir" style="display:inline"
                                      onsubmit="return confirm('Confirmar retirada e concluir doação?')">
                                    <?= $csrfCampo ?? '' ?>
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button class="btn btn-primario btn-sm">Confirmar retirada</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
