document.addEventListener('DOMContentLoaded', function () {
    initTreeToggle();
    initScanForm();
    initDeviceControl();
    initReboot();
});

function initReboot() {
    var btn = document.getElementById('btn-reboot');
    if (!btn) return;
    btn.addEventListener('click', function () {
        if (!confirm('Reboot?')) return;
        var body = new FormData();
        body.append('csrf_token', window.csrfToken);
        body.append('id', btn.getAttribute('data-device-id'));
        fetch('ajax/reboot.php', { method: 'POST', body: body });
    });
}

function initTreeToggle() {
    document.querySelectorAll('.tree-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var li = toggle.closest('.tree-group');
            li.classList.toggle('collapsed');
            toggle.textContent = li.classList.contains('collapsed') ? '▸' : '▾';
        });
    });
}

function initScanForm() {
    var form = document.getElementById('scan-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var statusEl = document.getElementById('scan-status');
        var resultsEl = document.getElementById('scan-results');
        statusEl.style.display = 'block';
        resultsEl.innerHTML = '';

        var formData = new FormData(form);

        fetch('ajax/scan.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                statusEl.style.display = 'none';
                renderScanResults(data.devices || [], resultsEl);
            })
            .catch(function () {
                statusEl.style.display = 'none';
                resultsEl.textContent = 'Error';
            });
    });
}

function renderScanResults(devices, container) {
    if (devices.length === 0) {
        container.innerHTML = '<p class="hint">' + window.i18n.scan_none_found + '</p>';
        return;
    }

    var html = '<h3>' + window.i18n.scan_found + '</h3>';
    devices.forEach(function (d, idx) {
        html += '<div class="scan-result-row">' +
            '<input type="checkbox" data-ip="' + d.ip + '" ' + (d.already_added ? 'disabled checked' : 'checked') + '>' +
            '<strong>' + d.ip + '</strong> — ' + (d.model || d.type || '?') +
            (d.already_added ? ' (bereits hinzugefügt)' : '') +
            '</div>';
    });
    html += '<button type="button" id="btn-add-selected" class="btn btn-primary">' + window.i18n.add_selected + '</button>';
    container.innerHTML = html;

    document.getElementById('btn-add-selected').addEventListener('click', function () {
        var ips = Array.from(container.querySelectorAll('input[type=checkbox]:checked:not(:disabled)'))
            .map(function (cb) { return cb.getAttribute('data-ip'); });
        if (ips.length === 0) return;

        var token = document.querySelector('#scan-form input[name=csrf_token]').value;
        var body = new FormData();
        body.append('csrf_token', token);
        ips.forEach(function (ip) { body.append('ips[]', ip); });

        fetch('ajax/add_found.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function () { window.location.href = 'index.php'; });
    });
}

function initDeviceControl() {
    var btn = document.getElementById('btn-refresh-status');
    if (!btn) return;

    btn.addEventListener('click', function () {
        loadStatus();
    });

    loadStatus();
}

function loadStatus() {
    fetch('ajax/status.php?id=' + window.deviceId)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var liveEl = document.getElementById('live-status');
            var panel = document.getElementById('control-panel');
            liveEl.innerHTML = '<strong>' + (data.online ? window.i18n.status_online : window.i18n.status_offline) + '</strong>';
            panel.innerHTML = '';

            if (!data.online || !data.status) {
                return;
            }

            if (window.deviceGeneration >= 2) {
                renderGen2Controls(data.status, panel);
            } else {
                renderGen1Controls(data.status, panel);
            }
        });
}

function renderGen1Controls(status, panel) {
    (status.relays || []).forEach(function (relay, idx) {
        var div = document.createElement('div');
        div.className = 'control-channel';
        div.innerHTML = '<div>' + window.i18n.channel + ' ' + idx + ' (' + (relay.ison ? 'ON' : 'OFF') + ')</div>';
        div.appendChild(makeButton(window.i18n.turn_on, function () { sendControl('switch', idx, 'on'); }));
        div.appendChild(makeButton(window.i18n.turn_off, function () { sendControl('switch', idx, 'off'); }));
        panel.appendChild(div);
    });
    (status.rollers || []).forEach(function (roller, idx) {
        var div = document.createElement('div');
        div.className = 'control-channel';
        div.innerHTML = '<div>' + window.i18n.channel + ' ' + idx + ' (' + (roller.state || '') + ')</div>';
        div.appendChild(makeButton(window.i18n.open, function () { sendControl('roller', idx, 'open'); }));
        div.appendChild(makeButton(window.i18n.close, function () { sendControl('roller', idx, 'close'); }));
        div.appendChild(makeButton(window.i18n.stop, function () { sendControl('roller', idx, 'stop'); }));
        panel.appendChild(div);
    });
}

function renderGen2Controls(status, panel) {
    Object.keys(status).forEach(function (key) {
        var match = key.match(/^switch:(\d+)$/);
        if (match) {
            var idx = parseInt(match[1], 10);
            var sw = status[key];
            var div = document.createElement('div');
            div.className = 'control-channel';
            div.innerHTML = '<div>' + window.i18n.channel + ' ' + idx + ' (' + (sw.output ? 'ON' : 'OFF') + ')</div>';
            div.appendChild(makeButton(window.i18n.turn_on, function () { sendControl('switch', idx, 'on'); }));
            div.appendChild(makeButton(window.i18n.turn_off, function () { sendControl('switch', idx, 'off'); }));
            panel.appendChild(div);
        }
        var coverMatch = key.match(/^cover:(\d+)$/);
        if (coverMatch) {
            var cidx = parseInt(coverMatch[1], 10);
            var cover = status[key];
            var cdiv = document.createElement('div');
            cdiv.className = 'control-channel';
            cdiv.innerHTML = '<div>' + window.i18n.channel + ' ' + cidx + ' (' + (cover.state || '') + ')</div>';
            cdiv.appendChild(makeButton(window.i18n.open, function () { sendControl('roller', cidx, 'open'); }));
            cdiv.appendChild(makeButton(window.i18n.close, function () { sendControl('roller', cidx, 'close'); }));
            cdiv.appendChild(makeButton(window.i18n.stop, function () { sendControl('roller', cidx, 'stop'); }));
            panel.appendChild(cdiv);
        }
    });
}

function makeButton(label, onClick) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn btn-small';
    b.textContent = label;
    b.addEventListener('click', onClick);
    return b;
}

function sendControl(component, channel, action) {
    var body = new FormData();
    body.append('csrf_token', window.csrfToken);
    body.append('id', window.deviceId);
    body.append('component', component);
    body.append('channel', channel);
    body.append('action', action);

    fetch('ajax/control.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function () { setTimeout(loadStatus, 500); });
}
