<?php
/**
 * Creates a new snippet, then redirects back to the list.
 * All values are escaped with real_escape_string before being placed in the query.
 */
require 'tools/SQLHelper.php';

$sqlManager = new SQLHelper();

$title       = isset($_POST['code_title']) ? trim($_POST['code_title']) : '';
$language    = isset($_POST['code_language']) ? trim($_POST['code_language']) : '';
$description = isset($_POST['code_description']) ? trim($_POST['code_description']) : '';
$text        = isset($_POST['code_text']) ? $_POST['code_text'] : '';

// code_text itself is NOT trimmed — leading indentation must survive
if ($title === '' || $language === '' || $description === '' || trim($text) === '') {
    header('Location: index.php#new-code');
    exit;
}

$sqlManager->sendQuery(
    "INSERT INTO `codes` (`code_title`, `code_text`, `code_lang`, `code_description`)
     VALUES ('"
    . $sqlManager->escape($title) . "', '"
    . $sqlManager->escape($text) . "', '"
    . $sqlManager->escape($language) . "', '"
    . $sqlManager->escape($description) . "')"
);

header('Location: index.php');
exit;