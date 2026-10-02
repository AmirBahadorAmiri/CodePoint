<?php
/**
 * Shared top navbar for every page.
 *
 * Expects (optional) from the caller:
 *   $pageTitle string  -> rendered as <title>
 *   $activePage string  -> 'home' (index.php) or 'code' (read.php)
 */

$pageTitle = $pageTitle ?? 'CodePoint';
$activePage = $activePage ?? 'home';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | CodePoint</title>

    <link rel="shortcut icon" href="images/codepoint.png" type="image/x-icon"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="tools/style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar-inner">

        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="8" y="2" width="8" height="4" rx="1"></rect>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <path d="M9 12h6M9 16h4"></path>
                </svg>
            </span>
            <span class="brand-text">CodePoint</span>
        </a>

        <nav class="nav-links" aria-label="ناوبری اصلی">
            <a class="nav-link <?= $activePage === 'home' ? 'is-active' : '' ?>" href="index.php">خانه</a>
            <a class="nav-link <?= $activePage === 'code' ? 'is-active' : '' ?>" href="index.php">کدها</a>
        </nav>

        <div class="navbar-actions">
            <button type="button" class="btn btn-primary btn-sm" data-open-code-dialog>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"></path>
                </svg>
                ثبت کد جدید
            </button>
        </div>

    </div>
</header>

<dialog class="modal" id="code-dialog" aria-labelledby="code-dialog-title">
    <form action="api.php" method="post" id="code-form">
        <!-- which operation api.php should run; openEditor() rewrites these -->
        <input type="hidden" name="action" id="code-form-action" value="insert">
        <input type="hidden" name="code_id" id="code-form-id" value="">
        <input type="hidden" name="return_to" id="code-form-return" value="read">

        <div class="modal-head">
            <h2 class="modal-title" id="code-dialog-title">ثبت کد جدید</h2>
            <button type="button" class="icon-btn" data-close-code-dialog aria-label="بستن">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="modal-body form-grid">
            <div>
                <label class="field-label" for="code_title">عنوان</label>
                <input class="field" type="text" id="code_title" name="code_title" required
                       placeholder="مثلاً: خواندن فایل در PHP">
            </div>
            <div>
                <label class="field-label" for="code_language">زبان</label>
                <input class="field" type="text" id="code_language" name="code_language" required
                       placeholder="مثلاً: PHP" dir="ltr">
            </div>
            <div>
                <label class="field-label" for="code_description">توضیح کوتاه</label>
                <input class="field" type="text" id="code_description" name="code_description" required
                       placeholder="این کد چه کاری انجام می‌دهد؟">
            </div>
            <div>
                <label class="field-label" for="code_text">کد</label>
                <textarea class="field" id="code_text" name="code_text" rows="11" required
                          placeholder="کد را اینجا بنویسید..." dir="ltr" spellcheck="false"></textarea>
            </div>
            <div class="submit-row">
                <button class="btn btn-primary" type="submit" id="code-form-submit">ذخیره کد</button>
            </div>
        </div>
    </form>
</dialog>

<!-- delete is a POST too: a GET would let a stray link wipe a snippet -->
<form action="api.php" method="post" id="code-delete-form" hidden>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="code_id" id="code-delete-id" value="">
</form>

<div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="false"></div>

<script>
    (function () {
        var dialog = document.getElementById('code-dialog');
        var form = document.getElementById('code-form');
        var stack = document.getElementById('toast-stack');

        var actionField = document.getElementById('code-form-action');
        var idField = document.getElementById('code-form-id');
        var returnField = document.getElementById('code-form-return');
        var titleEl = document.getElementById('code-dialog-title');
        var submitEl = document.getElementById('code-form-submit');
        var deleteId = document.getElementById('code-delete-id');
        var deleteForm = document.getElementById('code-delete-form');

        var fields = {
            title: document.getElementById('code_title'),
            language: document.getElementById('code_language'),
            description: document.getElementById('code_description'),
            text: document.getElementById('code_text')
        };

        /* ---------- shared API, used by the card context menu and the viewer ---------- */

        function toast(message, kind) {
            if (!stack) return;
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

        // navigator.clipboard is undefined outside a secure context, which is exactly
        // what you get when this site is opened over the LAN (http://192.168.x.x/...)
        // instead of localhost. execCommand is deprecated but the only option there.
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

        // low level: hands the caller the result so it can flash its own button
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

        // no payload means "new snippet", which is what the navbar button passes
        function openEditor(data) {
            if (!dialog) return;

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

        function remove(id, title) {
            if (!id) return;
            var label = title ? '«' + title + '»' : 'این کد';
            if (!window.confirm('کد ' + label + ' برای همیشه حذف شود؟')) return;
            deleteId.value = id;
            deleteForm.submit();
        }

        function copy(text) {
            clipboard(text, function (ok) {
                toast(ok ? 'کد کپی شد ✓' : 'کپی ناموفق', ok ? 'ok' : 'error');
            });
        }

        window.codePoint = {
            openEditor: openEditor,
            remove: remove,
            copy: copy,
            toast: toast,
            clipboard: clipboard
        };

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
    })();
</script>