<?php
require 'tools/SQLHelper.php';

$sqlManager = new SQLHelper();

/* ---------- Read search / filter state ---------- */
$search     = isset($_POST['search_title']) ? trim($_POST['search_title']) : '';
$searchType = isset($_POST['searchType']) ? $_POST['searchType'] : 'title';
$lang       = isset($_GET['lang']) ? trim($_GET['lang']) : '';

$where = [];

if ($search !== '') {
    $safeSearch = $sqlManager->escape($search);

    if ($searchType === 'id') {
        $where[] = "code_id = " . (int)$search;
    } elseif ($searchType === 'language') {
        $where[] = "code_lang LIKE '%$safeSearch%'";
    } else {
        $where[] = "code_title LIKE '%$safeSearch%'";
    }
}

if ($lang !== '') {
    $where[] = "code_lang = '" . $sqlManager->escape($lang) . "'";
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$codes     = $sqlManager->fetchAll("SELECT * FROM `codes`$whereSql ORDER BY code_id DESC");
$languages = $sqlManager->fetchAll(
    "SELECT code_lang, COUNT(*) AS total FROM `codes` GROUP BY code_lang ORDER BY total DESC, code_lang"
);
$totalCodes = array_sum(array_column($languages, 'total'));

/* ---------- Build filter URLs (keeps the current search) ---------- */
$baseParams = [];
if ($search !== '') {
    $baseParams['search_title'] = $search;
    $baseParams['searchType']   = $searchType;
}
$allUrl = 'index.php' . ($baseParams ? '?' . http_build_query($baseParams) : '');

$pageTitle = 'خانه';
$activePage = 'home';
require 'tools/header.php';
?>

<div class="layout">

    <!-- ================= Sidebar ================= -->
    <aside class="sidebar">

        <!-- Search -->
        <section class="panel sidebar-section">
            <h2 class="sidebar-title">جستجو</h2>
            <form action="index.php" method="post" class="search-form">
                <div class="segmented">
                    <label>
                        <input type="radio" name="searchType" value="title" <?= $searchType === 'title' ? 'checked' : '' ?>>
                        <span>عنوان</span>
                    </label>
                    <label>
                        <input type="radio" name="searchType" value="language" <?= $searchType === 'language' ? 'checked' : '' ?>>
                        <span>زبان</span>
                    </label>
                    <label>
                        <input type="radio" name="searchType" value="id" <?= $searchType === 'id' ? 'checked' : '' ?>>
                        <span>شناسه</span>
                    </label>
                </div>
                <input class="field" type="text" name="search_title"
                       value="<?= htmlspecialchars($search) ?>" placeholder="جستجو در کدها...">
                <div class="submit-row">
                    <button class="btn btn-primary btn-sm" type="submit">جستجو</button>
                    <?php if ($search !== '' || $lang !== '') { ?>
                        <a class="btn btn-ghost btn-sm" href="index.php">پاک کردن</a>
                    <?php } ?>
                </div>
            </form>
        </section>

        <!-- Language filter -->
        <section class="panel sidebar-section">
            <h2 class="sidebar-title">زبان‌ها</h2>
            <div class="filter-list">
                <a class="filter-item <?= $lang === '' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($allUrl) ?>">
                    <span>همه</span>
                    <span class="filter-count"><?= (int)$totalCodes ?></span>
                </a>
                <?php foreach ($languages as $row) { ?>
                    <?php
                    $params   = $baseParams;
                    $params['lang'] = $row['code_lang'];
                    $itemUrl  = 'index.php?' . http_build_query($params);
                    $isActive = ($lang === $row['code_lang']);
                    ?>
                    <a class="filter-item <?= $isActive ? 'is-active' : '' ?>" href="<?= htmlspecialchars($itemUrl) ?>">
                        <span dir="ltr"><?= htmlspecialchars($row['code_lang']) ?></span>
                        <span class="filter-count"><?= (int)$row['total'] ?></span>
                    </a>
                <?php } ?>
            </div>
        </section>

    </aside>

    <!-- ================= Main ================= -->
    <main class="main">

        <div class="page-head">
            <div>
                <h1><?= $lang !== '' ? 'کدهای ' . htmlspecialchars($lang) : 'همه کدها' ?></h1>
                <p>
                    <?= (int)count($codes) ?> کد
                    <?php if ($search !== '') { ?>
                        برای «<?= htmlspecialchars($search) ?>»
                    <?php } ?>
                    · <?= (int)$totalCodes ?> کد در <?= count($languages) ?> زبان
                </p>
            </div>
        </div>

        <?php if (!$codes) { ?>
            <section class="panel empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="8" y="2" width="8" height="4" rx="1"></rect>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                </svg>
                <p class="empty-state-title">کدی پیدا نشد</p>
                <p class="empty-state-text">
                    فیلترها را تغییر بده یا از فرم «ثبت کد جدید» اولین کدت را اضافه کن.
                </p>
            </section>
        <?php } else { ?>
            <div class="code-list">
                <?php foreach ($codes as $code) { ?>
                    <?php
                    $cardId    = (int)$code['code_id'];
                    $cardTitle = $code['code_title'];
                    $cardLang  = $code['code_lang'];
                    $cardDesc  = $code['code_description'];
                    // the HTML parser turns a literal CR in an attribute value into an
                    // LF, which would turn every CRLF into a blank line; the entities
                    // are added after escaping, so they survive that untouched
                    $cardText = str_replace(["\r\n", "\r"], "\n", $code['code_text']);
                    $cardText = str_replace("\n", '&#10;', htmlspecialchars($cardText, ENT_QUOTES));
                    ?>
                    <article class="code-card"
                             data-code-id="<?= $cardId ?>"
                             data-code-title="<?= htmlspecialchars($cardTitle, ENT_QUOTES) ?>"
                             data-code-lang="<?= htmlspecialchars($cardLang, ENT_QUOTES) ?>"
                             data-code-desc="<?= htmlspecialchars($cardDesc, ENT_QUOTES) ?>"
                             data-code-text="<?= $cardText ?>">

                        <a class="code-card-link" href="read.php?code_id=<?= $cardId ?>">
                            <div class="code-card-head">
                                <h2 class="code-card-title"><?= htmlspecialchars($cardTitle) ?></h2>
                                <span class="badge badge-lang"><?= htmlspecialchars($cardLang) ?></span>
                            </div>
                            <p class="code-card-desc"><?= htmlspecialchars($cardDesc) ?></p>
                        </a>

                        <div class="code-card-meta">
                            <span>#<?= $cardId ?></span>
                            <span><?= number_format(strlen($code['code_text'])) ?> کاراکتر</span>
                            <span><?= substr_count($code['code_text'], "\n") + 1 ?> خط</span>
                            <button type="button" class="icon-btn code-card-more" data-code-menu
                                    aria-haspopup="menu" aria-label="گزینه‌های این کد">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <circle cx="12" cy="5" r="1.7"></circle>
                                    <circle cx="12" cy="12" r="1.7"></circle>
                                    <circle cx="12" cy="19" r="1.7"></circle>
                                </svg>
                            </button>
                        </div>
                    </article>
                <?php } ?>
            </div>
        <?php } ?>

    </main>

</div>

<div class="context-menu" id="code-menu" role="menu" hidden aria-label="گزینه‌های کد">
    <button type="button" class="context-menu-item" role="menuitem" data-action="copy">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="9" y="9" width="12" height="12" rx="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
        </svg>
        کپی کد
    </button>
    <button type="button" class="context-menu-item" role="menuitem" data-action="edit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
        </svg>
        ویرایش
    </button>
    <div class="context-menu-sep" role="separator"></div>
    <button type="button" class="context-menu-item is-danger" role="menuitem" data-action="delete">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 6h18"></path>
            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"></path>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
            <path d="M10 11v6M14 11v6"></path>
        </svg>
        حذف
    </button>
</div>

<script>
    (function () {
        var menu = document.getElementById('code-menu');
        if (!menu) return;

        var api = window.codePoint;
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
            var target = closest(event.target, '.code-card');
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
                openMenu(closest(trigger, '.code-card'), box.left, box.bottom + 6, trigger);
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
    })();
</script>