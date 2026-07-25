# بررسی‌کننده پیش‌نیازهای وردپرس | WordPress Requirements Checker

> ابزار تک‌فایلی و دوزبانه (فارسی/انگلیسی) برای سنجش آمادگی هاست اشتراکی پیش از نصب وردپرس.
> A single-file, bilingual (FA/EN) tool that checks shared-hosting readiness before installing WordPress.

| | |
|---|---|
| **فایل / File** | `wordpress-check.php` |
| **نسخه / Version** | `1.0.0` |
| **سازگاری / Compatibility** | PHP 5.6 – 8.5 |
| **وابستگی / Dependencies** | ندارد (بدون CDN، بدون Composer) — None (no CDN, no Composer) |
| **توسعه / Developer** | شرکت نوید ایرانیان · Navid iranian Co. |

---

## 🇮🇷 فارسی

### معرفی
این اسکریپت سرور شما را در برابر **الزامات رسمی وردپرس** (منبع: `wordpress.org/about/requirements`) می‌سنجد و در یک صفحه‌ی مرتب می‌گوید دقیقاً چه چیزی باید تغییر کند. عمداً با نحو قدیمی PHP نوشته شده تا روی هاست‌های قدیمی هم اجرا شود و بتواند خودِ «قدیمی بودن نسخه‌ی PHP» را گزارش کند، نه اینکه با خطای Syntax متوقف شود.

### ویژگی‌ها
1. **گیج امتیاز آمادگی** با وزن‌دهی؛ موارد حیاتی وزن بیشتری دارند و یک مردودی حیاتی، حکم را به «آماده نیست» می‌برد.
2. **دوزبانه‌ی کامل** فارسی و انگلیسی با کلید تغییر زبان؛ جهت صفحه خودکار بین `rtl` و `ltr` عوض می‌شود.
3. **تست واقعی mod_rewrite** با ساخت یک پوشه‌ی موقت و درخواست به خودِ سرور (نه حدس از روی تنظیمات).
4. **تست اتصال دیتابیس** شامل نسخه، `utf8mb4`، موتور InnoDB و سطح دسترسی کاربر (با ساخت و حذف واقعی یک جدول).
5. **درخواست حلقه‌ای (Loopback)** برای اطمینان از کارکرد WP-Cron و انتشار زمان‌بندی‌شده.
6. **متن آماده‌ی تیکت پشتیبانی** که از روی نتایج ساخته می‌شود و با یک کلیک کپی می‌شود.
7. **قطعه‌ی php.ini** فقط شامل خطوطی که واقعاً باید عوض شوند.
8. **حذف امن خودکار** با دکمه‌ی تأییددار، به‌علاوه قفل اختیاری با کلید دسترسی.

### نصب و استفاده
1. فایل `wordpress-check.php` را در پوشه‌ی اصلی هاست (`public_html`) آپلود کنید.
2. در مرورگر باز کنید: `https://your-domain.com/wordpress-check.php`
3. گزارش را بخوانید؛ متن آماده را برای پشتیبانی هاست بفرستید.
4. **پس از پایان کار، حتماً فایل را حذف کنید** (دکمه‌ی «حذف این فایل از سرور»).

**تغییر زبان:** کلید بالای صفحه، یا `?lang=en` و `?lang=fa`
**تست‌های عمیق:** دکمه‌ی مربوطه، یا `?deep=1`

### چه چیزهایی بررسی می‌شود
1. **هسته و موتور اجرا** — نسخه PHP، دوره‌ی پشتیبانی امنیتی PHP، حالت اجرا (SAPI)، وب‌سرور، درایور دیتابیس، و شناسایی وردپرس نصب‌شده.
2. **افزونه‌های الزامی** — `json`, `mysqli`, `pcre`, `hash`, `filter`, `ctype`, `date`.
3. **افزونه‌های توصیه‌شده** — `mbstring`, `curl`, `gd`, `imagick`, `openssl`, `dom`, `fileinfo`, `zip`, `exif`, `intl`, `iconv`, `simplexml`, `xml`, `sodium`, `zlib` و OPcache.
4. **تنظیمات php.ini** — `memory_limit`, `max_execution_time`, `upload_max_filesize`, `post_max_size`, `max_input_vars`, `max_input_time`, `file_uploads`, `allow_url_fopen`, `display_errors`, `date.timezone`.
5. **محدودیت‌های هاست اشتراکی** — `disable_functions`, `open_basedir`, PHP خط فرمان برای کرون، و تابع `mail()`.
6. **فایل‌سیستم و مجوزها** — امکان ساخت فایل و پوشه، تطابق مالکیت، امنیت `wp-config.php`، پوشه‌ی موقت آپلود، فضای دیسک و HTTPS.
7. **تست‌های عمیق (اختیاری)** — دسترسی به `api.wordpress.org`، تست زنده‌ی mod_rewrite و پیوندهای یکتا، درخواست حلقه‌ای، و DNS.
8. **تست دیتابیس (اختیاری)** — اتصال، نسخه‌ی MySQL/MariaDB، پشتیبانی utf8mb4، موتور InnoDB و سطح دسترسی کاربر.

### پیکربندی
در بالای فایل چند ثابت قابل تنظیم است:

| ثابت | توضیح |
|---|---|
| `NVD_ACCESS_KEY` | خالی = بدون قفل. مقدار بگذارید و با `?key=...` باز کنید تا گزارش محافظت شود. |
| `NVD_VERSION` | نسخه‌ی ابزار. |
| `NVD_COMPANY_FA` / `NVD_COMPANY_EN` | نام شرکت (فارسی و انگلیسی). |
| `NVD_PHONE1` / `NVD_PHONE2` | شماره‌های تماس نمایش‌داده‌شده در پاورقی. |

### امنیت و حریم خصوصی
- هدر `noindex, nofollow` روی صفحه ست می‌شود تا در موتورهای جست‌وجو ایندکس نشود.
- اطلاعات تست دیتابیس فقط برای همان درخواست استفاده می‌شود و **ذخیره یا ارسال نمی‌شود**.
- این فایل مسیرها و تنظیمات سرور را نشان می‌دهد؛ برای همین **بعد از استفاده باید حذف شود**. از قفل `NVD_ACCESS_KEY` هم می‌توانید استفاده کنید.

### پشتیبانی
شرکت نوید ایرانیان — طراحی وب‌سایت، سئو، میزبانی وب، ثبت دامنه و دیجیتال مارکتینگ
📱 **۰۹۳۹۵۵۶۶۶۵۲** (همراه · واتساپ) — ☎️ **۰۲۱۹۱۳۰۳۶۶۲** (دفتر مرکزی)

---

## 🇬🇧 English

### Overview
This script checks your server against the **official WordPress requirements** (source: `wordpress.org/about/requirements`) and, on one tidy page, tells you exactly what to change. It is intentionally written in legacy PHP syntax so it also runs on old hosts and can *report* an outdated PHP version instead of dying with a syntax error.

### Features
1. **Weighted readiness score** — critical items carry more weight, and a single critical failure drops the verdict to “not ready.”
2. **Fully bilingual** FA/EN with a language toggle; page direction flips automatically between `rtl` and `ltr`.
3. **Live mod_rewrite test** — creates a temporary folder and calls the server itself (not a guess from settings).
4. **Database connection test** — version, `utf8mb4`, InnoDB engine and user privileges (via a real create-and-drop table).
5. **Loopback request** — confirms WP-Cron and scheduled publishing will work.
6. **Ready-made support ticket** — generated from the results, copied with one click.
7. **php.ini snippet** — only the lines that actually need changing.
8. **Safe self-delete** — a confirmed button, plus an optional access-key lock.

### Installation & usage
1. Upload `wordpress-check.php` to your site root (`public_html`).
2. Open in a browser: `https://your-domain.com/wordpress-check.php`
3. Read the report; send the ready-made text to your host support.
4. **When finished, delete the file** (the “Delete this file” button).

**Switch language:** the header toggle, or `?lang=en` / `?lang=fa`
**Deep tests:** the button, or `?deep=1`

### What it checks
1. **Core & engine** — PHP version, PHP security-support window, runtime (SAPI), web server, DB driver, and existing-WordPress detection.
2. **Required extensions** — `json`, `mysqli`, `pcre`, `hash`, `filter`, `ctype`, `date`.
3. **Recommended extensions** — `mbstring`, `curl`, `gd`, `imagick`, `openssl`, `dom`, `fileinfo`, `zip`, `exif`, `intl`, `iconv`, `simplexml`, `xml`, `sodium`, `zlib`, and OPcache.
4. **php.ini settings** — `memory_limit`, `max_execution_time`, `upload_max_filesize`, `post_max_size`, `max_input_vars`, `max_input_time`, `file_uploads`, `allow_url_fopen`, `display_errors`, `date.timezone`.
5. **Shared-hosting limits** — `disable_functions`, `open_basedir`, PHP CLI for cron, and the `mail()` function.
6. **Filesystem & permissions** — file/folder creation, ownership match, `wp-config.php` security, upload temp folder, disk space, and HTTPS.
7. **Deep tests (optional)** — access to `api.wordpress.org`, a live mod_rewrite / pretty-permalinks test, loopback request, and DNS.
8. **Database test (optional)** — connection, MySQL/MariaDB version, utf8mb4 support, InnoDB engine, and user privileges.

### Configuration
A few constants at the top of the file:

| Constant | Purpose |
|---|---|
| `NVD_ACCESS_KEY` | Empty = no lock. Set a value and open with `?key=...` to protect the report. |
| `NVD_VERSION` | Tool version. |
| `NVD_COMPANY_FA` / `NVD_COMPANY_EN` | Company name (Persian and English). |
| `NVD_PHONE1` / `NVD_PHONE2` | Contact numbers shown in the footer. |

### Security & privacy
- The page sends a `noindex, nofollow` header so it is not indexed by search engines.
- Database-test credentials are used only for that single request and are **never stored or sent anywhere**.
- This file exposes server paths and settings, so **delete it after use**. You can also lock it with `NVD_ACCESS_KEY`.

### WordPress requirements reference
| Component | Minimum | Recommended |
|---|---|---|
| PHP | 7.4 | 8.3+ |
| MySQL | 5.5.5 | 8.0+ |
| MariaDB | 10.4 | 10.11+ |
| HTTPS | — | Required for every install |
| Web server | Any PHP+MySQL | Apache / Nginx + mod_rewrite |
| `memory_limit` | 64M | 256M |

### Support
Navid iranian Co. — Web Design, SEO, Web Hosting, Domain Registration & Digital Marketing
📱 **09395566652** (Mobile · WhatsApp) — ☎️ **02191303662** (Head office)

---

<sub>© 1405 / 2026 — شرکت نوید ایرانیان · Navid iranian Co. — کلیه حقوق محفوظ است / All rights reserved.</sub>
