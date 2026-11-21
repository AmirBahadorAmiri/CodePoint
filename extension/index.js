/**
 * The full-page snippet list — index.php ported to the client.
 *
 * Same layout, same classes, same empty state, same counts. The server-side
 * POST search becomes an instant client-side filter, and the language filter
 * keeps its ?lang= URL state like the site. chrome.storage change events
 * stand in for the site's form-POST reloads.
 */

(function () {
    'use strict';

    var store = window.CodePointStore;
    var api = window.codePoint;

    var params = new URLSearchParams(window.location.search);
    var state = {
        search: '',
        searchType: 'title',
        lang: params.get('lang') || ''
    };

    var filterList = document.getElementById('filter-list');
    var pageHead = document.getElementById('page-head');
    var codeArea = document.getElementById('code-area');
    var searchInput = document.getElementById('search-input');
    var clearBtn = document.getElementById('clear-btn');
    var searchForm = document.getElementById('search-form');
    var searchType = document.getElementById('search-type');

    /* ---------- filtering ---------- */

    function matches(snippet) {
        var term = state.search.trim().toLowerCase();
        if (term !== '') {
            if (state.searchType === 'id') {
                if (String(snippet.code_id) !== state.search.trim()) return false;
            } else if (state.searchType === 'language') {
                if (snippet.code_lang.toLowerCase().indexOf(term) === -1) return false;
            } else if (snippet.code_title.toLowerCase().indexOf(term) === -1) {
                return false;
            }
        }
        if (state.lang !== '' && snippet.code_lang !== state.lang) return false;
        return true;
    }

    /* ---------- sidebar language filter ---------- */

    function renderFilters(languages, total) {
        filterList.textContent = '';

        var baseParams = [];
        if (state.search !== '') {
            baseParams.push('search_title=' + encodeURIComponent(state.search));
            baseParams.push('searchType=' + encodeURIComponent(state.searchType));
        }

        filterList.appendChild(filterItem('همه', total, state.lang === '', buildUrl(baseParams)));

        languages.forEach(function (row) {
            var params = baseParams.slice();
            params.push('lang=' + encodeURIComponent(row.lang));
            filterList.appendChild(filterItem(row.lang, row.total, state.lang === row.lang, buildUrl(params), true));
        });
    }

    function buildUrl(extra) {
        var qs = extra.join('&');
        return 'index.html' + (qs ? '?' + qs : '');
    }

    function filterItem(label, count, isActive, url, ltrLabel) {
        var a = document.createElement('a');
        a.className = 'filter-item' + (isActive ? ' is-active' : '');
        a.href = url;

        var text = document.createElement('span');
        if (ltrLabel) text.dir = 'ltr';
        text.textContent = label;

        var badge = document.createElement('span');
        badge.className = 'filter-count';
        badge.textContent = String(count);

        a.appendChild(text);
        a.appendChild(badge);

        // the URL is kept for middle-click / bookmarking; a normal click stays
        // in-page so the search state survives, exactly like the site's links
        // only pretended to navigate (GET params) — here it really just filters
        a.addEventListener('click', function (event) {
            event.preventDefault();
            state.lang = ltrLabel ? label : '';
            history.replaceState(null, '', url);
            render();
        });

        return a;
    }

    /* ---------- page head ---------- */

    function renderPageHead(filtered, languages, total) {
        pageHead.textContent = '';

        var box = document.createElement('div');
        var h1 = document.createElement('h1');
        h1.textContent = state.lang !== '' ? 'کدهای ' + state.lang : 'همه کدها';
        var p = document.createElement('p');
        p.textContent = filtered.length + ' کد'
            + (state.search !== '' ? ' برای «' + state.search + '»' : '')
            + ' · ' + total + ' کد در ' + languages.length + ' زبان';
        box.appendChild(h1);
        box.appendChild(p);
        pageHead.appendChild(box);
    }

    /* ---------- cards ---------- */

    function cardMeta(text) {
        return store.numberFormat(store.byteLength(text)) + ' کاراکتر';
    }

    function renderCards(snippets) {
        codeArea.textContent = '';

        if (!snippets.length) {
            codeArea.appendChild(emptyState());
            return;
        }

        var list = document.createElement('div');
        list.className = 'code-list';

        snippets.forEach(function (snippet) {
            list.appendChild(card(snippet));
        });

        codeArea.appendChild(list);
    }

    function card(snippet) {
        var article = document.createElement('article');
        article.className = 'code-card';
        article.dataset.codeId = snippet.code_id;
        article.dataset.codeTitle = snippet.code_title;
        article.dataset.codeLang = snippet.code_lang;
        article.dataset.codeDesc = snippet.code_description;
        article.dataset.codeText = snippet.code_text;

        var link = document.createElement('a');
        link.className = 'code-card-link';
        link.href = 'viewer.html?code_id=' + snippet.code_id;

        var head = document.createElement('div');
        head.className = 'code-card-head';

        var title = document.createElement('h2');
        title.className = 'code-card-title';
        title.textContent = snippet.code_title;

        var badge = document.createElement('span');
        badge.className = 'badge badge-lang';
        badge.textContent = snippet.code_lang;

        head.appendChild(title);
        head.appendChild(badge);

        var desc = document.createElement('p');
        desc.className = 'code-card-desc';
        desc.textContent = snippet.code_description;

        link.appendChild(head);
        link.appendChild(desc);

        var meta = document.createElement('div');
        meta.className = 'code-card-meta';

        var id = document.createElement('span');
        id.textContent = '#' + snippet.code_id;

        var chars = document.createElement('span');
        chars.textContent = cardMeta(snippet.code_text);

        var lines = document.createElement('span');
        lines.textContent = store.lineCount(snippet.code_text) + ' خط';

        var more = document.createElement('button');
        more.type = 'button';
        more.className = 'icon-btn code-card-more';
        more.dataset.codeMenu = '';
        more.setAttribute('aria-haspopup', 'menu');
        more.setAttribute('aria-label', 'گزینه‌های این کد');
        more.innerHTML =
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
            '    <circle cx="12" cy="5" r="1.7"></circle>' +
            '    <circle cx="12" cy="12" r="1.7"></circle>' +
            '    <circle cx="12" cy="19" r="1.7"></circle>' +
            '</svg>';

        meta.appendChild(id);
        meta.appendChild(chars);
        meta.appendChild(lines);
        meta.appendChild(more);

        article.appendChild(link);
        article.appendChild(meta);
        return article;
    }

    function emptyState() {
        var section = document.createElement('section');
        section.className = 'panel empty-state';
        section.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"' +
            '     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '    <rect x="8" y="2" width="8" height="4" rx="1"></rect>' +
            '    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>' +
            '</svg>' +
            '<p class="empty-state-title">کدی پیدا نشد</p>' +
            '<p class="empty-state-text">فیلترها را تغییر بده یا از فرم «ثبت کد جدید» اولین کدت را اضافه کن.</p>';
        return section;
    }

    /* ---------- render ---------- */

    function render(data) {
        data = data || null;
        var ready = data
            ? Promise.resolve(data)
            : store.loadAll();

        ready.then(function (snippets) {
            store.loadLanguages().then(function (languages) {
                var total = languages.reduce(function (sum, row) { return sum + row.total; }, 0);
                var visible = snippets.filter(matches);

                renderFilters(languages, total);
                renderPageHead(visible, languages, total);
                renderCards(visible);
                clearBtn.hidden = state.search === '' && state.lang === '';
            });
        });
    }

    /* ---------- search wiring ---------- */

    // the site searched on form submit; here the filter is instant and the
    // submit button just keeps its place
    var searchTimer = null;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            state.search = searchInput.value;
            render();
        }, 120);
    });

    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        state.search = searchInput.value;
        render();
    });

    searchType.addEventListener('change', function () {
        var checked = searchType.querySelector('input:checked');
        if (checked) state.searchType = checked.value;
        render();
    });

    clearBtn.addEventListener('click', function () {
        state.search = '';
        state.lang = '';
        searchInput.value = '';
        history.replaceState(null, '', 'index.html');
        render();
    });

    // restore the segmented control to whatever the URL/state implies
    (function restoreSearchType() {
        var radio = searchType.querySelector('input[value="' + state.searchType + '"]');
        if (radio) radio.checked = true;
    })();

    /* ---------- captured selection from the context menu ---------- */

    store.takeCapture().then(function (capture) {
        if (!capture || !capture.text) return;
        api.openEditor({ text: capture.text });
        api.toast('کد انتخاب‌شده به فرم منتقل شد — عنوان و زبان را کامل کن');
    });

    /* ---------- live re-render when the storage changes ---------- */

    store.subscribe(function () {
        render();
    });

    render();
})();
