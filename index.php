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
                    <a class="code-card" href="read.php?code_id=<?= (int)$code['code_id'] ?>">
                        <div class="code-card-head">
                            <h2 class="code-card-title"><?= htmlspecialchars($code['code_title']) ?></h2>
                            <span class="badge badge-lang"><?= htmlspecialchars($code['code_lang']) ?></span>
                        </div>
                        <p class="code-card-desc"><?= htmlspecialchars($code['code_description']) ?></p>
                        <div class="code-card-meta">
                            <span>#<?= (int)$code['code_id'] ?></span>
                            <span><?= number_format(strlen($code['code_text'])) ?> کاراکتر</span>
                            <span><?= substr_count($code['code_text'], "\n") + 1 ?> خط</span>
                        </div>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

    </main>

</div>