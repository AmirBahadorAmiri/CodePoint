/**
 * CodePoint — data layer on chrome.storage.local.
 *
 * Replaces the whole server side of the website:
 *   tools/SQLHelper.php  -> chrome.storage.local instead of MySQL
 *   api.php              -> insert / update / remove / import
 *   backup.php           -> exportObject()
 *   tools/import.php     -> importBackup() with the same merge semantics
 *
 * The JSON backup keeps the exact website schema (codepoint-backup / snippets /
 * code_id, code_title, code_text, code_lang, code_description), so a backup
 * made by the site imports into the extension and the other way around.
 *
 * Works as a classic script in extension pages and, via the self assignment at
 * the bottom, inside the service worker as well.
 */

var CodePointStore = (function () {
    'use strict';

    var SNIPPETS_KEY = 'codepoint:snippets';
    var SEEDED_KEY   = 'codepoint:seeded';
    var CAPTURE_KEY  = 'codepoint:capture';

    /* ---------- helpers ---------- */

    // PHP's strlen counts bytes, and the site shows that number as "کاراکتر".
    // TextEncoder reproduces the byte count so the extension reads the same.
    function byteLength(text) {
        return new TextEncoder().encode(text).length;
    }

    function numberFormat(value) {
        return value.toLocaleString('en-US');
    }

    function sizeLabel(bytes) {
        return bytes >= 1024
            ? (bytes / 1024).toFixed(1) + ' KB'
            : bytes + ' B';
    }

    function lineCount(text) {
        return text.split('\n').length;
    }

    function compareByIdDesc(a, b) { return b.code_id - a.code_id; }
    function compareByIdAsc(a, b)  { return a.code_id - b.code_id; }

    /* ---------- validation — a port of api.php's guard and import.php's normalize ---------- */

    function normalizeRow(row) {
        if (!row || typeof row !== 'object') return null;

        var title       = typeof row.code_title       === 'string' ? row.code_title       : '';
        var text        = typeof row.code_text        === 'string' ? row.code_text        : '';
        var language    = typeof row.code_lang        === 'string' ? row.code_lang        : '';
        var description = typeof row.code_description === 'string' ? row.code_description : '';

        // code_text itself is NOT trimmed — leading indentation must survive
        if (title.trim() === '' || language.trim() === '' || description.trim() === '' || text.trim() === '') {
            return null;
        }

        // only a positive integer id is trusted; anything else becomes 0, which
        // means "insert fresh" rather than "overwrite whichever row has this number"
        var id = parseInt(row.code_id, 10);
        id = Number.isFinite(id) && id > 0 ? id : 0;

        return {
            id: id,
            title: title,
            text: text,
            language: language,
            description: description
        };
    }

    function toSnippet(normalized, id) {
        return {
            code_id:          id !== undefined ? id : normalized.id,
            code_title:       normalized.title,
            code_text:        normalized.text,
            code_lang:        normalized.language,
            code_description: normalized.description
        };
    }

    /* ---------- storage primitives ---------- */

    function storageGet(keys) {
        return new Promise(function (resolve, reject) {
            chrome.storage.local.get(keys, function (result) {
                if (chrome.runtime.lastError) reject(new Error(chrome.runtime.lastError.message));
                else resolve(result);
            });
        });
    }

    function storageSet(data) {
        return new Promise(function (resolve, reject) {
            chrome.storage.local.set(data, function () {
                if (chrome.runtime.lastError) reject(new Error(chrome.runtime.lastError.message));
                else resolve();
            });
        });
    }

    async function loadAllRaw() {
        var result = await storageGet(SNIPPETS_KEY);
        var rows = result[SNIPPETS_KEY];
        return Array.isArray(rows) ? rows : [];
    }

    /* ---------- public read API ---------- */

    /** Every snippet, newest first — the order index.php renders. */
    async function loadAll() {
        var rows = await loadAllRaw();
        return rows.slice().sort(compareByIdDesc);
    }

    async function get(id) {
        var rows = await loadAllRaw();
        var wanted = parseInt(id, 10);
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].code_id === wanted) return rows[i];
        }
        return null;
    }

    /** Language list with counts — the sidebar filter. */
    async function loadLanguages() {
        var rows = await loadAllRaw();
        var counts = {};
        var order = [];
        for (var i = 0; i < rows.length; i++) {
            var lang = rows[i].code_lang;
            if (!(lang in counts)) { counts[lang] = 0; order.push(lang); }
            counts[lang]++;
        }
        // ORDER BY total DESC, code_lang — the site's query
        order.sort(function (a, b) {
            return counts[b] - counts[a] || a.localeCompare(b);
        });
        return order.map(function (lang) { return { lang: lang, total: counts[lang] }; });
    }

    /* ---------- writes ---------- */

    function nextId(rows) {
        var max = 0;
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].code_id > max) max = rows[i].code_id;
        }
        return max + 1;
    }

    /**
     * api.php's insert branch: one non-empty guard, then the row lands.
     * Throws {reason: 'empty'} on the site's silent redirect-back condition.
     */
    async function insert(data) {
        var normalized = normalizeRow({
            code_title: data.title,
            code_text: data.text,
            code_lang: data.language,
            code_description: data.description
        });
        if (!normalized) {
            var err = new Error('validation');
            err.reason = 'empty';
            throw err;
        }

        var rows = await loadAllRaw();
        var snippet = toSnippet(normalized, nextId(rows));
        rows.push(snippet);
        await storageSet(snapshot(SNIPPETS_KEY, rows));
        return snippet.code_id;
    }

    async function update(id, data) {
        var normalized = normalizeRow({
            code_title: data.title,
            code_text: data.text,
            code_lang: data.language,
            code_description: data.description
        });
        if (!normalized) {
            var err = new Error('validation');
            err.reason = 'empty';
            throw err;
        }

        var rows = await loadAllRaw();
        var wanted = parseInt(id, 10);
        var found = false;
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].code_id === wanted) {
                rows[i] = toSnippet(normalized, wanted);
                found = true;
                break;
            }
        }
        if (!found) {
            var err = new Error('missing');
            err.reason = 'missing';
            throw err;
        }
        await storageSet(snapshot(SNIPPETS_KEY, rows));
        return wanted;
    }

    /** api.php's delete branch: cast to int, delete when positive. */
    async function remove(id) {
        var wanted = parseInt(id, 10);
        if (!(wanted > 0)) return false;

        var rows = await loadAllRaw();
        var kept = rows.filter(function (row) { return row.code_id !== wanted; });
        if (kept.length === rows.length) return false;

        await storageSet(snapshot(SNIPPETS_KEY, kept));
        return true;
    }

    /* ---------- export — a port of backup.php ---------- */

    function exportObject() {
        // the file lists snippets in ascending id order, like the site's query
        return loadAllRaw().then(function (rows) {
            rows.sort(compareByIdAsc);
            return {
                format:   'codepoint-backup',
                version:  1,
                exported: new Date().toISOString(),
                count:    rows.length,
                snippets: rows.map(function (row) {
                    return {
                        code_id:          row.code_id,
                        code_title:       row.code_title,
                        code_text:        row.code_text,
                        code_lang:        row.code_lang,
                        code_description: row.code_description
                    };
                })
            };
        });
    }

    /** php date('Y-m-d_His') — the backup filename stamp. */
    function exportStamp() {
        var now = new Date();
        function pad(n) { return String(n).padStart(2, '0'); }
        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
            + '_' + pad(now.getHours()) + pad(now.getMinutes()) + pad(now.getSeconds());
    }

    function exportFilename() {
        return 'codepoint-backup-' + exportStamp() + '.json';
    }

    /* ---------- import — a port of tools/import.php ---------- */

    /**
     * Merge semantics, unchanged from the site: an id that exists updates in
     * place, a new one inserts, nothing is ever deleted. The whole batch is
     * validated first and written with ONE storage.set, which is the
     * chrome.storage equivalent of import.php's single transaction — a
     * half-applied import is still impossible.
     *
     * Resolves {inserted, updated, skipped}; rejects with .reason using the
     * site's reason codes so the toast wording maps one to one.
     */
    async function importBackup(data) {
        if (!data || typeof data !== 'object' || !Array.isArray(data.snippets)) {
            return importFailure('badschema');
        }

        var rows = [];
        for (var i = 0; i < data.snippets.length; i++) {
            var row = normalizeRow(data.snippets[i]);
            if (row !== null) rows.push(row);
        }

        if (!rows.length) return importFailure('norows');

        // rows the file carried but that were unusable are counted, never dropped silently
        var skipped = data.snippets.length - rows.length;

        var existing = await loadAllRaw();
        var byId = {};
        for (var j = 0; j < existing.length; j++) byId[existing[j].code_id] = existing[j];

        var inserted = 0;
        var updated  = 0;

        for (var k = 0; k < rows.length; k++) {
            var row = rows[k];
            if (row.id > 0 && byId[row.id]) {
                byId[row.id] = toSnippet(row, row.id); // update in place
                updated++;
            } else {
                var id = row.id > 0 ? row.id : nextId(existing);
                byId[id] = toSnippet(row, id);
                existing.push(byId[id]);
                inserted++;
            }
        }

        try {
            var merged = Object.keys(byId).map(function (key) { return byId[key]; });
            await storageSet(snapshot(SNIPPETS_KEY, merged));
        } catch (err) {
            return importFailure('database');
        }

        return { inserted: inserted, updated: updated, skipped: skipped };
    }

    function importFailure(reason) {
        var err = new Error('import failed: ' + reason);
        err.reason = reason;
        return Promise.reject(err);
    }

    /* ---------- selection capture (extension-only feature) ---------- */

    /**
     * The context menu hands over the selected code; the first page that asks
     * for it gets it and the key is cleared, so the editor never fills twice.
     */
    async function saveCapture(payload) {
        await storageSet(snapshot(CAPTURE_KEY, payload));
    }

    async function takeCapture() {
        var result = await storageGet(CAPTURE_KEY);
        var payload = result[CAPTURE_KEY];
        if (!payload) return null;
        await storageSet(snapshot(CAPTURE_KEY, null));
        return payload;
    }

    /* ---------- first run seed — the sample block of database.sql ---------- */

    var SAMPLES = [
        {
            code_id: 1,
            code_title: 'خوش‌آمدید',
            code_text: '<?php\n\n// اینجا کدت را بنویس\n$name = \'Amir\';\necho "سلام {$name}";',
            code_lang: 'PHP',
            code_description: 'یک قطعه‌کد نمونه برای تست اولیه.'
        },
        {
            code_id: 2,
            code_title: 'خواندن فایل خط‌به‌خط',
            code_text: '<?php\n\n$file = new SplFileObject(\'data.txt\');\nforeach ($file as $lineNumber => $line) {\n    echo $lineNumber . \': \' . $line;\n}',
            code_lang: 'PHP',
            code_description: 'خواندن فایل با SplFileObject و شماره‌گذاری خطوط.'
        },
        {
            code_id: 3,
            code_title: 'Hello World',
            code_text: 'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello World!");\n    }\n}',
            code_lang: 'Java',
            code_description: 'ساده‌ترین برنامه جاوا.'
        }
    ];

    /**
     * Seeds the sample snippets exactly once. Idempotent and safe to call from
     * every page load: the seeded flag guards the write, the same call shape
     * chrome.storage's set gives all-or-nothing keeps a half seed impossible.
     */
    async function ensureSeeded() {
        var result = await storageGet([SEEDED_KEY, SNIPPETS_KEY]);
        if (result[SEEDED_KEY]) return false;

        var data = {};
        if (!Array.isArray(result[SNIPPETS_KEY])) data[SNIPPETS_KEY] = SAMPLES;
        data[SEEDED_KEY] = true;
        await storageSet(data);
        return true;
    }

    /* ---------- change subscription ---------- */

    /**
     * Storage is shared between every open tab, so list pages
     * subscribe and re-render live instead of the site's form-POST reload.
     * Fires only for the snippets key — theme and capture traffic stays quiet.
     */
    function subscribe(callback) {
        var listener = function (changes, area) {
            if (area !== 'local' || !(SNIPPETS_KEY in changes)) return;
            callback();
        };
        chrome.storage.onChanged.addListener(listener);
        return function () { chrome.storage.onChanged.removeListener(listener); };
    }

    /* storage.set wraps values in its own structure; a plain key/value snapshot
       is all that is needed, the helper exists so every write goes through one
       shape and a future schema note has a single place to live */
    function snapshot(key, value) {
        var data = {};
        data[key] = value;
        return data;
    }

    return {
        SNIPPETS_KEY: SNIPPETS_KEY,
        loadAll: loadAll,
        get: get,
        loadLanguages: loadLanguages,
        insert: insert,
        update: update,
        remove: remove,
        exportObject: exportObject,
        exportFilename: exportFilename,
        importBackup: importBackup,
        saveCapture: saveCapture,
        takeCapture: takeCapture,
        ensureSeeded: ensureSeeded,
        subscribe: subscribe,
        byteLength: byteLength,
        numberFormat: numberFormat,
        sizeLabel: sizeLabel,
        lineCount: lineCount
    };
})();

if (typeof window !== 'undefined') {
    window.CodePointStore = CodePointStore;
} else {
    self.CodePointStore = CodePointStore;
}
