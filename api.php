<?php
/**
 * Single POST endpoint for every write: insert, update and delete.
 * The caller picks the operation with the `action` field.
 * All values are escaped with real_escape_string before being placed in the query.
 */
require 'tools/SQLHelper.php';

$sqlManager = new SQLHelper();

$action = isset($_POST['action']) ? $_POST['action'] : 'insert';
$codeId = isset($_POST['code_id']) ? (int)$_POST['code_id'] : 0;

// where an update should land afterwards — whitelisted, so it can never be
// turned into an open redirect by a hand-crafted POST
$returnTo = (isset($_POST['return_to']) && $_POST['return_to'] === 'index') ? 'index' : 'read';

if ($action === 'delete') {
    // code_id is cast to (int), so it can never carry SQL
    if ($codeId > 0) {
        $sqlManager->sendQuery("DELETE FROM `codes` WHERE code_id = " . $codeId);
    }
    // the snippet is gone, so there is no read page left to go back to
    header('Location: index.php');
    exit;
}

/* ---------- insert / update ---------- */
$title       = isset($_POST['code_title']) ? trim($_POST['code_title']) : '';
$language    = isset($_POST['code_language']) ? trim($_POST['code_language']) : '';
$description = isset($_POST['code_description']) ? trim($_POST['code_description']) : '';
$text        = isset($_POST['code_text']) ? $_POST['code_text'] : '';

// code_text itself is NOT trimmed — leading indentation must survive
if ($title === '' || $language === '' || $description === '' || trim($text) === '') {
    header('Location: ' . ($action === 'update' ? 'read.php?code_id=' . $codeId : 'index.php#new-code'));
    exit;
}

// column order here is the table's own order: title, text, lang, description
$titleEsc       = $sqlManager->escape($title);
$textEsc        = $sqlManager->escape($text);
$languageEsc    = $sqlManager->escape($language);
$descriptionEsc = $sqlManager->escape($description);

if ($action === 'update' && $codeId > 0) {
    $sqlManager->sendQuery(
        "UPDATE `codes` SET `code_title` = '$titleEsc', `code_text` = '$textEsc', "
        . "`code_lang` = '$languageEsc', `code_description` = '$descriptionEsc' "
        . "WHERE code_id = " . $codeId
    );
} else {
    $sqlManager->sendQuery(
        "INSERT INTO `codes` (`code_title`, `code_text`, `code_lang`, `code_description`)
         VALUES ('$titleEsc', '$textEsc', '$languageEsc', '$descriptionEsc')"
    );
}

header('Location: ' . ($returnTo === 'index' ? 'index.php' : 'read.php?code_id=' . $codeId));
exit;
