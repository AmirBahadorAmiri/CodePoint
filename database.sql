-- =============================================================
--  CodePoint — database schema
--  MySQL 5.7+ / MariaDB 10.4+
--
--  Import from the command line:
--      mysql -u root -p < database.sql
--
--  Or in phpMyAdmin:  Import -> choose this file -> Go
-- =============================================================

CREATE DATABASE IF NOT EXISTS `code_point`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `code_point`;

-- utf8mb4 so Persian titles, descriptions and any Unicode inside a
-- snippet survive the round trip. InnoDB for real transactions and
-- foreign keys if you extend the schema later.
DROP TABLE IF EXISTS `codes`;

CREATE TABLE `codes` (
    `code_id`         INT AUTO_INCREMENT PRIMARY KEY,
    `code_title`      VARCHAR(255) NOT NULL,
    `code_text`       TEXT         NOT NULL,
    `code_lang`       VARCHAR(100) NOT NULL,
    `code_description` TEXT        NOT NULL,

    -- the language filter lists every code_lang with its count
    INDEX `idx_codes_lang` (`code_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Optional sample data — safe to skip.
--  Delete the block below if you want to start with an empty list.
-- -------------------------------------------------------------
-- INSERT INTO `codes` (`code_title`, `code_text`, `code_lang`, `code_description`) VALUES
-- ('خوش‌آمدید',
--  '<?php\n\n// اینجا کدت را بنویس\n$name = ''Amir'';\necho "سلام {$name}";',
--  'PHP',
--  'یک قطعه‌کد نمونه برای تست اولیه.'),
-- ('خواندن فایل خط‌به‌خط',
--  '<?php\n\n$file = new SplFileObject(''data.txt'');\nforeach ($file as $lineNumber => $line) {\n    echo $lineNumber . '': '' . $line;\n}',
--  'PHP',
--  'خواندن فایل با SplFileObject و شماره‌گذاری خطوط.'),
-- ('Hello World',
--  'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello World!");\n    }\n}',
--  'Java',
--  'ساده‌ترین برنامه جاوا.');
