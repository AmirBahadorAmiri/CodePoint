# CodePoint

> 🌍 [English version](./README.md)

مدیریت قطعه‌کد فارسی با رابط کاملاً راست‌به‌چپ — کدها را ذخیره کن، جستجو کن، بر اساس زبان مرور کن و با شماره خط و هایلایت سینتکس بخوان. PHP و MySQL خام روی آپاچی، بدون فریم‌ورک و بدون مرحله build.

![اسکرین‌شات صفحه لیست کدها](./screenshot/screenshot.png)

<p align="center">
  <a href="#-امکانات">امکانات</a> ·
  <a href="#-ساختار-پروژه">ساختار پروژه</a> ·
  <a href="#-پیش‌نیازها">پیش‌نیازها</a> ·
  <a href="#-مشارکت">مشارکت</a>
</p>

![پلتفرم](https://img.shields.io/badge/platform-XAMPP%20%2B%20Apache-f97316?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![پایگاه داده](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-10.4-003545?logo=mariadb&logoColor=white)
![سینتکس](https://img.shields.io/badge/syntax-highlight.js-11.9-563d7c?style=flat-square)
![پروانه](https://img.shields.io/badge/license-see%20repo-6b7280?style=flat-square)
![آخرین کامیت](https://img.shields.io/github/last-commit/AmirBahadorAmiri/CodePoint?style=flat-square&label=last%20commit)

## ✨ امکانات

### 📚 کتابخانه کدها

- لیست کارتی که برای هر قطعه‌کد عنوان، برچسب زبان، توضیح محدودشده به دو خط و متا (شناسه، تعداد کاراکتر، تعداد خط) را نشان می‌دهد.
- پنل حالت خالی وقتی جستجو یا فیلتر هیچ نتیجه‌ای ندارد.
- متای حجم به‌صورت نسبی (بایت / کیلوبایت) با رندر امن LTR، طوری که `80 B` در سند راست‌به‌چپ هرگز به `B 80` وارونه نشود.

### 🔍 جستجو و فیلتر

- جستجو در سه حالت: بر اساس **عنوان**، بر اساس **زبان**، یا بر اساس **شناسه** — که با کنترل سه‌بخشی در سایدبار انتخاب می‌شود.
- لیست فیلتر زبان که از کوئری `GROUP BY` ساخته می‌شود و کنار هر زبان تعداد قطعه‌کدهایش را نشان می‌دهد.
- جستجوی فعال هنگام کلیک روی فیلتر زبان حفظ می‌شود، پس فیلترها همدیگر را ریست نمی‌کنند بلکه ترکیب می‌شوند.

### 💬 نمایشگر کد

- صفحه اختصاصی `read.php?code_id=N` با نوار ابزار چسبان: عنوان قطعه‌کد، لیست انتخاب زبان، دکمه‌های **کپی** و **کپی با شماره خط**.
- ستون شماره خط که با کد هایلایت‌شده هم‌تراز می‌ماند، و ناحیه اسکرول افقی برای خطوط بلند.
- هایلایت سینتکس با highlight.js 11.9.0 (تم github-dark) از CDN، که با تغییر زبان دوباره اعمال می‌شود.
- سایدبار شامل متای قطعه‌کد و توضیح کامل آن.

### ⚙️ رابط کاربری

- چیدمان کاملاً راست‌به‌چپ ساخته‌شده با **ویژگی‌های منطقی** CSS (`margin-inline-*`، `padding-inline`، `border-inline-*`) به‌جای left/right ثابت.
- دیزاین‌سیستم دست‌نویس در یک فایل استایل واحد — متغیرهای CSS برای رنگ، فاصله، شعاع و سایه، به‌همراه قرارداد کلاس‌ها مستندشده در هدر فایل.
- فرم «ثبت کد جدید» داخل یک مودال بومی `<dialog>` قرار دارد تا سایدبار فضای اسکرول خودش را حفظ کند.
- واکنش‌گرا: چیدمان دو ستونی در دسکتاپ، تک‌ستونی در `1024px`، و wrap شدن نوار ابزار و کوچک شدن فونت کد در `640px`.
- فونت وزیرمتن برای رابط فارسی، با بازگشت به `system-ui` / `Tahoma`.

### 🔒 ایمنی

- هر مقدار پیش از رسیدن به کوئری با `SQLHelper::escape()` (`mysqli_real_escape_string`) escape می‌شود — هیچ الحاق رشته‌ای داخل SQL انجام نمی‌شود.
- همه مقادیر نمایش‌داده‌شده از `htmlspecialchars()` عبور می‌کنند.
- `code_id` به `(int)` تبدیل می‌شود، پس هرگز نمی‌تواند SQL حمل کند.
- `SQLHelper` فراخوانی‌های اتصال و کوئری را در `try/catch` برای `mysqli_sql_exception` پیچیده — چیزی که PHP 8.1+ به‌صورت پیش‌فرض پرتاب می‌کند.

## 🛠 تکنولوژی‌ها

| بخش | ابزار |
| --- | --- |
| زبان | PHP 8.2 |
| پایگاه داده | MySQL / MariaDB 10.4 (`utf8mb4_unicode_ci`، InnoDB) |
| وب‌سرور | آپاچی از طریق XAMPP |
| دسترسی به دیتابیس | `mysqli`، پیچیده‌شده در `tools/SQLHelper.php` |
| هایلایت سینتکس | highlight.js 11.9.0 (CDN) |
| فونت | Vazirmatn (CDN) |
| استایل | دیزاین‌سیستم دست‌نویس CSS با ویژگی‌های منطقی |
| فرانت‌اند | جاوااسکریپت خام، `<dialog>` بومی |
| ابزار | `php -l` برای بررسی نحو، Playwright برای تست بصری |

## 📁 ساختار پروژه

```text
CodePoint/
├── index.php               # خانه: جستجو، فیلتر زبان، لیست کارت کدها
├── read.php                # نمایشگر: شماره خط، هایلایت، دکمه‌های کپی
├── api.php                 # endpoint پست: اعتبارسنجی و درج کد جدید
├── database.sql            # اسکیما: CREATE DATABASE + CREATE TABLE codes (+ داده نمونه اختیاری)
├── screenshot/
│   └── screenshot.png      # اسکرین‌شات استفاده‌شده در این README
└── tools/
    ├── SQLHelper.php       # لایه دیتابیس: escape()، fetchAll()، fetchOne()، sendQuery()
    ├── header.php          # پارشال مشترک: <head>، نوار بالا، مودال ثبت کد
    └── style.css           # دیزاین‌سیستم (توکن‌ها، چیدمان، کامپوننت‌ها)
```

- **`index.php`** — نقطه ورود. عبارت جستجو و زبان فعال را می‌خواند، شرط `WHERE` را با مقادیر escape‌شده می‌سازد و لیست کارت‌ها به‌همراه سایدبار فیلتر را رندر می‌کند.
- **`read.php`** — نمایشگر کد. روی `(int)$_GET['code_id']` یک `fetchOne()` می‌زند و سپس نوار ابزار، ستون شماره خط و بدنه هایلایت‌شده را می‌سازد.
- **`api.php`** — مقصد فرم. خالی نبودن عنوان، زبان، توضیح و کد را بررسی می‌کند، هر چهار را escape می‌کند، درج می‌کند و به `index.php` ریدایرکت می‌شود.
- **`tools/header.php`** — توسط هر دو صفحه include می‌شود. قبل از include کردن، `$pageTitle` و `$activePage` را تنظیم کنید.
- **`tools/SQLHelper.php`** — اطلاعات اتصال و همه متدهای کوئری را نگه می‌دارد. **اول این فایل را برای تنظیم اتصال ویرایش کنید.**

## 🚀 اجرا و بیلد

چیزی برای بیلد کردن وجود ندارد — نه Composer، نه باندلر. پوشه را در وب‌روت کپی کنید و یک جدول بسازید.

### ۱. انتقال پروژه

پوشه `CodePoint/` را داخل `htdocs` در XAMPP کپی کنید:

```bash
# ویندوز
xcopy /E /I CodePoint "C:\xampp\htdocs\CodePoint"
```

> 💡 اگر پوشه را داخل یک پوشه به نام `www` نگه می‌دارید، آدرس را در گام بعد اصلاح کنید — آدرس وب‌سایت از مسیر **زیر داکیومنت‌روت** می‌آید، نه از چیدمان دیسک شما.

### ۲. ساخت پایگاه داده

اسکیما در [`database.sql`](./database.sql) آمده، پس می‌توانید همان را ایمپورت کنید. از خط فرمان:

```bash
mysql -u root -p < database.sql
```

یا در phpMyAdmin (`http://localhost/phpmyadmin`): **Import → انتخاب `database.sql` → Go**. فایل UTF-8 است، پس عنوان‌های فارسی درست ایمپورت می‌شوند.

این فایل دیتابیس `code_point`، جدول `codes` و یک ایندکس روی `code_lang` برای فیلتر زبان می‌سازد:

```sql
CREATE DATABASE IF NOT EXISTS `code_point`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `code_point`;

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
```

> ⚠️ فایل `database.sql` شامل `DROP TABLE IF EXISTS codes` است — یعنی ایمپورت آن روی یک نصب موجود **کدهای شما را پاک می‌کند**. همچنین بلافاصله بعد از `CREATE TABLE` سه قطعه‌کد نمونه به‌صورت کامنت‌شده دارد؛ اگر داده نمونه می‌خواهید، آن بلوک را از حالت کامنت خارج کنید.

### ۳. اتصال اپ به دیتابیس

اطلاعات اتصال را در بالای `tools/SQLHelper.php` ویرایش کنید:

```php
private $hostname = "localhost";
private $username = "root";
private $password = "";
private $database_name = "code_point";
```

### ۴. اجرای سرویس‌ها و باز کردن اپ

**Apache** و **MySQL** را از پنل کنترل XAMPP روشن کنید و سپس این آدرس را باز کنید:

```text
http://localhost/CodePoint/
```

> 🌐 فونت Vazirmatn و highlight.js از CDN بارگذاری می‌شوند، پس اولین رنگ‌آمیزی به اینترنت نیاز دارد. چیدمان، ذخیره‌سازی و کپی همگی بدون اینترنت کار می‌کنند.

### ۵. بررسی نصب

```bash
# بررسی نحو همه فایل‌های PHP
for f in index.php read.php api.php tools/SQLHelper.php tools/header.php; do
  php -l "$f"
done
```

## 📋 پیش‌نیازها

- **PHP 8.0+** (توسعه و تست روی 8.2 انجام شده). به اکستنشن `mysqli` نیاز دارد.
- **MySQL 5.7+** یا **MariaDB 10.4+** (تست‌شده روی MariaDB 10.4.32).
- **آپاچی** با داکیومنت‌روتی که به پوشه شامل `CodePoint/` اشاره می‌کند.
- اتصال اینترنت فقط برای فایل‌های CDN — خود اپ کاملاً آفلاین کار می‌کند.

## 🤝 مشارکت

پول‌ریکوئست‌ها خوش‌آمد است. اگر باگی پیدا کردید، لطفاً یک issue با موارد زیر باز کنید:

- چه کاری انجام دادید،
- چه چیزی انتظار داشتید،
- چه چیزی به‌جای آن اتفاق افتاد،
- نسخه PHP و MySQL/MariaDB شما،
- مرورگری که استفاده کردید.

> 🔒 گزارش‌های امنیتی به‌خصوص استقبال می‌شود: این پروژه کد و SQL ورودی کاربر را ذخیره می‌کند، پس هر یافته مربوط به escaping یا XSS اولویت بالایی دارد.

---

<div align="center">

ساخته‌شده با ❤️ توسط [AmirBahadorAmiri](https://github.com/AmirBahadorAmiri)

</div>
