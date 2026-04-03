<?php
/**
 * Partial: campos de cartão de crédito
 * Variáveis opcionais:
 *   $cardPrefix string - prefixo dos campos (default: 'cc')
 *   $cardTitle string - título da seção
 *   $cardNote string - nota abaixo dos campos
 *   $cardRequired bool - se os campos são required (default: true)
 *   $prefillCpf string - CPF pré-preenchido
 *   $prefillPostalCode string - CEP pré-preenchido
 *   $prefillAddressNumber string - número pré-preenchido
 *   $prefillPhone string - telefone pré-preenchido
 */
$cardPrefix = $cardPrefix ?? 'cc';
$cardTitle = $cardTitle ?? 'Dados do cartão de crédito';
$cardNote = $cardNote ?? null;
$cardRequired = $cardRequired ?? true;
$req = $cardRequired ? 'required' : '';
$prefillCpf = $prefillCpf ?? '';
$prefillPostalCode = $prefillPostalCode ?? '';
$prefillAddressNumber = $prefillAddressNumber ?? '';
$prefillPhone = $prefillPhone ?? '';
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<div class="cc-section">
    <div class="cc-title"><?= $e($cardTitle) ?></div>
    <?php if ($cardNote): ?>
        <div class="cc-note"><?= $e($cardNote) ?></div>
    <?php endif; ?>

    <div class="cc-grid2">
        <div class="lc-field">
            <label class="lc-label">Nome no cartão</label>
            <input class="lc-input" type="text" name="cc_holder" <?= $req ?> placeholder="Como está no cartão" autocomplete="cc-name" />
        </div>
        <div class="lc-field">
            <label class="lc-label">CPF do titular</label>
            <input class="lc-input" type="text" name="cpf" id="ccCpf" <?= $req ?> placeholder="000.000.000-00" value="<?= $e($prefillCpf) ?>" inputmode="numeric" />
        </div>
    </div>

    <div class="lc-field" style="margin-top:10px;">
        <label class="lc-label">Número do cartão</label>
        <input class="lc-input" type="text" name="cc_number" id="ccNumber" <?= $req ?> placeholder="0000 0000 0000 0000" inputmode="numeric" autocomplete="cc-number" maxlength="19" />
    </div>

    <div class="cc-grid3" style="margin-top:10px;">
        <div class="lc-field">
            <label class="lc-label">Mês</label>
            <input class="lc-input" type="text" name="cc_exp_month" id="ccMonth" <?= $req ?> placeholder="MM" inputmode="numeric" maxlength="2" autocomplete="cc-exp-month" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Ano</label>
            <input class="lc-input" type="text" name="cc_exp_year" id="ccYear" <?= $req ?> placeholder="AAAA" inputmode="numeric" maxlength="4" autocomplete="cc-exp-year" />
        </div>
        <div class="lc-field">
            <label class="lc-label">CVV</label>
            <input class="lc-input" type="password" name="cc_cvv" id="ccCvv" <?= $req ?> placeholder="000" inputmode="numeric" maxlength="4" autocomplete="cc-csc" />
        </div>
    </div>

    <div class="cc-grid2" style="margin-top:10px;">
        <div class="lc-field">
            <label class="lc-label">CEP do titular</label>
            <input class="lc-input" type="text" name="postal_code" id="ccCep" <?= $req ?> placeholder="00000-000" value="<?= $e($prefillPostalCode) ?>" inputmode="numeric" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Número do endereço</label>
            <input class="lc-input" type="text" name="address_number" <?= $req ?> placeholder="Ex: 123" value="<?= $e($prefillAddressNumber) ?>" />
        </div>
    </div>

    <div class="lc-field" style="margin-top:10px;max-width:280px;">
        <label class="lc-label">Celular <span style="font-weight:500;font-size:11px;color:rgba(31,41,55,.40);">(opcional)</span></label>
        <input class="lc-input" type="text" name="mobile" id="ccMobile" placeholder="(00) 00000-0000" value="<?= $e($prefillPhone) ?>" inputmode="numeric" />
    </div>

    <div style="margin-top:10px;padding:10px 12px;border-radius:10px;background:rgba(99,102,241,.04);border:1px solid rgba(99,102,241,.10);font-size:11px;color:rgba(31,41,55,.50);line-height:1.5;">
        🔒 Seus dados de pagamento são transmitidos com segurança diretamente ao gateway de pagamento. Não armazenamos dados do cartão.
    </div>
</div>

<script>
(function() {
    function ccMask(el, mask) {
        if (!el) return;
        el.addEventListener('input', function() {
            var v = this.value.replace(/\D/g, ''), r = '', vi = 0;
            for (var i = 0; i < mask.length && vi < v.length; i++) {
                r += mask[i] === '0' ? v[vi++] : mask[i];
            }
            this.value = r;
        });
    }
    // Card number: 0000 0000 0000 0000
    var cn = document.getElementById('ccNumber');
    if (cn) {
        cn.addEventListener('input', function() {
            var v = this.value.replace(/\D/g, '').substring(0, 16);
            this.value = v.replace(/(.{4})/g, '$1 ').trim();
        });
    }
    ccMask(document.getElementById('ccMonth'), '00');
    ccMask(document.getElementById('ccYear'), '0000');
    ccMask(document.getElementById('ccCvv'), '0000');
    ccMask(document.getElementById('ccCpf'), '000.000.000-00');
    ccMask(document.getElementById('ccCep'), '00000-000');
    var mob = document.getElementById('ccMobile');
    if (mob) {
        mob.addEventListener('input', function() {
            var v = this.value.replace(/\D/g, '');
            var mask = v.length <= 10 ? '(00) 0000-0000' : '(00) 00000-0000';
            var r = '', vi = 0;
            for (var i = 0; i < mask.length && vi < v.length; i++) {
                r += mask[i] === '0' ? v[vi++] : mask[i];
            }
            this.value = r;
        });
    }
})();
</script>
