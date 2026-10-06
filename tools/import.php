<?php
/**
 * Import a backup.php JSON file back into the database.
 *
 * Included by api.php when action=import, so $sqlManager already exists in that
 * scope — this file only adds the branch.
 *
 * Merge semantics: a snippet whose code_id already exists is updated in place,
 * a new one is inserted. Nothing is ever deleted, so restoring an old backup
 * cannot wipe snippets that were added since.
 *
 * The whole batch runs in one transaction. A backup can hold many rows, and a
 * half-applied import is worse than a failed one.
 *
 * Every statement is a prepared statement: unlike the escape-then-interpolate
 * style of the insert/update branch, nothing here has a path where a value
 * could reach the query as SQL at all.
 */

/** @var SQLHelper $sqlManager */

$redirect = function ($query) {
    header('Location: index.php?' . $query);
    exit;
};

$fail = function ($reason) use ($redirect) {
    $redirect('import=failed&reason=' . rawurlencode($reason));
};

if (!isset($_FILES['backup_file']) || !is_array($_FILES['backup_file'])) {
    $fail('nofile');
}

$file = $_FILES['backup_file'];

// UPLOAD_ERR_INI_SIZE / FORM_SIZE mean the file was bigger than PHP allows;
// saying so out loud matters, because a silent "nothing happened" reads like
// data loss to whoever is trying to restore a backup
if ($file['error'] !== UPLOAD_ERR_OK) {
    $isTooBig = ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE);
    $fail($isTooBig ? 'toolarge' : 'upload');
}

$json = file_get_contents($file['tmp_name']);

if ($json === false || trim($json) === '') {
    $fail('empty');
}

// the depth cap keeps a deeply nested file from exhausting the parser's stack
$data = json_decode($json, true, 32);

if (json_last_error() !== JSON_ERROR_NONE) {
    $fail('badjson');
}

if (!is_array($data) || !isset($data['snippets']) || !is_array($data['snippets'])) {
    $fail('badschema');
}

/**
 * A row is usable only when all four text columns are present and non-empty.
 * code_id is optional: without one the row is inserted and MySQL assigns the id.
 */
$normalize = function ($row) {
    if (!is_array($row)) {
        return null;
    }

    $title       = isset($row['code_title'])       ? (string)$row['code_title']       : '';
    $text        = isset($row['code_text'])        ? (string)$row['code_text']        : '';
    $language    = isset($row['code_lang'])        ? (string)$row['code_lang']        : '';
    $description = isset($row['code_description']) ? (string)$row['code_description'] : '';

    if (trim($title) === '' || trim($language) === '' || trim($description) === '' || trim($text) === '') {
        return null;
    }

    // only a positive integer id is trusted; anything else becomes 0, which means
    // "insert fresh" rather than "overwrite whichever row has this number"
    $id = isset($row['code_id']) ? (int)$row['code_id'] : 0;

    // code_text is deliberately not trimmed: indentation has to survive
    return [
        'id'          => $id > 0 ? $id : 0,
        'title'       => $title,
        'text'        => $text,
        'language'    => $language,
        'description' => $description,
    ];
};

$rows = [];

foreach ($data['snippets'] as $raw) {
    $row = $normalize($raw);
    if ($row !== null) {
        $rows[] = $row;
    }
}

if (!$rows) {
    $fail('norows');
}

// rows the file carried but that were unusable are counted and reported,
// never silently dropped
$skipped = count($data['snippets']) - count($rows);

$conn = $sqlManager->getSql();
$conn->begin_transaction();

$inserted = 0;
$updated  = 0;

try {
    foreach ($rows as $index => $row) {
        // bind_param takes its arguments BY REFERENCE, and a reference into
        // $rows[$index] is invalidated the moment the next iteration rebinds that
        // slot. Every statement would then write through the same storage and
        // silently overwrite each other, so each value gets its own variable.
        $id          = $row['id'];
        $title       = $row['title'];
        $text        = $row['text'];
        $language    = $row['language'];
        $description = $row['description'];

        // the check and the write are two statements, but they share the
        // transaction, so a row inserted by someone else in between turns this
        // into an update of their row rather than a duplicate
        $found = 0;

        if ($id > 0) {
            $checkId = $id;
            $exists = $conn->prepare('SELECT 1 FROM `codes` WHERE code_id = ?');
            $exists->bind_param('i', $checkId);
            $exists->execute();
            $exists->store_result();
            $exists->bind_result($found);
            $exists->fetch();
            $exists->close();
        }

        if ($found === 1) {
            $updateId = $id;
            $stmt = $conn->prepare(
                'UPDATE `codes` SET `code_title` = ?, `code_text` = ?, `code_lang` = ?, '
                . '`code_description` = ? WHERE code_id = ?'
            );
            // four 's' for the four bound columns, then the int id — a stray 'i'
            // in front casts the title to a number and every row ends up "0"
            $stmt->bind_param('ssssi', $title, $text, $language, $description, $updateId);
            $stmt->execute();
            $stmt->close();
            $updated++;
            continue;
        }

        if ($id > 0) {
            $insertId = $id;
            $stmt = $conn->prepare(
                'INSERT INTO `codes` (`code_id`, `code_title`, `code_text`, `code_lang`, `code_description`) '
                . 'VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('issss', $insertId, $title, $text, $language, $description);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO `codes` (`code_title`, `code_text`, `code_lang`, `code_description`) '
                . 'VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('ssss', $title, $text, $language, $description);
        }

        $stmt->execute();
        $stmt->close();
        $inserted++;
    }

    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $fail('database');
}

$redirect('import=ok&inserted=' . $inserted . '&updated=' . $updated . '&skipped=' . $skipped);