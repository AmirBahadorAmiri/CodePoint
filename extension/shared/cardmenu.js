/**
 * The code card context menu — a port of index.php's inline script.
 *
 * Injects the same #code-menu markup, wires right-click on .code-card / the
 * "..." button / full keyboard navigation, and routes the three actions
 * through the shared codePoint API instead of a form POST. Cards may be
 * rendered at any time because the wiring is event delegation on document.
 */

(function () {
    'use strict';

    var api = window.codePoint;
    var store = window.CodePointStore;

    var MENU_HTML =
        '<div class="context-menu" id="code-menu" role="menu" hidden aria-label="گزینه‌های کد">' +
        '    <button type="button" class="context-menu-item" role="menuitem" data-action="copy">' +
        '        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"' +
        '             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '            <rect x="9" y="9" width="12" height="12" rx="2"></rect>' +
        '            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>' +
        '        </svg>' +
        '        کپی کد' +
        '    </button>' +
        '    <button type="button" class="context-menu-item" role="menuitem" data-action="edit">' +
        '        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"' +
        '             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '            <path d="M12 20h9"></path>' +
        '            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>' +
        '        </svg>' +
        '        ویرایش' +
        '    </button>' +
        '    <div class="context-menu-sep" role="separator"></div>' +
        '    <button type="button" class="context-menu-item is-danger" role="menuitem" data-action="delete">' +
        '        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"' +
        '             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '            <path d="M3 6h18"></path>' +
        '            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"></path>' +
        '            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>' +
        '            <path d="M10 11v6M14 11v6"></path>' +
        '        </svg>' +
        '        حذف' +
        '    </button>' +
        '</div>';

    var menu = document.getElementById('code-menu');
    if (!menu) {
        var host = document.createElement('div');
        host.innerHTML = MENU_HTML;
        document.body.appendChild(host);
        menu = document.getElementById('code-menu');
    }

    var items = menu.querySelectorAll('.context-menu-item');
    var card = null;   // the card the open menu belongs to
    var opener = null; // what to focus again once the menu closes

    function closest(el, selector) {
        while (el && el !== document.body) {
            if (el.matches && el.matches(selector)) return el;
            el = el.parentElement;
        }
        return null;
    }

    function closeMenu() {
        if (menu.hidden) return;
        menu.hidden = true;
        card = null;
        if (opener) {
            opener.focus();
            opener = null;
        }
    }

    function openMenu(target, x, y, source) {
        card = target;
        opener = source;

        menu.hidden = false;
        // park it at the origin first so it can be measured, then place it
        menu.style.left = '0px';
        menu.style.top = '0px';
        var box = menu.getBoundingClientRect();
        var left = x;
        var top = y;
        if (left + box.width > window.innerWidth - 8) {
            left = Math.max(8, window.innerWidth - box.width - 8);
        }
        if (top + box.height > window.innerHeight - 8) {
            top = Math.max(8, window.innerHeight - box.height - 8);
        }
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        items[0].focus();
    }

    function payload() {
        return {
            id: card.dataset.codeId,
            title: card.dataset.codeTitle,
            lang: card.dataset.codeLang,
            description: card.dataset.codeDesc,
            text: card.dataset.codeText,
            returnTo: 'index'
        };
    }

    // right-click: only cards get the custom menu, the rest keeps the browser's
    document.addEventListener('contextmenu', function (event) {
        var target = closest(event.target, '.code-card, .mini-card');
        if (!target) {
            closeMenu();
            return;
        }
        event.preventDefault();
        openMenu(target, event.clientX, event.clientY, null);
    });

    // the "..." button is the same menu, for pointers that cannot right-click
    document.addEventListener('click', function (event) {
        var trigger = closest(event.target, '[data-code-menu]');
        if (trigger) {
            var box = trigger.getBoundingClientRect();
            openMenu(closest(trigger, '.code-card, .mini-card'), box.left, box.bottom + 6, trigger);
            return;
        }
        if (!menu.contains(event.target)) closeMenu();
    });

    menu.addEventListener('click', function (event) {
        var item = closest(event.target, '.context-menu-item');
        if (!item || !card) return;

        var data = payload();
        closeMenu();
        if (!api) return;

        var action = item.dataset.action;
        if (action === 'copy') {
            api.copy(data.text);
        } else if (action === 'edit') {
            api.openEditor(data);
        } else if (action === 'delete') {
            api.remove(data.id, data.title);
        }
    });

    function focusOffset(step, fromStart, fromEnd) {
        var current = -1;
        for (var i = 0; i < items.length; i++) {
            if (items[i] === document.activeElement) current = i;
        }
        if (current < 0) return;

        var next;
        if (fromStart) next = 0;
        else if (fromEnd) next = items.length - 1;
        else next = (current + step + items.length) % items.length;
        items[next].focus();
    }

    menu.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu();
            return;
        }
        // a menu that is open is a popup: Tab leaves it instead of walking into it
        if (event.key === 'Tab') {
            closeMenu();
            return;
        }
        if (event.key === 'ArrowDown') focusOffset(1, false, false);
        else if (event.key === 'ArrowUp') focusOffset(-1, false, false);
        else if (event.key === 'Home') focusOffset(0, true, false);
        else if (event.key === 'End') focusOffset(0, false, true);
        else return;
        event.preventDefault();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !menu.hidden) closeMenu();
    });

    // a menu pinned to a scrolled-away position is worse than no menu at all
    window.addEventListener('scroll', function () {
        if (!menu.hidden) closeMenu();
    }, true);
    window.addEventListener('resize', function () {
        if (!menu.hidden) closeMenu();
    });

    // nothing is exported — including this script twice is the whole contract
    void store;
})();
