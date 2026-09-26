document.addEventListener('DOMContentLoaded', function () {
    loadSettings();
    bindQuickActions();
});

function bindQuickActions() {
    bindClick('btn-reboot', function () {
        var msg = document.getElementById('reboot-msg');
        postAction('ajax/reboot.php', {}, msg);
    });

    bindClick('btn-factory-reset', function () {
        if (!confirm(window.i18n.factory_reset_confirm)) {
            return;
        }
        var msg = document.getElementById('factory-reset-msg');
        postAction('ajax/factory_reset.php', {}, msg);
    });

    bindClick('btn-rename', function () {
        var msg = document.getElementById('rename-msg');
        var name = document.getElementById('rename-input').value.trim();
        if (!name) {
            return;
        }
        postAction('ajax/rename_device.php', { name: name }, msg);
    });

    bindClick('btn-fw-check', function () {
        var msg = document.getElementById('fw-msg');
        var body = new FormData();
        body.append('csrf_token', window.csrfToken);
        body.append('id', window.deviceId);
        body.append('action', 'check');
        fetch('ajax/firmware.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    msg.textContent = window.i18n.save_failed;
                    msg.className = 'save-msg error';
                    return;
                }
                var hasUpdate = data.result && (data.result.has_update || (data.result.new_version));
                msg.textContent = hasUpdate ? window.i18n.firmware_available : window.i18n.firmware_up_to_date;
                msg.className = 'save-msg success';
            })
            .catch(function () {
                msg.textContent = window.i18n.save_failed;
                msg.className = 'save-msg error';
            });
    });

    bindClick('btn-fw-update', function () {
        if (!confirm(window.i18n.firmware_update_confirm)) {
            return;
        }
        var msg = document.getElementById('fw-msg');
        var body = new FormData();
        body.append('csrf_token', window.csrfToken);
        body.append('id', window.deviceId);
        body.append('action', 'update');
        postFormAction('ajax/firmware.php', body, msg);
    });

    bindClick('btn-auth', function () {
        var msg = document.getElementById('auth-msg');
        var enabled = document.getElementById('auth-enabled').checked;
        var password = document.getElementById('auth-password').value;
        if (enabled && !password) {
            msg.textContent = window.i18n.auth_password_required;
            msg.className = 'save-msg error';
            return;
        }
        postAction('ajax/set_auth.php', { enabled: enabled ? '1' : '0', password: password }, msg);
    });
}

function bindClick(id, handler) {
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('click', handler);
    }
}

function postAction(url, params, msgEl, onSuccess) {
    var body = new FormData();
    body.append('csrf_token', window.csrfToken);
    body.append('id', window.deviceId);
    Object.keys(params).forEach(function (key) {
        body.append(key, params[key]);
    });
    postFormAction(url, body, msgEl, onSuccess);
}

function postFormAction(url, body, msgEl, onSuccess) {
    fetch(url, { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            msgEl.textContent = data.success ? window.i18n.saved : window.i18n.save_failed;
            msgEl.className = 'save-msg ' + (data.success ? 'success' : 'error');
            if (data.success && onSuccess) {
                onSuccess();
            }
        })
        .catch(function () {
            msgEl.textContent = window.i18n.save_failed;
            msgEl.className = 'save-msg error';
        });
}

function loadSettings() {
    fetch('ajax/device_config.php?id=' + window.deviceId)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('settings-loading').style.display = 'none';
            if (data.error) {
                document.getElementById('settings-sections').innerHTML =
                    '<p class="alert alert-error">' + window.i18n.load_failed + '</p>';
                return;
            }
            renderSections(data.sections || []);
        })
        .catch(function () {
            document.getElementById('settings-loading').style.display = 'none';
            document.getElementById('settings-sections').innerHTML =
                '<p class="alert alert-error">' + window.i18n.load_failed + '</p>';
        });
}

var CATEGORY_ORDER = ['network', 'connectivity', 'device', 'components', 'advanced'];

function renderSections(sections) {
    var container = document.getElementById('settings-sections');
    container.innerHTML = '';

    var groups = {};
    sections.forEach(function (section) {
        var cat = section.category || 'advanced';
        if (!groups[cat]) {
            groups[cat] = [];
        }
        groups[cat].push(section);
    });

    CATEGORY_ORDER.forEach(function (cat) {
        if (!groups[cat] || !groups[cat].length) {
            return;
        }
        var heading = document.createElement('h2');
        heading.className = 'settings-category';
        heading.textContent = window.i18n['cat_' + cat] || cat;
        container.appendChild(heading);

        groups[cat].forEach(function (section) {
            container.appendChild(renderSectionCard(section));
        });
    });
}

function renderSectionCard(section) {
    var card = document.createElement('section');
    card.className = 'card settings-section';

    var title = document.createElement('h3');
    title.textContent = section.label + ' (' + section.key + ')';
    card.appendChild(title);

    var textarea = document.createElement('textarea');
    textarea.className = 'settings-json';
    textarea.rows = Math.min(20, Math.max(4, JSON.stringify(section.data, null, 2).split('\n').length));
    textarea.value = JSON.stringify(section.data, null, 2);
    card.appendChild(textarea);

    var msg = document.createElement('span');
    msg.className = 'save-msg';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-primary';
    btn.textContent = window.i18n.save;
    btn.addEventListener('click', function () {
        saveSection(section.key, textarea.value, msg);
    });
    card.appendChild(btn);
    card.appendChild(msg);

    return card;
}

function saveSection(key, jsonText, msgEl) {
    var parsed;
    try {
        parsed = JSON.parse(jsonText);
    } catch (e) {
        msgEl.textContent = 'JSON: ' + e.message;
        msgEl.className = 'save-msg error';
        return;
    }

    var body = new FormData();
    body.append('csrf_token', window.csrfToken);
    body.append('id', window.deviceId);
    body.append('key', key);
    body.append('data', JSON.stringify(parsed));

    fetch('ajax/device_config.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            msgEl.textContent = data.success ? window.i18n.saved : window.i18n.save_failed;
            msgEl.className = 'save-msg ' + (data.success ? 'success' : 'error');
        })
        .catch(function () {
            msgEl.textContent = window.i18n.save_failed;
            msgEl.className = 'save-msg error';
        });
}
