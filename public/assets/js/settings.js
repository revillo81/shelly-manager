document.addEventListener('DOMContentLoaded', function () {
    loadSettings();
});

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

function renderSections(sections) {
    var container = document.getElementById('settings-sections');
    container.innerHTML = '';

    sections.forEach(function (section) {
        var card = document.createElement('section');
        card.className = 'card settings-section';

        var title = document.createElement('h2');
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

        container.appendChild(card);
    });
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
