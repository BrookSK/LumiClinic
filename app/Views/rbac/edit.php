<?php
$title = 'Editar papel';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$role = $role ?? null;
$catalog = $catalog ?? [];
$decisions = $decisions ?? ['allow' => [], 'deny' => []];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

$allow = is_array($decisions['allow'] ?? null) ? $decisions['allow'] : [];
$deny = is_array($decisions['deny'] ?? null) ? $decisions['deny'] : [];

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Permissões do papel</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$role): ?>
        <div class="lc-alert lc-alert--danger">Role não encontrada.</div>
    <?php else: ?>
        <div class="lc-badge lc-badge--primary" style="margin-bottom:12px;">
            <?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars((string)$role['code'], ENT_QUOTES, 'UTF-8') ?>)
        </div>

        <?php if ((int)$role['is_editable'] !== 1): ?>
            <div class="lc-alert">Este papel é travado e não pode ser editado.</div>
        <?php endif; ?>

        <?php if ($can('rbac.manage')): ?>
            <form method="post" action="/rbac/edit" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$role['id'] ?>" />

                <label class="lc-label">Nome do papel</label>
                <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?>" <?= ((int)$role['is_editable'] !== 1) ? 'disabled' : '' ?> />

                <?php if ((int)$role['is_editable'] === 1): ?>
                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px; margin-bottom:6px;">
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" id="lcSelectAllAllow">Marcar tudo como Allow</button>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" id="lcClearAll">Limpar tudo</button>
                </div>
                <?php endif; ?>

                <div class="lc-table-wrap" style="margin-top:14px;">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Módulo</th>
                            <th>Ação</th>
                            <th>Permissão</th>
                            <th>Permitir</th>
                            <th>Negar</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $moduleLabels = [
                            'scheduling' => 'Agenda',
                            'patients' => 'Pacientes',
                            'medical_records' => 'Prontuários',
                            'medical_images' => 'Imagens Clínicas',
                            'anamnesis' => 'Anamnese',
                            'consent_terms' => 'Termos de Consentimento',
                            'finance' => 'Financeiro',
                            'stock' => 'Estoque',
                            'marketing' => 'Marketing',
                            'rbac' => 'Permissões',
                            'users' => 'Usuários',
                            'settings' => 'Configurações',
                            'clinics' => 'Clínica',
                            'audit' => 'Auditoria',
                            'compliance' => 'Compliance',
                            'reports' => 'Relatórios',
                            'procedures' => 'Procedimentos',
                            'schedule_rules' => 'Regras de Agenda',
                        ];
                        $actionLabels = [
                            'read' => 'Visualizar',
                            'create' => 'Criar',
                            'update' => 'Editar',
                            'delete' => 'Excluir',
                            'cancel' => 'Cancelar',
                            'manage' => 'Gerenciar',
                            'finalize' => 'Finalizar',
                            'fill' => 'Preencher',
                            'accept' => 'Aceitar',
                            'ops' => 'Operações',
                            'refund' => 'Estornar',
                            'export' => 'Exportar',
                        ];
                        ?>
                        <?php foreach ($catalog as $p): ?>
                            <?php
                            $code = (string)$p['code'];
                            $isAllow = in_array($code, $allow, true);
                            $isDeny = in_array($code, $deny, true);
                            $modRaw = (string)$p['module'];
                            $actRaw = (string)$p['action'];
                            $modLabel = $moduleLabels[$modRaw] ?? ucfirst(str_replace('_', ' ', $modRaw));
                            $actLabel = $actionLabels[$actRaw] ?? ucfirst(str_replace('_', ' ', $actRaw));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($modLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($actLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div style="font-weight:650; font-size:12px; color:var(--lc-muted);"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="lc-muted" style="font-size:12px;">
                                        <?= htmlspecialchars((string)($p['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td>
                                    <input type="checkbox" class="lc-rbac-allow" name="allow[]" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $isAllow ? 'checked' : '' ?> <?= ((int)$role['is_editable'] !== 1) ? 'disabled' : '' ?> />
                                </td>
                                <td>
                                    <input type="checkbox" class="lc-rbac-deny" name="deny[]" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $isDeny ? 'checked' : '' ?> <?= ((int)$role['is_editable'] !== 1) ? 'disabled' : '' ?> />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                    <?php if ((int)$role['is_editable'] === 1): ?>
                        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    <?php endif; ?>
                    <a class="lc-btn lc-btn--secondary" href="/rbac">Voltar</a>
                </div>
            </form>

            <?php if ((int)$role['is_editable'] === 1): ?>
                <form method="post" action="/rbac/reset" style="margin-top:10px;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="id" value="<?= (int)$role['id'] ?>" />
                    <button class="lc-btn lc-btn--danger" type="submit">Resetar padrão</button>
                </form>

                <script>
                (function(){
                    var btnAll = document.getElementById('lcSelectAllAllow');
                    var btnClear = document.getElementById('lcClearAll');
                    if (btnAll) {
                        btnAll.addEventListener('click', function(){
                            document.querySelectorAll('.lc-rbac-allow').forEach(function(cb){ cb.checked = true; });
                            document.querySelectorAll('.lc-rbac-deny').forEach(function(cb){ cb.checked = false; });
                        });
                    }
                    if (btnClear) {
                        btnClear.addEventListener('click', function(){
                            document.querySelectorAll('.lc-rbac-allow, .lc-rbac-deny').forEach(function(cb){ cb.checked = false; });
                        });
                    }
                })();
                </script>
            <?php endif; ?>
        <?php else: ?>
            <div class="lc-alert lc-alert--danger">Acesso negado.</div>
            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <a class="lc-btn lc-btn--secondary" href="/rbac">Voltar</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
