document.addEventListener('DOMContentLoaded', function () {
    loadScriptList();

    document.getElementById('script-create-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var name = e.target.name.value.trim();
        if (!name) return;

        var body = new FormData();
        body.append('csrf_token', window.csrfToken);
        body.append('id', window.deviceId);
        body.append('action', 'create');
        body.append('name', name);

        fetch('ajax/scripts.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function () {
                e.target.reset();
                loadScriptList();
            });
    });
});

function loadScriptList() {
    fetch('ajax/scripts.php?id=' + window.deviceId + '&action=list')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            renderScriptList(data.scripts || []);
        });
}

function renderScriptList(scripts) {
    var list = document.getElementById('script-list');
    list.innerHTML = '';

    if (scripts.length === 0) {
        list.innerHTML = '<li class="hint">–</li>';
        return;
    }

    scripts.forEach(function (script) {
        var li = document.createElement('li');
        li.className = 'script-list-item';

        var status = document.createElement('span');
        status.className = 'badge ' + (script.running ? 'badge-online' : 'badge-unknown');
        status.textContent = script.running ? window.i18n.scripts_running : window.i18n.scripts_stopped;

        var name = document.createElement('strong');
        name.textContent = script.name || ('Script #' + script.id);

        li.appendChild(name);
        li.appendChild(document.createTextNode(' '));
        li.appendChild(status);

        var actions = document.createElement('div');
        actions.className = 'script-actions';

        actions.appendChild(makeBtn(window.i18n.edit, function () { openEditor(script); }));
        actions.appendChild(makeBtn(script.running ? window.i18n.stop : window.i18n.start, function () {
            scriptAction(script.running ? 'stop' : 'start', script.id, loadScriptList);
        }));
        actions.appendChild(makeBtn(script.enable ? window.i18n.disable : window.i18n.enable, function () {
            var body = new FormData();
            body.append('csrf_token', window.csrfToken);
            body.append('id', window.deviceId);
            body.append('action', 'enable');
            body.append('script_id', script.id);
            body.append('enable', script.enable ? '0' : '1');
            fetch('ajax/scripts.php', { method: 'POST', body: body }).then(loadScriptList);
        }));
        actions.appendChild(makeBtn(window.i18n.delete, function () {
            if (!confirm(window.i18n.confirm_delete)) return;
            scriptAction('delete', script.id, loadScriptList);
        }));

        li.appendChild(actions);
        list.appendChild(li);
    });
}

function makeBtn(label, onClick) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn btn-small';
    b.textContent = label;
    b.addEventListener('click', onClick);
    return b;
}

function scriptAction(action, scriptId, callback) {
    var body = new FormData();
    body.append('csrf_token', window.csrfToken);
    body.append('id', window.deviceId);
    body.append('action', action);
    body.append('script_id', scriptId);
    fetch('ajax/scripts.php', { method: 'POST', body: body }).then(callback);
}

function openEditor(script) {
    var editor = document.getElementById('script-editor');
    editor.innerHTML = '<p><em>' + (script.name || ('Script #' + script.id)) + '</em></p><textarea class="script-code" rows="20"></textarea><br><button type="button" class="btn btn-primary" id="btn-save-code">' + window.i18n.save + '</button> <span class="save-msg" id="code-save-msg"></span>';

    var textarea = editor.querySelector('.script-code');
    textarea.value = '';

    fetch('ajax/script_code.php?id=' + window.deviceId + '&script_id=' + script.id)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            textarea.value = data.code || '';
        });

    document.getElementById('btn-save-code').addEventListener('click', function () {
        var body = new FormData();
        body.append('csrf_token', window.csrfToken);
        body.append('id', window.deviceId);
        body.append('script_id', script.id);
        body.append('code', textarea.value);

        var msg = document.getElementById('code-save-msg');
        fetch('ajax/script_code.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                msg.textContent = data.success ? window.i18n.saved : window.i18n.save_failed;
                msg.className = 'save-msg ' + (data.success ? 'success' : 'error');
            });
    });
}
