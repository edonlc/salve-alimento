<?php
$tituloPagina = 'Usuários — Admin';

if (isset($_GET['ativado']))   $_SESSION['sucesso'] = 'Usuário ativado.';
if (isset($_GET['bloqueado'])) $_SESSION['sucesso'] = 'Usuário bloqueado.';
include __DIR__ . '/../layout/cabecalho.php';
?>

<div class="flex-entre">
    <h1 class="pagina-titulo" style="margin-bottom:0">Gerenciar usuários</h1>
    <a href="/admin" class="btn btn-secundario">← Painel</a>
</div>

<div class="card mt-2">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['perfil']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['criado_em']))) ?></td>
                    <td>
                        <?php if ($u['status'] !== 'ativo'): ?>
                        <form method="POST" action="/admin/ativar" style="display:inline">
                            <?= $csrfCampo ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn btn-primario btn-sm">Ativar</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($u['status'] !== 'bloqueado'): ?>
                        <form method="POST" action="/admin/bloquear" style="display:inline"
                              onsubmit="return confirm('Bloquear este usuário?')">
                            <?= $csrfCampo ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn btn-perigo btn-sm">Bloquear</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layout/rodape.php'; ?>
