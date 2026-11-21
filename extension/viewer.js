/**
 * The snippet viewer — read.php ported to the client.
 *
 * Same shell: meta sidebar, breadcrumb, toolbar with the language picker and
 * the two copy buttons, gutter + highlighted code. The PHP server round trip
 * becomes one storage read; the grammar lazy-loader now serves files from the
 * local vendor folder instead of jsDelivr, so highlighting is fully offline.
 */

(function () {
    'use strict';

    var store = window.CodePointStore;
    var api = window.codePoint;
    var langs = window.CodePointLangs;

    var params = new URLSearchParams(window.location.search);
    var id = parseInt(params.get('code_id'), 10);

    var block = document.getElementById('code-block');
    var select = document.getElementById('lang-select');
    var editBtn = document.getElementById('edit-code-btn');
    var deleteBtn = document.getElementById('delete-code-btn');
    var gutter = document.getElementById('code-gutter');
    var metaList = document.getElementById('meta-list');
    var aboutText = document.getElementById('about-text');
    var breadcrumbTitle = document.getElementById('breadcrumb-title');
    var pageTitle = document.getElementById('page-title');
    var pageDesc = document.getElementById('page-desc');
    var toolbarLang = document.getElementById('toolbar-lang');
    var toolbarTitle = document.getElementById('toolbar-title');

    /* ---------- theme — the site's pickTheme, unchanged ---------- */

    function pickTheme() {
        var root = document.documentElement;
        var dark = root.dataset.theme
            ? root.dataset.theme === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.getElementById('hljs-' + (dark ? 'dark' : 'light') + '-theme').media = 'all';
        document.getElementById('hljs-' + (dark ? 'light' : 'dark') + '-theme').media = 'not all';
    }
    pickTheme();

    // ui.js's handler is registered first, so it has already written
    // data-theme by the time this one runs
    var themeToggle = document.querySelector('[data-theme-toggle]');
    if (themeToggle) themeToggle.addEventListener('click', pickTheme);

    /* ---------- grammar loading — same loader, local files ---------- */

    // the bundled grammars stop at the 36 "common" languages, so the rest
    // arrive one file at a time — from the extension's own vendor folder,
    // which keeps the whole thing offline
    var GRAMMAR_URL = chrome.runtime.getURL('vendor/hljs/languages/');
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
            // no file on disk would fetch itself in a loop
            loadGrammar(lang, function () {
                if (select.value === lang && hljs.getLanguage(lang)) paint();
            });
            return;
        }
        hljs.highlightElement(block);
    }

    select.addEventListener('change', paint);

    /* ---------- rendering ---------- */

    function metaRow(key, valueNode) {
        var row = document.createElement('div');
        row.className = 'meta-row';

        var keySpan = document.createElement('span');
        keySpan.className = 'meta-key';
        keySpan.textContent = key;

        var value = document.createElement('span');
        value.className = 'meta-value';
        value.appendChild(valueNode);

        row.appendChild(keySpan);
        row.appendChild(value);
        return row;
    }

    function textValue(text) {
        var span = document.createElement('span');
        span.textContent = text;
        return span;
    }

    function renderMeta(code) {
        var charCount = store.byteLength(code.code_text);
        metaList.textContent = '';
        metaList.appendChild(metaRow('شناسه', textValue('#' + code.code_id)));

        var badge = document.createElement('span');
        badge.className = 'badge badge-lang';
        badge.textContent = code.code_lang;
        metaList.appendChild(metaRow('زبان', badge));

        metaList.appendChild(metaRow('تعداد خط', textValue(String(store.lineCount(code.code_text)))));
        metaList.appendChild(metaRow('تعداد کاراکتر', textValue(store.numberFormat(charCount))));
        metaList.appendChild(metaRow('حجم', textValue(store.sizeLabel(charCount))));
    }

    function buildLangSelect(current) {
        var currentKey = current.toLowerCase();
        select.textContent = '';

        var selected = document.createElement('option');
        selected.value = currentKey;
        selected.textContent = current;
        selected.selected = true;
        select.appendChild(selected);

        Object.keys(langs.groups).forEach(function (groupLabel) {
            var group = document.createElement('optgroup');
            group.label = groupLabel;

            langs.groups[groupLabel].forEach(function (option) {
                if (option === currentKey) return;
                var opt = document.createElement('option');
                opt.value = option;
                opt.textContent = langs.label(option);
                group.appendChild(opt);
            });

            select.appendChild(group);
        });
    }

    function renderCode(code) {
        var text = code.code_text;
        var lineTotal = store.lineCount(text);

        gutter.textContent = '';
        for (var i = 1; i <= lineTotal; i++) {
            var num = document.createElement('span');
            num.textContent = String(i);
            gutter.appendChild(num);
        }

        // textContent is the snippet byte for byte — no HTML escaping detour
        block.textContent = text;
        block.className = 'language-' + code.code_lang.toLowerCase();
        block.removeAttribute('data-highlighted');
    }

    function render(code) {
        document.title = code.code_title + ' | CodePoint';
        breadcrumbTitle.textContent = code.code_title;
        pageTitle.textContent = code.code_title;
        pageDesc.textContent = code.code_description;
        aboutText.textContent = code.code_description;
        toolbarLang.textContent = code.code_lang;
        toolbarTitle.textContent = code.code_title;

        editBtn.dataset.codeId = code.code_id;
        editBtn.dataset.codeTitle = code.code_title;
        editBtn.dataset.codeLang = code.code_lang;
        editBtn.dataset.codeDesc = code.code_description;
        deleteBtn.dataset.codeId = code.code_id;
        deleteBtn.dataset.codeTitle = code.code_title;

        renderMeta(code);
        buildLangSelect(code.code_lang);
        renderCode(code);
        paint();
    }

    /* ---------- copy buttons — the site's flash behavior ---------- */

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

    /* ---------- edit / delete ---------- */

    editBtn.addEventListener('click', function () {
        api.openEditor({
            id: editBtn.dataset.codeId,
            title: editBtn.dataset.codeTitle,
            lang: editBtn.dataset.codeLang,
            description: editBtn.dataset.codeDesc,
            text: block ? block.textContent : '',
            returnTo: 'read'
        });
    });

    deleteBtn.addEventListener('click', function () {
        api.remove(deleteBtn.dataset.codeId, deleteBtn.dataset.codeTitle);
    });

    /* ---------- boot + live sync ---------- */

    store.ensureSeeded().then(function () {
        return id > 0 ? store.get(id) : null;
    }).then(function (code) {
        // the viewer has nothing to show without a snippet, so a missing or
        // deleted id goes back to the list instead of a page full of empties
        if (!code) {
            window.location.replace('index.html');
            return;
        }
        render(code);

        // an edit made in another tab repaints this page; a
        // delete sends it back to the list, like the site's redirect
        store.subscribe(function () {
            store.get(id).then(function (fresh) {
                if (!fresh) {
                    api.toast('این کد حذف شده است', 'error');
                    window.location.replace('index.html');
                    return;
                }
                render(fresh);
            });
        });
    });
})();
