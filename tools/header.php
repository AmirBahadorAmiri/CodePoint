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
    <form action="api.php" method="post">
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
                <button class="btn btn-primary" type="submit">ذخیره کد</button>
            </div>
        </div>
    </form>
</dialog>

<script>
    (function () {
        var dialog = document.getElementById('code-dialog');

        document.querySelectorAll('[data-open-code-dialog]').forEach(function (btn) {
            btn.addEventListener('click', function () { dialog.showModal(); });
        });

        document.querySelectorAll('[data-close-code-dialog]').forEach(function (btn) {
            btn.addEventListener('click', function () { dialog.close(); });
        });

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });
    })();
</script>