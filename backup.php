<?php
/**
 * Export every snippet as a JSON backup.
 *
 * A GET link is fine here: the endpoint only reads, and a download is a GET
 * by definition. Import, which writes, lives in api.php as a POST.
 *
 * The payload keeps code_id so an import can merge back onto the same rows
 * instead of blindly appending duplicates.
 */
require 'tools/SQLHelper.php';

$sqlManager = new SQLHelper();
$snippets = $sqlManager->fetchAll("SELECT * FROM `codes` ORDER BY code_id ASC");

if ($snippets === null) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'خروجی گرفتن از دیتابیس ممکن نشد.';
    exit;
}

$backup = [
    'format'    => 'codepoint-backup',
    'version'   => 1,
    'exported'  => date('c'),
    'count'     => count($snippets),
    'snippets'  => array_map(function ($row) {
        return [
            'code_id'          => (int)$row['code_id'],
            'code_title'       => $row['code_title'],
            'code_text'        => $row['code_text'],
            'code_lang'        => $row['code_lang'],
            'code_description' => $row['code_description'],
        ];
    }, $snippets),
];

// JSON_UNESCAPED_UNICODE keeps the Persian titles readable in the file; the
// unescaped slashes keep code snippets from turning every path into a mess
$json = json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

if ($json === false) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'خروجی گرفتن از دیتابیس ممکن نشد.';
    exit;
}

$stamp = date('Y-m-d_His');
header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="codepoint-backup-' . $stamp . '.json"');
header('Content-Length: ' . strlen($json));
// a stale cached copy of a backup would be worse than no backup at all
header('Cache-Control: no-store');

echo $json;