document.addEventListener('DOMContentLoaded', function () {
    initNavToggle();
    initTreeToggle();
    initScanForm();
    initDeviceControl();
    initReboot();
    initDashboardMetrics();
});

function initNavToggle() {
    var btn = document.getElementById('nav-toggle');
    var menu = document.getElementById('topbar-menu');
    if (!btn || !menu) return;
    btn.addEventListener('click', function () {
        menu.classList.toggle('open');
    });
    menu.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            menu.classList.remove('open');
        });
    });
}

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

/* ---- Dashboard: Live-Metriken für alle Geräte im Baum ---- */

var dashboardMetricsTimer = null;

function initDashboardMetrics() {
    var tree = document.getElementById('device-tree');
    if (!tree) return;

    loadDashboardMetrics();
    dashboardMetricsTimer = setInterval(loadDashboardMetrics, 20000);
}

function loadDashboardMetrics() {
    fetch('ajax/dashboard_metrics.php')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var metrics = data.metrics || {};
            Object.keys(metrics).forEach(function (id) {
                applyDeviceMetrics(id, metrics[id]);
            });
        })
        .catch(function () { /* letzte bekannte Werte beibehalten */ });
}

function applyDeviceMetrics(id, info) {
    var badge = document.querySelector('[data-status-badge="' + id + '"]');
    if (badge) {
        var online = !!info.online;
        badge.classList.remove('badge-online', 'badge-offline', 'badge-unknown');
        badge.classList.add(online ? 'badge-online' : 'badge-offline');
        badge.textContent = online ? window.i18n.status_online : window.i18n.status_offline;
    }

    var metricsEl = document.querySelector('[data-metrics-for="' + id + '"]');
    if (metricsEl) {
        metricsEl.innerHTML = formatMetricsInline(info.metrics || []);
    }
}

function formatMetricsInline(metrics) {
    if (!metrics || metrics.length === 0) return '';
    return metrics.map(function (m) {
        return '<span class="metric-chip" title="' + (window.metricLabels[m.key] || m.key) + '">' +
            m.icon + ' ' + m.value + m.unit + '</span>';
    }).join('');
}

/* ---- Gerätedetail: Steuerung + Live-Status ---- */

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
            var metricsPanel = document.getElementById('metrics-panel');
            var panel = document.getElementById('control-panel');

            liveEl.innerHTML = '<span class="status-dot ' + (data.online ? 'online' : 'offline') + '"></span>' +
                '<strong>' + (data.online ? window.i18n.status_online : window.i18n.status_offline) + '</strong>';

            metricsPanel.innerHTML = '';
            panel.innerHTML = '';

            if (!data.online || !data.status) {
                return;
            }

            renderMetricCards(data.metrics || [], metricsPanel);

            if (window.deviceGeneration >= 2) {
                renderGen2Controls(data.status, panel);
            } else {
                renderGen1Controls(data.status, panel);
            }
        });
}

function renderMetricCards(metrics, container) {
    if (!metrics || metrics.length === 0) return;
    metrics.forEach(function (m) {
        var card = document.createElement('div');
        card.className = 'metric-card';
        card.innerHTML = '<span class="metric-icon">' + m.icon + '</span>' +
            '<span class="metric-value">' + m.value + '<small>' + m.unit + '</small></span>' +
            '<span class="metric-label">' + (window.metricLabels[m.key] || m.key) + '</span>';
        container.appendChild(card);
    });
}

function renderGen1Controls(status, panel) {
    (status.relays || []).forEach(function (relay, idx) {
        var div = document.createElement('div');
        div.className = 'control-channel';
        div.appendChild(makeChannelHeader(window.i18n.channel + ' ' + idx));
        div.appendChild(makeToggle(!!relay.ison, function (nextOn) {
            sendControl('switch', idx, nextOn ? 'on' : 'off');
        }));
        panel.appendChild(div);
    });
    (status.rollers || []).forEach(function (roller, idx) {
        var div = document.createElement('div');
        div.className = 'control-channel';
        div.appendChild(makeChannelHeader(window.i18n.channel + ' ' + idx + ' (' + (roller.state || '') + ')'));
        var row = document.createElement('div');
        row.className = 'button-row';
        row.appendChild(makeButton(window.i18n.open, function () { sendControl('roller', idx, 'open'); }));
        row.appendChild(makeButton(window.i18n.close, function () { sendControl('roller', idx, 'close'); }));
        row.appendChild(makeButton(window.i18n.stop, function () { sendControl('roller', idx, 'stop'); }));
        div.appendChild(row);
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
            div.appendChild(makeChannelHeader(window.i18n.channel + ' ' + idx));
            div.appendChild(makeToggle(!!sw.output, function (nextOn) {
                sendControl('switch', idx, nextOn ? 'on' : 'off');
            }));
            panel.appendChild(div);
        }
        var coverMatch = key.match(/^cover:(\d+)$/);
        if (coverMatch) {
            var cidx = parseInt(coverMatch[1], 10);
            var cover = status[key];
            var cdiv = document.createElement('div');
            cdiv.className = 'control-channel';
            cdiv.appendChild(makeChannelHeader(window.i18n.channel + ' ' + cidx + ' (' + (cover.state || '') + ')'));
            var row = document.createElement('div');
            row.className = 'button-row';
            row.appendChild(makeButton(window.i18n.open, function () { sendControl('roller', cidx, 'open'); }));
            row.appendChild(makeButton(window.i18n.close, function () { sendControl('roller', cidx, 'close'); }));
            row.appendChild(makeButton(window.i18n.stop, function () { sendControl('roller', cidx, 'stop'); }));
            cdiv.appendChild(row);
            panel.appendChild(cdiv);
        }
    });
}

function makeChannelHeader(text) {
    var h = document.createElement('div');
    h.className = 'control-channel-title';
    h.textContent = text;
    return h;
}

function makeToggle(isOn, onChange) {
    var label = document.createElement('label');
    label.className = 'switch-toggle';

    var input = document.createElement('input');
    input.type = 'checkbox';
    input.checked = isOn;
    input.addEventListener('change', function () { onChange(input.checked); });

    var slider = document.createElement('span');
    slider.className = 'switch-slider';

    label.appendChild(input);
    label.appendChild(slider);
    return label;
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
