# تغییرات | Changelog

همه‌ی تغییرات قابل‌توجه این پروژه در این فایل ثبت می‌شود.
All notable changes to this project are documented in this file.

قالب بر پایه‌ی [Keep a Changelog](https://keepachangelog.com/) و نسخه‌گذاری بر پایه‌ی [SemVer](https://semver.org/) است.
The format is based on [Keep a Changelog](https://keepachangelog.com/) and this project adheres to [Semantic Versioning](https://semver.org/).

---

## [1.0.0] - 2026-07-25

نخستین نسخه‌ی پایدار — بررسی‌کننده‌ی پیش‌نیازهای وردپرس، تک‌فایلی و دوزبانه.
First stable release — a single-file, bilingual WordPress requirements checker.

### افزوده شد | Added

**هسته و ساختار | Core & structure**
- اسکریپت تک‌فایلی `wordpress-check.php` بدون هیچ وابستگی خارجی (بدون CDN و Composer). / Single-file `wordpress-check.php` with zero external dependencies (no CDN, no Composer).
- سازگاری با PHP 5.6 تا 8.5 با نحو قدیمی، تا روی هاست قدیمی هم اجرا شود و نسخه‌ی منسوخ PHP را گزارش کند. / PHP 5.6–8.5 compatibility via legacy syntax, so it runs on old hosts and reports an outdated PHP instead of crashing.
- رابط کاربری کاملاً دوزبانه (فارسی/انگلیسی) با کلید تغییر زبان و جهت خودکار `rtl`/`ltr`. / Fully bilingual UI (FA/EN) with a language toggle and automatic `rtl`/`ltr` direction.

**بررسی‌ها | Checks**
- بررسی هسته: نسخه PHP، دوره‌ی پشتیبانی امنیتی، حالت اجرا (SAPI)، وب‌سرور، درایور دیتابیس و شناسایی وردپرس نصب‌شده. / Core checks: PHP version, security-support window, SAPI, web server, DB driver, and existing-WordPress detection.
- افزونه‌های الزامی: `json`, `mysqli`, `pcre`, `hash`, `filter`, `ctype`, `date`. / Required extensions.
- افزونه‌های توصیه‌شده مطابق «سلامت سایت» وردپرس + OPcache. / Recommended extensions per WordPress Site Health, plus OPcache.
- تنظیمات `php.ini`: حافظه، زمان اجرا، آپلود، `max_input_vars` و موارد دیگر. / `php.ini` settings: memory, execution time, upload, `max_input_vars`, and more.
- محدودیت‌های هاست اشتراکی: `disable_functions`, `open_basedir`, کرون خط فرمان و `mail()`. / Shared-hosting limits: `disable_functions`, `open_basedir`, CLI cron, and `mail()`.
- فایل‌سیستم: ساخت فایل و پوشه، تطابق مالکیت، امنیت `wp-config.php`، فضای دیسک و HTTPS. / Filesystem: file/folder creation, ownership match, `wp-config.php` security, disk space, and HTTPS.

**تست‌های عمیق و دیتابیس | Deep & database tests**
- تست عمیق اختیاری (`?deep=1`): دسترسی به سرورهای وردپرس، تست زنده‌ی mod_rewrite و پیوندهای یکتا، درخواست حلقه‌ای (Loopback) و DNS. / Optional deep tests (`?deep=1`): WordPress-server access, live mod_rewrite/permalink test, loopback request, and DNS.
- تست اتصال دیتابیس: نسخه‌ی MySQL/MariaDB، پشتیبانی `utf8mb4`، موتور InnoDB و سطح دسترسی کاربر با ساخت و حذف واقعی جدول. / Database connection test: MySQL/MariaDB version, `utf8mb4`, InnoDB, and user privileges via a real create-and-drop table.

**خروجی و ابزارها | Output & tools**
- گیج امتیاز آمادگی با وزن‌دهی و چهار وضعیت (قبول/هشدار/مردود/اطلاع). / Weighted readiness gauge with four states (pass/warn/fail/info).
- تولید خودکار متن تیکت پشتیبانی و قطعه‌ی `php.ini` بر اساس نتایج. / Auto-generated support-ticket text and `php.ini` snippet based on results.
- دکمه‌ی چاپ / ذخیره‌ی PDF با استایل مخصوص چاپ. / Print / Save-as-PDF button with dedicated print styles.

**امنیت | Security**
- هدر `noindex, nofollow` برای جلوگیری از ایندکس شدن صفحه. / `noindex, nofollow` header to prevent indexing.
- قفل اختیاری گزارش با `NVD_ACCESS_KEY` و `?key=...`. / Optional report lock via `NVD_ACCESS_KEY` and `?key=...`.
- حذف امن خودکار فایل با دکمه‌ی تأییددار. / Safe self-delete with a confirmed button.

**هویت شرکتی | Branding**
- سربرگ و پاورقی شرکت نوید ایرانیان، فهرست خدمات و شماره‌های تماس، با تولید محتوای دوزبانه. / Navid Iranian header/footer, services list, and contact numbers, with bilingual copy.

---

## انواع تغییرات | Types of changes

- **افزوده شد / Added** — قابلیت‌های جدید / new features
- **تغییر کرد / Changed** — تغییر در رفتار موجود / changes in existing behaviour
- **منسوخ شد / Deprecated** — قابلیت‌هایی که به‌زودی حذف می‌شوند / soon-to-be-removed features
- **حذف شد / Removed** — قابلیت‌های حذف‌شده / removed features
- **رفع شد / Fixed** — رفع اشکال / bug fixes
- **امنیت / Security** — موارد مرتبط با آسیب‌پذیری / vulnerability-related changes

---

<sub>© 1405 / 2026 — شرکت نوید ایرانیان · Navid Iranian Co.</sub>
