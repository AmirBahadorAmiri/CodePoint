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

// the highlight.js bundle only carries its "common" grammars (~36), so the picker
// offers these and the script at the bottom of the page fetches whatever it misses
// from the same release — cdnjs publishes no standalone grammar files
$langGroups = [
    'وب و نشانه‌گذاری' => [
        'html', 'xml', 'css', 'scss', 'less', 'stylus', 'haml', 'twig', 'handlebars',
        'django', 'php-template', 'markdown', 'asciidoc', 'latex', 'xquery', 'http',
    ],
    'اسکریپت' => [
        'javascript', 'typescript', 'python', 'ruby', 'php', 'perl', 'lua', 'r',
        'matlab', 'julia', 'scala', 'kotlin', 'groovy', 'clojure', 'haskell', 'elixir',
        'erlang', 'lisp', 'scheme', 'ocaml', 'fsharp', 'nim', 'coffeescript', 'elm',
        'powershell', 'awk', 'vim',
    ],
    'کامپایلی و سیستمی' => [
        'c', 'cpp', 'csharp', 'vbnet', 'java', 'swift', 'dart', 'go', 'rust',
        'objectivec', 'arduino', 'smali', 'd', 'fortran', 'ada', 'smalltalk',
        'x86asm', 'llvm', 'wasm', 'glsl', 'verilog', 'vhdl',
    ],
    'داده و پیکربندی' => [
        'json', 'yaml', 'ini', 'properties', 'sql', 'pgsql', 'graphql', 'protobuf',
        'thrift', 'diff', 'dns', 'accesslog', 'gherkin',
    ],
    'ترمینال و زیرساخت' => [
        'bash', 'shell', 'dos', 'makefile', 'cmake', 'apache', 'nginx', 'dockerfile',
        'gradle', 'nix',
    ],
    'سایر' => ['excel', 'plaintext'],
];

// only the entries whose grammar name is uglier than the label people expect
$langLabels = [
    'csharp' => 'C#', 'cpp' => 'C++', 'fsharp' => 'F#', 'vbnet' => 'VB.NET',
    'objectivec' => 'Objective-C', 'php-template' => 'PHP Template',
    'html' => 'HTML', 'xml' => 'XML', 'css' => 'CSS', 'scss' => 'SCSS', 'sql' => 'SQL',
    'pgsql' => 'PostgreSQL', 'json' => 'JSON', 'yaml' => 'YAML', 'ini' => 'INI',
    'php' => 'PHP', 'graphql' => 'GraphQL', 'http' => 'HTTP', 'dns' => 'DNS',
    'apache' => 'Apache', 'nginx' => 'Nginx', 'cmake' => 'CMake', 'nix' => 'Nix',
    'wasm' => 'WebAssembly', 'dockerfile' => 'Dockerfile', 'gradle' => 'Gradle',
    'x86asm' => 'x86 ASM', 'llvm' => 'LLVM', 'glsl' => 'GLSL', 'vhdl' => 'VHDL',
    'latex' => 'LaTeX', 'xquery' => 'XQuery', 'asciidoc' => 'AsciiDoc', 'haml' => 'Haml',
    'properties' => 'Java Properties', 'plaintext' => 'بدون هایلایت',
];

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
                    <select id="lang-select" class="field field-dark" dir="ltr" title="زبان کد">
                        <option value="<?= htmlspecialchars(strtolower($lang)) ?>" selected><?= htmlspecialchars($lang) ?></option>
                        <?php foreach ($langGroups as $groupLabel => $options) { ?>
                            <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                                <?php foreach ($options as $option) {
                                    if ($option === strtolower($lang)) continue; ?>
                                    <option value="<?= $option ?>"><?= htmlspecialchars($langLabels[$option] ?? $option) ?></option>
                                <?php } ?>
                            </optgroup>
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

<!-- GitHub's own syntax themes, straight from highlight.js. Both links ship in
     the markup and pickTheme() below toggles them with media="not all", which is
     the only thing that actually disables a stylesheet. -->
<link rel="stylesheet" id="hljs-light-theme" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.2/styles/github.min.css">
<link rel="stylesheet" id="hljs-dark-theme" media="not all" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.2/styles/github-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.2/highlight.min.js"></script>
<script>
    (function () {
        var block = document.getElementById('code-block');
        var select = document.getElementById('lang-select');
        var editBtn = document.getElementById('edit-code-btn');
        var deleteBtn = document.getElementById('delete-code-btn');
        var api = window.codePoint;

        function pickTheme() {
            var root = document.documentElement;
            var dark = root.dataset.theme
                ? root.dataset.theme === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.getElementById('hljs-' + (dark ? 'dark' : 'light') + '-theme').media = 'all';
            document.getElementById('hljs-' + (dark ? 'light' : 'dark') + '-theme').media = 'not all';
        }
        pickTheme();

        // header.php's handler is registered first, so it has already written
        // data-theme by the time this one runs
        var themeToggle = document.querySelector('[data-theme-toggle]');
        if (themeToggle) themeToggle.addEventListener('click', pickTheme);

        // the bundled grammars stop at the 36 "common" languages, so the rest
        // arrive one file at a time from the same release the core came from
        var GRAMMAR_URL = 'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.2/build/languages/';
        var loading = {};

        function loadGrammar(lang, done) {
            if (typeof hljs === 'undefined' || hljs.getLanguage(lang)) return false;
            // select.value can come from a stored snippet, so only plain grammar
            // names are ever allowed to become part of a request path
            if (loading[lang] || !/^[a-z0-9][a-z0-9.-]*$/i.test(lang)) return false;
            loading[lang] = true;
            var script = document.createElement('script');
            script.src = GRAMMAR_URL + lang + '.min.js';
            script.onload = script.onerror = function () {
                script.parentNode.removeChild(script);
                delete loading[lang];
                done();
            };
            document.head.appendChild(script);
            return true;
        }

        function paint() {
            if (typeof hljs === 'undefined' || !block) return;
            var lang = select.value;
            // hljs reads textContent anyway, but warns about "unescaped HTML" while
            // the spans of the previous run are still children of the element
            block.textContent = block.textContent;
            block.className = 'language-' + lang; // also drops the hljs class
            block.removeAttribute('data-highlighted');
            if (lang === 'plaintext') {
                return; // leave as plain text
            }
            if (!hljs.getLanguage(lang)) {
                // repaint only once the grammar really landed, otherwise a name with
                // no file on the CDN would fetch itself in a loop
                loadGrammar(lang, function () {
                    if (select.value === lang && hljs.getLanguage(lang)) paint();
                });
                return;
            }
            hljs.highlightElement(block);
        }

        select.addEventListener('change', paint);
        paint();

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