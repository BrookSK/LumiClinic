<?php
$title = 'Configurações - WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$diagnose = $diagnose ?? null;
$connectData = $connect_data ?? null;
$evolutionInstance = $evolution_instance ?? null;
$evolutionApiKeySet = $evolution_apikey_set ?? false;
$globalConfigured = $global_configured ?? false;

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
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">WhatsApp (Evolution API)</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-card__body">
        <?php if (!$globalConfigured): ?>
            <div class="lc-muted" style="margin-bottom:10px;">
                A API key é salva criptografada por clínica. Não exibimos o valor após salvar.
            </div>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <label class="lc-label">Status</label>
            <?php
            $isConfigured = $globalConfigured || ($evolutionInstance !== null && $evolutionInstance !== '' && $evolutionApiKeySet);
            ?>
            <div id="lc-wa-status" class="lc-badge <?= $isConfigured ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
                <?= $isConfigured ? 'Configurado' : 'Não configurado' ?>
            </div>

            <?php if ($globalConfigured): ?>
                <input type="hidden" id="lc-wa-csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <details style="margin-top:10px;">
                    <summary class="lc-muted" style="cursor:pointer;">Detalhes técnicos</summary>
                    <div class="lc-muted" style="margin-top:6px;">Instância: <code><?= htmlspecialchars((string)($evolutionInstance ?? ''), ENT_QUOTES, 'UTF-8') ?></code></div>
                </details>

                <div class="lc-flex lc-gap-sm" style="margin-top:10px; align-items:center; flex-wrap:wrap;">
                    <button id="lc-wa-connect" class="lc-btn lc-btn--primary" type="button" style="width:auto; padding:8px 12px;">Conectar</button>
                    <button id="lc-wa-disconnect" class="lc-btn lc-btn--danger" type="button" style="display:none; width:auto; padding:8px 12px;">Desconectar</button>
                    <a class="lc-btn lc-btn--secondary" href="/settings" style="width:auto; padding:8px 12px;">Voltar</a>
                </div>
            <?php else: ?>
                <form method="post" class="lc-form" action="/settings/whatsapp">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                    <label class="lc-label" style="margin-top:12px;">Instância</label>
                    <input class="lc-input" type="text" name="evolution_instance" value="<?= htmlspecialchars((string)($evolutionInstance ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="nome-da-instancia" autocomplete="off" />

                    <label class="lc-label" style="margin-top:12px;">API Key</label>
                    <input class="lc-input" type="password" name="evolution_apikey" placeholder="apikey" autocomplete="off" />

                    <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                        <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
            </div>
        <?php endif; ?>

        <?php if ($can('settings.update') && !$globalConfigured): ?>
            <form method="post" class="lc-form" action="/settings/whatsapp/test" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Testar conexão</button>
            </form>
        <?php endif; ?>

        <?php if (is_array($connectData)): ?>
            <?php
            $pairingCode = isset($connectData['pairingCode']) ? trim((string)$connectData['pairingCode']) : '';
            $code = '';
            if (isset($connectData['qrcode']) && is_array($connectData['qrcode'])) {
                $code = isset($connectData['qrcode']['base64']) ? trim((string)$connectData['qrcode']['base64']) : $code;
                if ($code === '') {
                    $code = isset($connectData['qrcode']['code']) ? trim((string)$connectData['qrcode']['code']) : $code;
                }
            }
            if ($code === '') {
                $code = isset($connectData['base64']) ? trim((string)$connectData['base64']) : $code;
            }
            if ($code === '') {
                $code = isset($connectData['code']) ? trim((string)$connectData['code']) : $code;
            }
            $count = isset($connectData['count']) ? (int)$connectData['count'] : null;
            $imgSrc = null;
            if ($code !== '') {
                if (stripos($code, 'data:image') === 0) {
                    $imgSrc = $code;
                } elseif (preg_match('/^[A-Za-z0-9+\/\r\n]+=*$/', $code) && strlen($code) > 120) {
                    $imgSrc = 'data:image/png;base64,' . $code;
                }
            }
            ?>
            <div id="lc-wa-qr" class="lc-card" style="margin-top:14px;">
                <div class="lc-card__title">Conectar WhatsApp</div>
                <div class="lc-card__body">
                    <div id="lc-wa-qr-img" style="display:<?= $imgSrc !== null ? 'flex' : 'none' ?>; justify-content:center;">
                        <?php if ($imgSrc !== null): ?>
                            <img id="lc-wa-qr-img-tag" src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code" style="max-width:260px; width:100%; height:auto;" />
                        <?php else: ?>
                            <img id="lc-wa-qr-img-tag" src="" alt="QR Code" style="max-width:260px; width:100%; height:auto;" />
                        <?php endif; ?>
                    </div>

                    <div id="lc-wa-pairing" class="lc-muted" style="margin-top:10px; display:<?= $pairingCode !== '' ? 'block' : 'none' ?>;">Pairing code: <code><?= htmlspecialchars($pairingCode, ENT_QUOTES, 'UTF-8') ?></code></div>

                    <div id="lc-wa-attempt" class="lc-muted" style="margin-top:6px; <?= $count !== null ? '' : 'display:none;' ?>">Tentativa: <?= htmlspecialchars((string)($count ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

                    <div id="lc-wa-raw" style="display:<?= ($imgSrc === null && $code !== '') ? 'block' : 'none' ?>;">
                        <div class="lc-muted" style="margin-top:10px;">Código retornado pela Evolution:</div>
                        <pre style="white-space:pre-wrap; word-break:break-word; margin-top:6px;"><code id="lc-wa-raw-code"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></code></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($globalConfigured && !is_array($connectData)): ?>
            <div id="lc-wa-qr" class="lc-card" style="margin-top:14px; display:none;">
                <div class="lc-card__title">Conectar WhatsApp</div>
                <div class="lc-card__body">
                    <div id="lc-wa-qr-img" style="display:none; justify-content:center;">
                        <img id="lc-wa-qr-img-tag" src="" alt="QR Code" style="max-width:260px; width:100%; height:auto;" />
                    </div>

                    <div id="lc-wa-pairing" class="lc-muted" style="margin-top:10px; display:none;"></div>
                    <div id="lc-wa-attempt" class="lc-muted" style="margin-top:6px; display:none;"></div>

                    <div id="lc-wa-raw" style="display:none;">
                        <div class="lc-muted" style="margin-top:10px;">Código retornado pela Evolution:</div>
                        <pre style="white-space:pre-wrap; word-break:break-word; margin-top:6px;"><code id="lc-wa-raw-code"></code></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($globalConfigured): ?>
            <script>
                (function () {
                    var csrfEl = document.getElementById('lc-wa-csrf');
                    var statusEl = document.getElementById('lc-wa-status');
                    var btnConnect = document.getElementById('lc-wa-connect');
                    var btnDisconnect = document.getElementById('lc-wa-disconnect');
                    var qrCard = document.getElementById('lc-wa-qr');
                    var qrImgWrap = document.getElementById('lc-wa-qr-img');
                    var qrImg = document.getElementById('lc-wa-qr-img-tag');
                    var pairingEl = document.getElementById('lc-wa-pairing');
                    var attemptEl = document.getElementById('lc-wa-attempt');
                    var rawWrap = document.getElementById('lc-wa-raw');
                    var rawCode = document.getElementById('lc-wa-raw-code');

                    var csrf = csrfEl ? csrfEl.value : '';
                    var lastQrAt = 0;
                    var refreshing = false;
                    var pollMs = 2500;
                    var qrTtlMs = 25000;

                    function setStatus(text, ok) {
                        if (!statusEl) {
                            return;
                        }
                        statusEl.textContent = text;
                        statusEl.className = 'lc-badge ' + (ok ? 'lc-badge--success' : 'lc-badge--secondary');
                    }

                    function setButtonsConnected(connected) {
                        if (btnConnect) {
                            btnConnect.style.display = connected ? 'none' : '';
                        }
                        if (btnDisconnect) {
                            btnDisconnect.style.display = connected ? '' : 'none';
                        }
                    }

                    function readQrFromConnectData(connectData) {
                        if (!connectData || typeof connectData !== 'object') {
                            return { imgSrc: null, pairingCode: '', count: null, raw: '' };
                        }

                        var pairingCode = (connectData.pairingCode || '').toString().trim();
                        var count = (typeof connectData.count === 'number') ? connectData.count : null;
                        var code = '';

                        if (connectData.qrcode && typeof connectData.qrcode === 'object') {
                            if (connectData.qrcode.base64) {
                                code = connectData.qrcode.base64.toString().trim();
                            }
                            if (!code && connectData.qrcode.code) {
                                code = connectData.qrcode.code.toString().trim();
                            }
                        }
                        if (!code && connectData.base64) {
                            code = connectData.base64.toString().trim();
                        }
                        if (!code && connectData.code) {
                            code = connectData.code.toString().trim();
                        }

                        var imgSrc = null;
                        if (code) {
                            if (code.indexOf('data:image') === 0) {
                                imgSrc = code;
                            } else if (/^[A-Za-z0-9+\/\r\n]+=*$/.test(code) && code.length > 120) {
                                imgSrc = 'data:image/png;base64,' + code;
                            }
                        }

                        return { imgSrc: imgSrc, pairingCode: pairingCode, count: count, raw: code };
                    }

                    function renderQr(connectData) {
                        var parsed = readQrFromConnectData(connectData);
                        if (!qrCard) {
                            return;
                        }
                        qrCard.style.display = '';

                        if (parsed.imgSrc) {
                            if (qrImg) {
                                qrImg.src = parsed.imgSrc;
                            }
                            if (qrImgWrap) {
                                qrImgWrap.style.display = 'flex';
                            }
                            if (rawWrap) {
                                rawWrap.style.display = 'none';
                            }
                        } else {
                            if (qrImgWrap) {
                                qrImgWrap.style.display = 'none';
                            }
                            if (rawWrap) {
                                rawWrap.style.display = parsed.raw ? 'block' : 'none';
                            }
                            if (rawCode) {
                                rawCode.textContent = parsed.raw || '';
                            }
                        }

                        if (pairingEl) {
                            pairingEl.style.display = parsed.pairingCode ? 'block' : 'none';
                            if (parsed.pairingCode) {
                                pairingEl.innerHTML = 'Pairing code: <code>' + escapeHtml(parsed.pairingCode) + '</code>';
                            }
                        }

                        if (attemptEl) {
                            attemptEl.style.display = (parsed.count !== null) ? 'block' : 'none';
                            if (parsed.count !== null) {
                                attemptEl.textContent = 'Tentativa: ' + parsed.count;
                            }
                        }

                        lastQrAt = Date.now();
                    }

                    function escapeHtml(s) {
                        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    }

                    function postJson(url, payload) {
                        payload = payload || {};
                        payload._csrf = csrf;
                        return fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                            body: JSON.stringify(payload)
                        }).then(function (r) {
                            return r.json().then(function (data) {
                                return { status: r.status, data: data };
                            });
                        });
                    }

                    function getStatus() {
                        return fetch('/settings/whatsapp/status-json', { credentials: 'same-origin' })
                            .then(function (r) {
                                return r.json().then(function (data) {
                                    return { status: r.status, data: data };
                                });
                            });
                    }

                    function refreshQr() {
                        if (refreshing) {
                            return;
                        }
                        refreshing = true;
                        if (btnConnect) {
                            btnConnect.disabled = true;
                        }

                        postJson('/settings/whatsapp/connect-json', {}).then(function (resp) {
                            refreshing = false;
                            if (btnConnect) {
                                btnConnect.disabled = false;
                            }
                            if (!resp.data || !resp.data.ok) {
                                return;
                            }
                            renderQr(resp.data.connect_data);
                        }).catch(function () {
                            refreshing = false;
                            if (btnConnect) {
                                btnConnect.disabled = false;
                            }
                        });
                    }

                    function disconnect() {
                        if (btnDisconnect) {
                            btnDisconnect.disabled = true;
                        }
                        postJson('/settings/whatsapp/disconnect', {}).then(function (resp) {
                            if (btnDisconnect) {
                                btnDisconnect.disabled = false;
                            }
                            if (!resp.data || !resp.data.ok) {
                                return;
                            }
                            setStatus('Desconectado', false);
                            setButtonsConnected(false);
                            lastQrAt = 0;
                            refreshQr();
                        }).catch(function () {
                            if (btnDisconnect) {
                                btnDisconnect.disabled = false;
                            }
                        });
                    }

                    function tick() {
                        getStatus().then(function (resp) {
                            if (!resp.data || !resp.data.ok) {
                                return;
                            }
                            var connected = !!resp.data.connected;
                            var state = resp.data.state ? resp.data.state.toString() : '';

                            if (connected) {
                                setStatus('Conectado', true);
                                setButtonsConnected(true);
                                return;
                            }

                            setButtonsConnected(false);
                            if (state) {
                                setStatus('Status: ' + state, false);
                            } else {
                                setStatus('Aguardando conexão', false);
                            }

                            var now = Date.now();
                            if (!lastQrAt || (now - lastQrAt) > qrTtlMs) {
                                refreshQr();
                            }
                        }).catch(function () {
                        });
                    }

                    if (btnConnect) {
                        btnConnect.addEventListener('click', function () {
                            refreshQr();
                        });
                    }

                    if (btnDisconnect) {
                        btnDisconnect.addEventListener('click', function () {
                            disconnect();
                        });
                    }

                    tick();
                    setInterval(tick, pollMs);
                })();
            </script>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/whatsapp/diagnose" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit" style="width:auto; padding:8px 12px;">Resolver problemas do WhatsApp</button>
            </form>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/whatsapp/clear" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <?php if (!$globalConfigured): ?>
                    <button class="lc-btn lc-btn--danger" type="submit" onclick="return confirm('Remover a configuração de WhatsApp desta clínica?');">Remover configuração</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <?php if (is_array($diagnose) && isset($diagnose['checks']) && is_array($diagnose['checks'])): ?>
            <div class="lc-card" style="margin-top:14px;">
                <div class="lc-card__title">Diagnóstico</div>
                <div class="lc-card__body">
                    <?php foreach ($diagnose['checks'] as $c): ?>
                        <?php
                        $ok = isset($c['ok']) ? (bool)$c['ok'] : false;
                        $title = (string)($c['title'] ?? '');
                        $message = (string)($c['message'] ?? '');
                        $actionLabel = $c['action_label'] ?? null;
                        $actionLabel = $actionLabel === null ? null : (string)$actionLabel;
                        $actionUrl = $c['action_url'] ?? null;
                        $actionUrl = $actionUrl === null ? null : (string)$actionUrl;
                        ?>
                        <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px; padding:10px 0; border-bottom:1px solid rgba(0,0,0,0.06);">
                            <div>
                                <div style="font-weight:600;">
                                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                                    <span class="lc-badge <?= $ok ? 'lc-badge--success' : 'lc-badge--danger' ?>" style="margin-left:8px;">
                                        <?= $ok ? 'OK' : 'Atenção' ?>
                                    </span>
                                </div>
                                <div class="lc-muted"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div>
                                <?php if ($actionLabel !== null && $actionUrl !== null): ?>
                                    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
