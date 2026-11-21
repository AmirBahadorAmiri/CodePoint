/**
 * CodePoint service worker.
 *
 * Extension-only glue that has no PHP counterpart:
 *   - seeds the sample snippets on first install (store.ensureSeeded)
 *   - a context menu that saves the selected code anywhere on the web into
 *     the editor (the selection travels through chrome.storage, the page that
 *     opens picks it up and prefills the form)
 *   - the keyboard shortcut that opens the full page in a tab
 */

importScripts('shared/store.js');

var INDEX_URL = 'index.html';

/* ---------- the icon click opens the app in a tab ---------- */

// no default_popup is declared, so a click on the toolbar icon fires
// action.onClicked — which hands the full page to a real tab
function openApp() {
    chrome.tabs.create({ url: chrome.runtime.getURL(INDEX_URL) });
}

chrome.action.onClicked.addListener(openApp);

/* ---------- first install ---------- */

chrome.runtime.onInstalled.addListener(function () {
    CodePointStore.ensureSeeded().catch(function () { /* retried on the next page load */ });

    chrome.contextMenus.removeAll(function () {
        chrome.contextMenus.create({
            id: 'codepoint-save-selection',
            title: 'ذخیره انتخاب در CodePoint',
            contexts: ['selection']
        });
    });
});

/* ---------- selection capture ---------- */

chrome.contextMenus.onClicked.addListener(function (info) {
    if (info.menuItemId !== 'codepoint-save-selection') return;
    if (!info.selectionText || !info.selectionText.trim()) return;

    CodePointStore.saveCapture({
        text: info.selectionText,
        source: info.pageUrl || '',
        capturedAt: new Date().toISOString()
    }).then(function () {
        // the editor opens as a full tab; index.html checks for a pending
        // capture on load and prefills the new-snippet form
        chrome.tabs.create({ url: chrome.runtime.getURL(INDEX_URL + '?capture=1') });
    });
});

/* ---------- keyboard shortcut ---------- */

chrome.commands.onCommand.addListener(function (command) {
    if (command === 'open-codepoint') {
        openApp();
    }
});
