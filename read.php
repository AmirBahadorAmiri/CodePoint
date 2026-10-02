<?php
require 'tools/SQLHelper.php';

$sqlManager = new SQLHelper();

$code = null;
if (isset($_GET['code_id'])) {
    $code = $sqlManager->fetchOne(
        "SELECT * FROM `codes` WHERE code_id = " . (int)$_GET['code_id']
    );
}

// the viewer has nothing to show without a snippet, so a missing or deleted id
// goes back to the list instead of rendering a page full of empty values
if ($code === null) {
    header('Location: index.php');
    exit;
}

$codeText   = $code['code_text'];
$lang       = $code['code_lang'];
$lineCount  = substr_count($codeText, "\n") + 1;
$charCount  = strlen($codeText);
$sizeBytes  = $charCount;
$sizeLabel  = $sizeBytes >= 1024
    ? number_format($sizeBytes / 1024, 1) . ' KB'
    : $sizeBytes . ' B';

$pageTitle  = $code['code_title'];
$activePage = 'code';
require 'tools/header.php';
?>
<div class="layout">

    <!-- ================= Sidebar ================= -->
    <aside class="sidebar">
        <section class="panel sidebar-section">
            <h2 class="sidebar-title">جزئیات</h2>
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-key">شناسه</span>
                    <span class="meta-value">#<?= (int)$code['code_id'] ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">زبان</span>
                    <span class="meta-value"><span class="badge badge-lang"><?= htmlspecialchars($lang) ?></span></span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">تعداد خط</span>
                    <span class="meta-value"><?= (int)$lineCount ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">تعداد کاراکتر</span>
                    <span class="meta-value"><?= number_format($charCount) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">حجم</span>
                    <span class="meta-value"><?= $sizeLabel ?></span>
                </div>
            </div>
        </section>

        <section class="panel sidebar-section">
            <h2 class="sidebar-title">درباره این کد</h2>
            <p class="sidebar-text">
                <?= htmlspecialchars($code['code_description']) ?>
            </p>
        </section>
    </aside>

    <!-- ================= Main ================= -->
    <main class="main">
        <nav class="breadcrumb" aria-label="مسیر">
            <a href="index.php">خانه</a>
            <span class="sep">/</span>
            <span><?= htmlspecialchars($code['code_title']) ?></span>
        </nav>

        <div class="page-head">
            <div>
                <h1 class="badge-title"><?= htmlspecialchars($code['code_title']) ?></h1>
                <p><?= htmlspecialchars($code['code_description']) ?></p>
            </div>
            <div class="page-head-actions">
                <button type="button" class="btn btn-ghost btn-sm" id="edit-code-btn"
                        data-code-id="<?= (int)$code['code_id'] ?>"
                        data-code-title="<?= htmlspecialchars($code['code_title'], ENT_QUOTES) ?>"
                        data-code-lang="<?= htmlspecialchars($lang, ENT_QUOTES) ?>"
                        data-code-desc="<?= htmlspecialchars($code['code_description'], ENT_QUOTES) ?>">
                    ویرایش
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="delete-code-btn"
                        data-code-id="<?= (int)$code['code_id'] ?>"
                        data-code-title="<?= htmlspecialchars($code['code_title'], ENT_QUOTES) ?>">
                    حذف
                </button>
                <a class="btn btn-ghost btn-sm" href="index.php">بازگشت به لیست</a>
            </div>
        </div>

        <section class="code-shell">

            <div class="code-toolbar">
                <div class="code-toolbar-title">
                    <span class="badge badge-lang"><?= htmlspecialchars($lang) ?></span>
                    <span><?= htmlspecialchars($code['code_title']) ?></span>
                </div>

                <div class="code-toolbar-actions">
                    <label class="sr-only" for="lang-select">زبان کد</label>
                    <select id="lang-select" class="field field-dark" title="زبان کد">
                        <option value="<?= htmlspecialchars(strtolower($lang)) ?>" selected><?= htmlspecialchars($lang) ?></option>
                        <?php foreach (['php', 'javascript', 'typescript', 'java', 'kotlin', 'python', 'html', 'css', 'sql', 'bash', 'json', 'plaintext'] as $option) {
                            if ($option === strtolower($lang)) continue; ?>
                            <option value="<?= $option ?>"><?= $option ?></option>
                        <?php } ?>
                    </select>

                    <button type="button" class="btn btn-ghost btn-sm" id="copy-btn"
                            data-label="کپی کد">
                        کپی
                    </button>

                    <button type="button" class="btn btn-ghost btn-sm" id="copy-lines-btn"
                            data-label="کپی با شماره خط">
                        کپی با شماره خط
                    </button>
                </div>
            </div>

            <div class="code-scroll">
                <div class="code-gutter" aria-hidden="true">
                    <?php for ($i = 1; $i <= $lineCount; $i++) { ?>
                        <span><?= $i ?></span>
                    <?php } ?>
                </div>

                <pre class="code-body"><code id="code-block" class="language-<?= htmlspecialchars(strtolower($lang)) ?>"><?= htmlspecialchars($codeText) ?></code></pre>
            </div>

        </section>

    </main>

</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
    (function () {
        var block = document.getElementById('code-block');
        var select = document.getElementById('lang-select');
        var editBtn = document.getElementById('edit-code-btn');
        var deleteBtn = document.getElementById('delete-code-btn');
        var api = window.codePoint;

        function paint() {
            if (typeof hljs === 'undefined' || !block) return;
            var lang = select.value;
            block.className = 'language-' + lang;
            block.removeAttribute('data-highlighted');
            if (lang === 'plaintext' || !hljs.getLanguage(lang)) {
                block.textContent = block.textContent; // leave as plain text
                block.classList.remove('hljs');
                return;
            }
            hljs.highlightElement(block);
        }

        select.addEventListener('change', paint);
        if (typeof hljs !== 'undefined' && block) hljs.highlightElement(block);

        function flash(btn, ok) {
            btn.textContent = ok ? 'کپی شد ✓' : 'کپی ناموفق';
            btn.classList.toggle('is-copied', ok);
            btn.classList.toggle('is-failed', !ok);
            setTimeout(function () {
                btn.textContent = btn.dataset.label;
                btn.classList.remove('is-copied', 'is-failed');
            }, 1800);
        }

        function withLineNumbers(text) {
            return text.split('\n').map(function (line, i) {
                return (i + 1) + '  ' + line;
            }).join('\n');
        }

        // navigator.clipboard is undefined outside a secure context (LAN access,
        // not localhost) — the shared helper in header.php owns that fallback
        function wire(btnId, transform) {
            var btn = document.getElementById(btnId);
            if (!btn || !api) return;
            btn.addEventListener('click', function () {
                var text = block ? block.innerText.replace(/\n$/, '') : '';
                api.clipboard(transform(text), function (ok) {
                    flash(btn, ok);
                });
            });
        }

        wire('copy-btn', function (t) { return t; });
        wire('copy-lines-btn', withLineNumbers);

        if (editBtn && api) {
            editBtn.addEventListener('click', function () {
                api.openEditor({
                    id: editBtn.dataset.codeId,
                    title: editBtn.dataset.codeTitle,
                    lang: editBtn.dataset.codeLang,
                    description: editBtn.dataset.codeDesc,
                    // textContent is the snippet byte for byte, which innerText is not
                    text: block ? block.textContent : '',
                    returnTo: 'read'
                });
            });
        }

        if (deleteBtn && api) {
            deleteBtn.addEventListener('click', function () {
                api.remove(deleteBtn.dataset.codeId, deleteBtn.dataset.codeTitle);
            });
        }
    })();
</script>