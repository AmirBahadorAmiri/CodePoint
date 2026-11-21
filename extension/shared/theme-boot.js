/**
 * Puts the stored theme on <html> before the first paint.
 *
 * The site does this with a blocking inline script above every stylesheet;
 * MV3 forbids inline scripts, so the same trick runs from this external head
 * script — still blocking, still before the first stylesheet, so a reload
 * never flashes the light page for a frame.
 */
(function () {
    try {
        var saved = localStorage.getItem('codepoint-theme');
        if (saved === 'light' || saved === 'dark') {
            document.documentElement.dataset.theme = saved;
        }
    } catch (err) {
        /* storage is blocked in private mode; the OS preference still applies */
    }
})();
