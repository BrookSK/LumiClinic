<?php
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $procedures */
/** @var list<array<string,mixed>> $categories */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Serviços';

$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Serviços</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Cadastre os serviços oferecidos pela clínica. Eles aparecem na agenda ao criar um agendamento.</div>
    </div>
    <?php if ($can('services.manage')): ?>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newServiceForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo serviço</button>
    <?php endif; ?>
</div>

<!-- Formulário novo serviço -->
<?php if ($can('services.manage')): ?>
<div id="newServiceForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Novo serviço</div>
        <form method="post" action="/services/create">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="allow_specific_professional" value="0" />

            <div class="lc-field">
                <label class="lc-label">Nome do serviço</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Consulta Dermatológica, Limpeza de Pele..." />
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:4px;">
                <div class="lc-field">
                    <label class="lc-label">Duração (minutos)</label>
                    <input class="lc-input" type="number" name="duration_minutes" min="5" step="5" value="30" required />
                    <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Tempo que o serviço ocupa na agenda.</div>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Preço (R$)</label>
                    <input class="lc-input" type="text" name="price" placeholder="0,00" inputmode="decimal" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Categoria</label>
                    <select class="lc-select" name="category_id">
                        <option value="">Nenhuma</option>
                        <?php foreach (($categories ?? []) as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="lc-field" style="margin-top:4px;">
                <label class="lc-label">Procedimento vinculado</label>
                <select class="lc-select" name="procedure_id" style="max-width:300px;">
                    <option value="">Nenhum</option>
                    <?php foreach (($procedures ?? []) as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Vincule a um procedimento para rastrear custos de materiais.</div>
            </div>

            <div style="display:flex;gap:10px;margin-top:14px;">
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar serviço</button>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="document.getElementById('newServiceForm').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📋</div>
        <div style="font-size:14px;">Nenhum serviço cadastrado ainda.</div>
    </div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead><tr><th>Serviço</th><th>Categoria</th><th>Duração</th><th>Preço</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $pc = $it['price_cents'] ?? null;
                $display = $pc === null ? '—' : ('R$ ' . number_format(((float)(int)$pc) / 100.0, 2, ',', '.'));
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php $procName = trim((string)($it['procedure_name'] ?? '')); ?>
                        <?php if ($procName !== ''): ?>
                            <div style="font-size:11px;color:rgba(31,41,55,.45);">Procedimento: <?= htmlspecialchars($procName, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;"><?= htmlspecialchars((string)($it['category_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:13px;"><?= (int)$it['duration_minutes'] ?> min</td>
                    <td style="font-size:13px;font-weight:600;"><?= htmlspecialchars($display, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;">
                        <?php if ($can('services.manage')): ?>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/services/materials?service_id=<?= (int)$it['id'] ?>">Materiais</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($can('services.manage')): ?>
<div style="margin-top:14px;">
    <a style="font-size:13px;color:rgba(129,89,1,1);font-weight:600;text-decoration:none;" href="/services/categories">Gerenciar categorias de serviço →</a>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
