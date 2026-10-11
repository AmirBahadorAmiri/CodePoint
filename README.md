# CodePoint

> 📖 [نسخه فارسی](./README.fa.md)

A code-snippet manager with a right-to-left interface.

![The snippet list](./screenshot/screenshot.png)

`PHP 8.2` · `MySQL / MariaDB` · `Apache` · `highlight.js` · `Vazirmatn`

## Features

- Search by title, language or id, and filter by language with counts.
- Viewer with line numbers, syntax highlighting, and copy with or without line numbers.
- Right-click menu on every snippet: copy, edit, delete.
- Light and dark themes.
- JSON export and import. Import merges by `code_id` and never deletes.
- Delete is a POST; queries are escaped and the import uses prepared statements.

## Install

Needs PHP 8.0+ with `mysqli`, MySQL 5.7+ / MariaDB 10.4+, and Apache.

**1.** Copy `CodePoint/` into your web root.

**2.** Import the schema:

```bash
mysql -u root -p < database.sql
```

or use `Import` in phpMyAdmin.

**3.** Put your credentials at the top of `tools/SQLHelper.php` (defaults: `localhost`, `root`, empty, `code_point`).

**4.** Start Apache and MySQL, then open `http://localhost/CodePoint/`.

> ⚠️ `database.sql` drops the `codes` table, so importing it over an existing install erases your snippets.

The font and highlight.js load from a CDN; everything else works offline.

## Structure

```text
index.php        # list, search, language filter, right-click menu
read.php         # viewer: line numbers, highlighting, copy
api.php          # POST: insert / update / delete / import
backup.php       # JSON export
database.sql     # schema
images/          # favicon
screenshot/      # the screenshot above
tools/
  SQLHelper.php  # connection and query helpers  ← credentials here
  header.php     # shared <head>, navbar, dialogs, toasts
  import.php     # JSON import, merged by code_id
  style.css      # styles
```

## License

[GPL-3.0](./LICENSE)