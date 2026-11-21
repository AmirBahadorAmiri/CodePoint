/**
 * Shared page chrome — a port of tools/header.php's script.
 *
 * Owns everything every page shares: toasts, the clipboard helper with its
 * insecure-context fallback, the add/edit dialog, delete confirmation, the
 * theme toggle, and the JSON export / import buttons.
 *
 * The site submitted the dialog to api.php and relied on the redirect to see
 * fresh data; here a save writes chrome.storage and every listening page
 * re-renders through store.subscribe, so no navigation is needed.
 */

(function () {
    'use strict';

    var store = window.CodePointStore;

    var DIALOG_HTML =
        '<dialog class="modal" id="code-dialog" aria-labelledby="code-dialog-title">' +
        '    <form id="code-form">' +
        '        <input type="hidden" name="action" id="code-form-action" value="insert">' +
        '        <input type="hidden" name="code_id" id="code-form-id" value="">' +
        '        <input type="hidden" name="return_to" id="code-form-return" value="read">' +
        '        <div class="modal-head">' +
        '            <h2 class="modal-title" id="code-dialog-title">ثبت کد جدید</h2>' +
        '            <button type="button" class="icon-btn" data-close-code-dialog aria-label="بستن">' +
        '                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"' +
        '                     stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
        '                    <path d="M18 6 6 18M6 6l12 12"></path>' +
        '                </svg>' +
        '            </button>' +
        '        </div>' +
        '        <div class="modal-body form-grid">' +
        '            <div>' +
        '                <label class="field-label" for="code_title">عنوان</label>' +
        '                <input class="field" type="text" id="code_title" name="code_title" required' +
        '                       placeholder="مثلاً: خواندن فایل در PHP">' +
        '            </div>' +
        '            <div>' +
        '                <label class="field-label" for="code_language">زبان</label>' +
        '                <input class="field" type="text" id="code_language" name="code_language" required' +
        '                       placeholder="مثلاً: PHP" dir="ltr">' +
        '            </div>' +
        '            <div>' +
        '                <label class="field-label" for="code_description">توضیح کوتاه</label>' +
        '                <input class="field" type="text" id="code_description" name="code_description" required' +
        '                       placeholder="این کد چه کاری انجام می‌دهد؟">' +
        '            </div>' +
        '            <div>' +
        '                <label class="field-label" for="code_text">کد</label>' +
        '                <textarea class="field" id="code_text" name="code_text" rows="11" required' +
        '                          placeholder="کد را اینجا بنویسید..." dir="ltr" spellcheck="false"></textarea>' +
        '            </div>' +
        '            <div class="submit-row">' +
        '                <button class="btn btn-primary" type="submit" id="code-form-submit">ذخیره کد</button>' +
        '            </div>' +
        '        </div>' +
        '    </form>' +
        '</dialog>';

    var IMPORT_FORM_HTML =
        '<form id="backup-import-form" hidden>' +
        '    <input type="file" id="backup-import-file" accept=".json,application/json" hidden>' +
        '</form>';

    /* ---------- ensure shared DOM ---------- */

    var stack = document.getElementById('toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'toast-stack';
        stack.id = 'toast-stack';
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('aria-atomic', 'false');
        document.body.appendChild(stack);
    }

    var dialog = document.getElementById('code-dialog');
    if (!dialog) {
        var dialogHost = document.createElement('div');
        dialogHost.innerHTML = DIALOG_HTML + IMPORT_FORM_HTML;
        document.body.appendChild(dialogHost);
        dialog = dialogHost.querySelector('#code-dialog');
    }

    var form = document.getElementById('code-form');
    var actionField = document.getElementById('code-form-action');
    var idField = document.getElementById('code-form-id');
    var returnField = document.getElementById('code-form-return');
    var titleEl = document.getElementById('code-dialog-title');
    var submitEl = document.getElementById('code-form-submit');

    var fields = {
        title: document.getElementById('code_title'),
        language: document.getElementById('code_language'),
        description: document.getElementById('code_description'),
        text: document.getElementById('code_text')
    };

    /* ---------- toast ---------- */

    function toast(message, kind) {
        var el = document.createElement('div');
        el.className = 'toast ' + (kind === 'error' ? 'is-error' : 'is-ok');
        el.textContent = message;
        stack.appendChild(el);
        // let the entry animation finish before the exit one, then drop the node
        setTimeout(function () {
            el.className += ' is-out';
            setTimeout(function () {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 250);
        }, 2400);
    }

    /* ---------- clipboard — the site's exact pair of paths ---------- */

    // navigator.clipboard is undefined outside a secure context; extension
    // pages are secure, but the fallback stays because the helper is shared
    function fallbackCopy(text) {
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.top = '-1000px';
        document.body.appendChild(area);
        area.select();
        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (err) {
            copied = false;
        }
        document.body.removeChild(area);
        return copied;
    }

    function clipboard(text, done) {
        function report(ok) {
            if (done) done(ok);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                report(true);
            }, function () {
                report(fallbackCopy(text));
            });
        } else {
            report(fallbackCopy(text));
        }
    }

    function copy(text) {
        clipboard(text, function (ok) {
            toast(ok ? 'کد کپی شد ✓' : 'کپی ناموفق', ok ? 'ok' : 'error');
        });
    }

    /* ---------- editor dialog ---------- */

    // no payload means "new snippet", which is what the navbar button passes;
    // a payload without an id but with text (a captured selection) prefills
    // the insert form instead of resetting it
    function openEditor(data) {
        if (data && data.id) {
            fields.title.value = data.title || '';
            fields.language.value = data.lang || '';
            fields.description.value = data.description || '';
            fields.text.value = data.text || '';
            actionField.value = 'update';
            idField.value = data.id;
            returnField.value = data.returnTo === 'index' ? 'index' : 'read';
            titleEl.textContent = 'ویرایش کد';
            submitEl.textContent = 'ذخیره تغییرات';
        } else if (data && (data.text || data.title || data.description || data.lang)) {
            form.reset();
            fields.title.value = data.title || '';
            fields.language.value = data.lang || '';
            fields.description.value = data.description || '';
            fields.text.value = data.text || '';
            actionField.value = 'insert';
            idField.value = '';
            returnField.value = 'read';
            titleEl.textContent = 'ثبت کد جدید';
            submitEl.textContent = 'ذخیره کد';
        } else {
            form.reset();
            actionField.value = 'insert';
            idField.value = '';
            returnField.value = 'read';
            titleEl.textContent = 'ثبت کد جدید';
            submitEl.textContent = 'ذخیره کد';
        }

        dialog.showModal();
        fields.title.focus();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        // api.php's guard, one to one: text is not trimmed, the rest is
        var payload = {
            title: fields.title.value.trim(),
            language: fields.language.value.trim(),
            description: fields.description.value.trim(),
            text: fields.text.value
        };
        if (payload.title === '' || payload.language === '' || payload.description === '' || payload.text.trim() === '') {
            toast('همه فیلدها لازم‌اند.', 'error');
            return;
        }

        var action = actionField.value;
        var saving = action === 'update'
            ? store.update(idField.value, payload)
            : store.insert(payload);

        saving.then(function () {
            dialog.close();
            toast(action === 'update' ? 'تغییرات ذخیره شد ✓' : 'کد ذخیره شد ✓', 'ok');
        }, function () {
            toast('ذخیره ناموفق بود', 'error');
        });
    });

    /* ---------- delete — the site's confirm wording ---------- */

    function remove(id, title) {
        if (!id) return;
        var label = title ? '«' + title + '»' : 'این کد';
        if (!window.confirm('کد ' + label + ' برای همیشه حذف شود؟')) return;
        store.remove(id).then(function (removed) {
            toast(removed ? 'کد حذف شد' : 'کدی حذف نشد', removed ? 'ok' : 'error');
        }, function () {
            toast('حذف ناموفق بود', 'error');
        });
    }

    /* ---------- export — backup.php as a download ---------- */

    function saveJsonFile(json, filename) {
        // every CodePoint surface is a real tab now, so a plain anchor download
        // with a blob URL is reliable everywhere — no downloads API, and the
        // page stays open long enough for the blob to survive
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(new Blob([json], { type: 'application/json' }));
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            // keep the URL alive well past the download start, then let it go
            setTimeout(function () { URL.revokeObjectURL(url); resolve(true); }, 10000);
        });
    }

    function exportJson() {
        store.exportObject().then(function (backup) {
            // JSON.stringify skips escaping unicode and slashes, matching the
            // site's JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES output;
            // four spaces stand in for JSON_PRETTY_PRINT
            var json = JSON.stringify(backup, null, 4);
            saveJsonFile(json, store.exportFilename()).then(function (ok) {
                toast(ok ? 'خروجی JSON گرفته شد ✓' : 'خروجی گرفتن ناموفق بود', ok ? 'ok' : 'error');
            });
        }, function () {
            toast('خروجی گرفتن ناموفق بود', 'error');
        });
    }

    /* ---------- import — the site's flow and wording ---------- */

    var importButton = null;
    var importForm = document.getElementById('backup-import-form');
    var importInput = document.getElementById('backup-import-file');

    var importReasons = {
        nofile: 'فایلی انتخاب نشد.',
        upload: 'خواندن فایل ناموفق بود.',
        toolarge: 'فایل از حد مجاز بزرگ‌تر است.',
        empty: 'فایل خالی است.',
        badjson: 'فایل، JSON معتبر نیست.',
        badschema: 'ساختار فایل با بکاپ CodePoint نمی‌خواند.',
        norows: 'هیچ کد قابل‌استفاده‌ای در فایل نبود.',
        database: 'ذخیره در حافظه ناموفق بود؛ چیزی تغییر نکرد.'
    };

    function reportImport(result) {
        var parts = [];
        if (result.inserted) parts.push(result.inserted + ' کد جدید اضافه شد');
        if (result.updated) parts.push(result.updated + ' کد به‌روزرسانی شد');
        if (result.skipped) parts.push(result.skipped + ' ردیف نامعتبر رد شد');
        toast(parts.length ? parts.join('، ') + '.' : 'فایل خالی بود.', parts.length ? 'ok' : 'error');
    }

    if (importForm && importInput) {
        importInput.addEventListener('change', function () {
            if (!importInput.files || !importInput.files.length) return;

            var file = importInput.files[0];
            var ok = window.confirm(
                'فایل «' + file.name + '» وارد شود؟\n\n'
                + 'کدهایی که در فایل هستند به‌روزرسانی می‌شوند و کدهای جدید اضافه.\n'
                + 'هیچ کدی پاک نمی‌شود.'
            );

            if (!ok) {
                // a cancelled picker can be reused only after its value is cleared
                importInput.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function () {
                var data = null;
                try {
                    data = JSON.parse(String(reader.result));
                } catch (err) {
                    data = null;
                }

                if (data === null) {
                    toast(importReasons.badjson, 'error');
                    importInput.value = '';
                    return;
                }

                store.importBackup(data).then(function (result) {
                    reportImport(result);
                    importInput.value = '';
                }, function (err) {
                    toast(importReasons[err && err.reason] || 'ورود فایل ناموفق بود.', 'error');
                    importInput.value = '';
                });
            };
            reader.onerror = function () {
                toast(importReasons.upload, 'error');
                importInput.value = '';
            };
            reader.readAsText(file, 'utf-8');
        });
    }

    document.querySelectorAll('[data-open-import]').forEach(function (btn) {
        importButton = btn;
        btn.addEventListener('click', function () {
            if (importInput) importInput.click();
        });
    });

    document.querySelectorAll('[data-export-json]').forEach(function (btn) {
        btn.addEventListener('click', exportJson);
    });

    /* ---------- wiring ---------- */

    document.querySelectorAll('[data-open-code-dialog]').forEach(function (btn) {
        btn.addEventListener('click', function () { openEditor(); });
    });

    document.querySelectorAll('[data-close-code-dialog]').forEach(function (btn) {
        btn.addEventListener('click', function () { dialog.close(); });
    });

    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) dialog.close();
    });

    /* one click flips the theme and remembers it. while nothing is stored the
       operating system decides both the current theme and what a flip means */
    document.querySelectorAll('[data-theme-toggle]').forEach(function (themeToggle) {
        themeToggle.addEventListener('click', function () {
            var root = document.documentElement;
            var isDark = root.dataset.theme
                ? root.dataset.theme === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            var next = isDark ? 'light' : 'dark';
            root.dataset.theme = next;
            try {
                localStorage.setItem('codepoint-theme', next);
            } catch (err) {
                /* nothing to remember it with; the theme still holds for this page */
            }
        });
    });

    /* ---------- first run ---------- */

    // idempotent; the service worker also seeds on install, this is the
    // safety net for the page opened before that ever ran
    store.ensureSeeded().catch(function () { /* storage hiccup: next load retries */ });

    /* ---------- shared API — the same surface header.php published ---------- */

    window.codePoint = {
        openEditor: openEditor,
        remove: remove,
        copy: copy,
        toast: toast,
        clipboard: clipboard,
        exportJson: exportJson
    };
})();
