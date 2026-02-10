<?php
/** @var list<array<string,mixed>> $items */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Profissionais';
$users = $users ?? [];
$error = $error ?? '';

$userLabel = [];
foreach ($users as $u) {
    $id = (int)($u['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $name = trim((string)($u['name'] ?? ''));
    $email = trim((string)($u['email'] ?? ''));
    $label = $name;
    if ($email !== '') {
        $label = $label !== '' ? ($label . ' (' . $email . ')') : $email;
    }
    $userLabel[$id] = $label !== '' ? $label : ('Usuário #' . $id);
}

ob_start();
?>

<?php if (is_string($error) && trim($error) !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo profissional</div>
    <div class="lc-card__body">
        <form method="post" action="/professionals/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 2fr 1fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Como será o acesso do profissional?</label>
                <select class="lc-select" name="link_mode" id="lcProfessionalLinkMode">
                    <option value="existing" selected>Vincular a um usuário já cadastrado</option>
                    <option value="new">Criar novo usuário (com e-mail e senha)</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Especialidade</label>
                <input class="lc-input" type="text" name="specialty" />
            </div>

            <div class="lc-field" id="lcProfessionalExistingUser">
                <label class="lc-label">Selecione o usuário de login</label>
                <select class="lc-select" name="user_id">
                    <option value="">Selecione</option>
                    <?php foreach ($users as $u): ?>
                        <?php $uid = (int)($u['id'] ?? 0); ?>
                        <?php if ($uid <= 0) continue; ?>
                        <?php
                        $nm = trim((string)($u['name'] ?? ''));
                        $em = trim((string)($u['email'] ?? ''));
                        $lbl = $nm;
                        if ($em !== '') {
                            $lbl = $lbl !== '' ? ($lbl . ' (' . $em . ')') : $em;
                        }
                        ?>
                        <option value="<?= $uid ?>"><?= htmlspecialchars($lbl !== '' ? $lbl : ('Usuário #' . $uid), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field" id="lcProfessionalNewUserName" style="display:none;">
                <label class="lc-label">Nome do usuário</label>
                <input class="lc-input" type="text" name="new_user_name" />
            </div>

            <div class="lc-field" id="lcProfessionalNewUserEmail" style="display:none;">
                <label class="lc-label">E-mail de login</label>
                <input class="lc-input" type="email" name="new_user_email" />
            </div>

            <div class="lc-field" id="lcProfessionalNewUserPassword" style="display:none;">
                <label class="lc-label">Senha</label>
                <input class="lc-input" type="password" name="new_user_password" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Agendamento online?</label>
                <select class="lc-select" name="allow_online_booking">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
  try {
    var mode = document.getElementById('lcProfessionalLinkMode');
    var existing = document.getElementById('lcProfessionalExistingUser');
    var n1 = document.getElementById('lcProfessionalNewUserName');
    var n2 = document.getElementById('lcProfessionalNewUserEmail');
    var n3 = document.getElementById('lcProfessionalNewUserPassword');
    if (!mode || !existing || !n1 || !n2 || !n3) return;

    function setVisible(el, on){ el.style.display = on ? '' : 'none'; }
    function setRequired(inputName, on){
      var el = document.querySelector('[name="' + inputName + '"]');
      if (!el) return;
      if (on) el.setAttribute('required', 'required'); else el.removeAttribute('required');
    }

    function apply(){
      var v = (mode.value || 'existing');
      var isNew = v === 'new';
      setVisible(existing, !isNew);
      setVisible(n1, isNew);
      setVisible(n2, isNew);
      setVisible(n3, isNew);
      setRequired('user_id', !isNew);
      setRequired('new_user_name', isNew);
      setRequired('new_user_email', isNew);
      setRequired('new_user_password', isNew);
    }

    mode.addEventListener('change', apply);
    apply();
  } catch (e) {}
})();
</script>

<div class="lc-card">
    <div class="lc-card__header">Profissionais</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhum profissional cadastrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Especialidade</th>
                    <th>Usuário</th>
                    <th>Online</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['specialty'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <?php $uid = (int)($it['user_id'] ?? 0); ?>
                        <td><?= $uid > 0 ? htmlspecialchars((string)($userLabel[$uid] ?? ('Usuário #' . $uid)), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                        <td><?= ((int)$it['allow_online_booking'] === 1) ? 'Sim' : 'Não' ?></td>
                        <td class="lc-td-actions">
                            <a class="lc-btn lc-btn--secondary" href="/professionals/edit?id=<?= (int)$it['id'] ?>">Editar</a>
                            <form method="post" action="/professionals/delete" style="display:inline;" onsubmit="return confirm('Excluir (inativar) este profissional?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
