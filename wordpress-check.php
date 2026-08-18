<?php
/**
 * =============================================================================
 *  بررسی‌کننده پیش‌نیازهای وردپرس روی هاست اشتراکی  (دوزبانه: فارسی / انگلیسی)
 *  WordPress Shared-Hosting Readiness Checker      (Bilingual: FA / EN)
 * -----------------------------------------------------------------------------
 *  Tool version : 1.1.0
 *  Developer    : شرکت نوید ایرانیان  |  Navid Iranians Co.
 *  Services     : Web Design · SEO · Web Hosting · Domain Registration · Digital Marketing
 *  Phone        : +98 939 556 6652   |   +98 21 9130 3662
 *  Web          : navidiranian.com · navidiranian.co.ir · joomlafarsi.co.ir · cmssupport.ir
 * -----------------------------------------------------------------------------
 *  © 1405 / 2026 — All rights reserved · Navid Iranians Co.
 * -----------------------------------------------------------------------------
 *  Usage / روش استفاده:
 *    1) Upload this file to your site root (public_html).
 *    2) Open in browser:  https://your-domain.com/wordpress-check.php
 *    3) Read the report; send the ready-made text to your host support.
 *    4) When finished, DELETE this file (use the "Delete this file" button).
 *
 *  Switch language with the toggle in the header, or ?lang=en / ?lang=fa
 *  Compatible with PHP 5.6 – 8.5 (intentionally written in legacy syntax so it
 *  also runs on old hosts and can report an outdated PHP instead of crashing).
 * =============================================================================
 */

/* ---------------------------------------------------------------------------
 |  1) Settings
 --------------------------------------------------------------------------- */
define('NVD_ACCESS_KEY', '');          // set a value and open with ?key=... to lock the report
define('NVD_VERSION',    '1.1.0');
define('NVD_COMPANY_FA', 'شرکت نوید ایرانیان');
define('NVD_COMPANY_EN', 'Navid Iranians Co.');
define('NVD_PHONE1',     '+989395566652');
define('NVD_PHONE2',     '+982191303662');
define('NVD_WEB1',       'navidiranian.com');
define('NVD_WEB2',       'navidiranian.co.ir');
define('NVD_WEB3',       'joomlafarsi.co.ir');
define('NVD_WEB4',       'cmssupport.ir');

@ini_set('display_errors', '0');
@error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
@set_time_limit(120);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Early HTTPS detection, needed for the session-cookie "secure" flag below.
$nvdHttpsEarly = (
    (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);

// Session — holds nothing but a one-time CSRF token for the POST forms below.
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_set_cookie_params(0, '/', '', $nvdHttpsEarly, true);
    @session_start();
}

/** Random token generator with fallbacks so it still works on old PHP builds. */
function nvd_random_token($bytes = 32) {
    if (function_exists('random_bytes')) {
        try { return bin2hex(random_bytes($bytes)); } catch (Exception $e) {}
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        $r = @openssl_random_pseudo_bytes($bytes);
        if ($r !== false) return bin2hex($r);
    }
    $s = '';
    for ($i = 0; $i < $bytes; $i++) $s .= chr(mt_rand(0, 255));
    return bin2hex($s);
}
function nvd_csrf_token() {
    if (empty($_SESSION['nvd_csrf'])) $_SESSION['nvd_csrf'] = nvd_random_token(32);
    return $_SESSION['nvd_csrf'];
}
function nvd_csrf_check() {
    $tok = isset($_POST['nvd_csrf']) ? (string)$_POST['nvd_csrf'] : '';
    return ($tok !== '' && !empty($_SESSION['nvd_csrf']) && hash_equals($_SESSION['nvd_csrf'], $tok));
}

// Language
$LANG = 'fa';
if (isset($_GET['lang']) && $_GET['lang'] === 'en') $LANG = 'en';
$IS_FA = ($LANG === 'fa');
$DIR   = $IS_FA ? 'rtl' : 'ltr';

// Access lock — constant-time comparison so the key check leaks no timing signal.
$nvdLocked = (NVD_ACCESS_KEY !== '');
if ($nvdLocked) {
    $k = isset($_GET['key']) ? (string)$_GET['key'] : '';
    if (!hash_equals(NVD_ACCESS_KEY, $k)) {
        header('HTTP/1.1 403 Forbidden');
        echo '<meta charset="utf-8"><div style="font:16px Tahoma;padding:40px">Access denied.</div>';
        exit;
    }
}

/* ---------------------------------------------------------------------------
 |  2) i18n helpers
 --------------------------------------------------------------------------- */
function T($fa, $en) { global $IS_FA; return $IS_FA ? $fa : $en; }

/** bilingual value: pass string (same for both) or array('fa'=>.., 'en'=>..) */
function LV($v) {
    global $IS_FA;
    if (is_array($v)) return $IS_FA ? (isset($v['fa']) ? $v['fa'] : '') : (isset($v['en']) ? $v['en'] : '');
    return (string)$v;
}

function nvd_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nvd_get($a, $k, $d = null) { return (is_array($a) && isset($a[$k])) ? $a[$k] : $d; }

function nvd_num($s) {
    global $IS_FA;
    if (!$IS_FA) return (string)$s;
    $en = array('0','1','2','3','4','5','6','7','8','9');
    $fa = array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹');
    return str_replace($en, $fa, (string)$s);
}

function nvd_bytes($val) {
    $val = trim((string)$val);
    if ($val === '') return 0;
    if ($val === '-1') return -1;
    $last = strtolower(substr($val, -1));
    $num  = (float)$val;
    if ($last === 'g') $num *= 1073741824;
    elseif ($last === 'm') $num *= 1048576;
    elseif ($last === 'k') $num *= 1024;
    return (float)$num;
}
function nvd_mb($b) { return ($b < 0) ? -1 : round($b / 1048576, 1); }
function nvd_hsize($b) {
    if ($b < 0) return T('نامحدود', 'Unlimited');
    $u = array('B','KB','MB','GB','TB'); $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}
function nvd_ini($k) { $v = @ini_get($k); return ($v === false || $v === null) ? '' : (string)$v; }
function nvd_ini_on($k) { $v = strtolower(trim(nvd_ini($k))); return ($v === '1' || $v === 'on' || $v === 'true' || $v === 'yes'); }
function nvd_ext($n) { return extension_loaded($n); }
function nvd_func($n) {
    if (!function_exists($n)) return false;
    $d = array_map('trim', explode(',', strtolower(nvd_ini('disable_functions'))));
    return !in_array(strtolower($n), $d, true);
}
function nvd_onoff($b) { return $b ? T('فعال', 'Enabled') : T('غیرفعال', 'Disabled'); }

/**
 * Build a check item. label / actual / expected / note accept bilingual arrays.
 * status: pass | warn | fail | info
 */
function nvd_item($label, $status, $actual, $expected, $note = '', $weight = 1, $critical = false, $fix = '') {
    return array(
        'label' => $label, 'status' => $status, 'actual' => $actual, 'expected' => $expected,
        'note' => $note, 'weight' => $weight, 'critical' => $critical, 'fix' => $fix,
    );
}

/* ---------------------------------------------------------------------------
 |  3) WordPress reference values  (source: wordpress.org/about/requirements)
 --------------------------------------------------------------------------- */
$WP = array(
    'php_min'     => '7.4.0',  'php_rec'    => '8.3.0',
    'mysql_min'   => '5.5.5',  'mysql_rec'  => '8.0',
    'mariadb_min' => '10.4',   'mariadb_rec'=> '10.11',
    'memory_min'  => 64,       'memory_rec' => 256,   // MB
    'upload_min'  => 8,        'upload_rec' => 64,    // MB
    'exec_min'    => 30,       'exec_rec'   => 60,    // s
    'inputvars_min'=> 1000,    'inputvars_rec'=> 3000,
    'disk_min'    => 500,      'disk_rec'   => 2048,  // MB (WP + media + updates)
);

$PHP_EOL_MAP = array(
    '5.6'=>'2018-12-31','7.0'=>'2019-01-10','7.1'=>'2019-12-01','7.2'=>'2020-11-30',
    '7.3'=>'2021-12-06','7.4'=>'2022-11-28','8.0'=>'2023-11-26','8.1'=>'2025-12-31',
    '8.2'=>'2026-12-31','8.3'=>'2027-12-31','8.4'=>'2028-12-31','8.5'=>'2029-12-31',
);

/* ---------------------------------------------------------------------------
 |  4) Environment basics
 --------------------------------------------------------------------------- */
$phpVersion  = PHP_VERSION;
$phpBranch   = implode('.', array_slice(explode('.', $phpVersion), 0, 2));
$sapi        = php_sapi_name();
$serverSoft  = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
$isLiteSpeed = (stripos($serverSoft, 'litespeed') !== false || stripos($sapi, 'litespeed') !== false || stripos($sapi, 'lsapi') !== false);
$isApache    = (stripos($serverSoft, 'apache') !== false);
$isNginx     = (stripos($serverSoft, 'nginx') !== false);
$isIIS       = (stripos($serverSoft, 'iis') !== false);
$isHttps     = $nvdHttpsEarly;
$hostName    = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$deep        = (isset($_GET['deep']) && $_GET['deep'] === '1');
$here        = __DIR__;

/**
 * Host to use for the server's own outbound self-requests (mod_rewrite probe,
 * loopback test). Prefers SERVER_NAME — fixed by the vhost config — over the
 * client-supplied HTTP_HOST header, so a spoofed Host header cannot redirect
 * these outbound requests to an attacker-chosen target (SSRF).
 */
function nvd_safe_self_host($fallback) {
    $candidates = array();
    if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== '') $candidates[] = $_SERVER['SERVER_NAME'];
    if ($fallback !== '') $candidates[] = $fallback;
    foreach ($candidates as $c) {
        $hostOnly = preg_replace('/:\d+$/', '', $c);
        if (preg_match('/^[A-Za-z0-9]([A-Za-z0-9\-\.]{0,251}[A-Za-z0-9])?$/', $hostOnly)) return $c;
    }
    return 'localhost';
}
$selfHost = nvd_safe_self_host($hostName);

$sections = array();

/* ===========================================================================
 |  A — Core, versions, engine
 =========================================================================== */
$A = array();

$phpOk  = version_compare($phpVersion, $WP['php_min'], '>=');
$phpRec = version_compare($phpVersion, $WP['php_rec'], '>=');
$A[] = nvd_item(
    T('نسخه PHP', 'PHP version'),
    $phpOk ? ($phpRec ? 'pass' : 'warn') : 'fail',
    $phpVersion,
    T('حداقل ' . $WP['php_min'] . ' — پیشنهادی ' . $WP['php_rec'] . '+',
      'Min ' . $WP['php_min'] . ' — recommended ' . $WP['php_rec'] . '+'),
    $phpOk ? ($phpRec
        ? T('نسخه PHP کاملاً مناسب وردپرس است.', 'PHP version is a solid match for WordPress.')
        : T('وردپرس اجرا می‌شود، اما نسخه ۸.۳ یا بالاتر سرعت و امنیت بیشتری می‌دهد؛ افزونه‌های مدرن به آن نیاز دارند.',
            'WordPress runs, but 8.3+ is faster, safer, and required by modern plugins.'))
        : T('نسخه PHP پایین‌تر از حداقل وردپرس است. از cPanel → Select PHP Version نسخه را ارتقا دهید.',
            'PHP is below the WordPress floor. Upgrade via cPanel → Select PHP Version.'),
    6, !$phpOk,
    $phpOk ? '' : T('ارتقای PHP به ' . $WP['php_rec'] . ' یا بالاتر', 'Upgrade PHP to ' . $WP['php_rec'] . ' or newer')
);

$eolDate = nvd_get($PHP_EOL_MAP, $phpBranch, null);
if ($eolDate !== null) {
    $expired = (strtotime($eolDate) < time());
    $A[] = nvd_item(
        T('پشتیبانی امنیتی PHP', 'PHP security support'),
        $expired ? 'fail' : 'pass',
        'PHP ' . $phpBranch . ' — ' . T('تا ', 'until ') . $eolDate,
        T('نسخه‌ای که هنوز وصله می‌گیرد', 'A branch still receiving patches'),
        $expired
            ? T('این شاخه‌ی PHP دیگر وصله‌ی امنیتی نمی‌گیرد؛ ماندن روی آن یعنی آسیب‌پذیری اصلاح‌نشده.',
                'This PHP branch no longer receives security fixes; staying here means unpatched holes.')
            : T('این شاخه هنوز در دوره‌ی پشتیبانی امنیتی رسمی PHP است.',
                'This branch is still within official PHP security support.'),
        3, false,
        $expired ? T('مهاجرت به شاخه‌ی پشتیبانی‌شده‌ی PHP', 'Move to a supported PHP branch') : ''
    );
}

$sapiGood = (stripos($sapi, 'fpm') !== false || $isLiteSpeed);
$A[] = nvd_item(
    T('حالت اجرای PHP (SAPI)', 'PHP runtime (SAPI)'),
    'info', $sapi,
    T('php-fpm یا LiteSpeed LSAPI (بهینه)', 'php-fpm or LiteSpeed LSAPI (optimal)'),
    $sapiGood
        ? T('حالت اجرای فعلی برای وردپرس بهینه است.', 'Current runtime is optimal for WordPress.')
        : T('در حالت CGI/mod_php ممکن است مالکیت فایل‌ها و کارایی مشکل‌ساز شود.',
            'CGI/mod_php can cause file-ownership and performance issues.'),
    0
);

$wsStatus = ($isApache || $isLiteSpeed || $isNginx || $isIIS) ? 'pass' : 'info';
$A[] = nvd_item(
    T('وب‌سرور', 'Web server'), $wsStatus, $serverSoft,
    'Apache 2.4+ / LiteSpeed / Nginx / IIS',
    $isNginx
        ? T('روی Nginx فایل htaccess کار نمی‌کند؛ برای پیوندهای یکتا باید قواعد rewrite در کانفیگ سرور باشد.',
            'On Nginx, .htaccess is ignored; permalinks need rewrite rules in the server config.')
        : T('وب‌سرور شناسایی‌شده با وردپرس سازگار است.', 'Detected web server is compatible with WordPress.'),
    2
);

// DB driver
$drvMysqli   = nvd_ext('mysqli');
$drvPdoMysql = class_exists('PDO') ? in_array('mysql', PDO::getAvailableDrivers(), true) : false;
$dbDrvOk     = ($drvMysqli || $drvPdoMysql);
$drvList     = array();
if ($drvMysqli)   $drvList[] = 'mysqli';
if ($drvPdoMysql) $drvList[] = 'pdo_mysql';
if (nvd_ext('mysqlnd')) $drvList[] = 'mysqlnd';
$A[] = nvd_item(
    T('درایور دیتابیس', 'Database driver'),
    $dbDrvOk ? 'pass' : 'fail',
    $drvList ? implode(' , ', $drvList) : T('هیچ‌کدام', 'None'),
    T('افزونه mysqli (وردپرس به آن نیاز دارد)', 'mysqli extension (required by WordPress)'),
    $dbDrvOk
        ? T('امکان اتصال وردپرس به دیتابیس فراهم است.', 'WordPress can connect to the database.')
        : T('وردپرس بدون mysqli اصلاً نصب نمی‌شود.', 'WordPress will not install without mysqli.'),
    6, !$dbDrvOk,
    $dbDrvOk ? '' : T('فعال‌سازی افزونه mysqli', 'Enable the mysqli extension')
);

// Existing WP install?
$existingWp = '';
$verFile = $here . '/wp-includes/version.php';
if (@is_file($verFile)) {
    $src = @file_get_contents($verFile);
    if ($src && preg_match('/\$wp_version\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $m)) $existingWp = $m[1];
}
if ($existingWp !== '') {
    $A[] = nvd_item(
        T('وردپرس نصب‌شده در این مسیر', 'WordPress found in this path'),
        'info', 'WordPress ' . $existingWp, '—',
        T('روی این مسیر وردپرس نصب است؛ گزارش زیر وضعیت میزبانی همین سایت را نشان می‌دهد.',
          'WordPress is installed here; the report below reflects this site\'s hosting.'),
        0
    );
}

$sections[] = array(
    'id' => 'core',
    'title' => T('هسته، نسخه‌ها و موتور اجرا', 'Core, versions & engine'),
    'desc'  => T('اولین چیزهایی که وردپرس برای اجرا لازم دارد: نسخه PHP، وب‌سرور و درایور دیتابیس.',
                 'The first things WordPress needs to run: PHP version, web server, and DB driver.'),
    'items' => $A
);

/* ===========================================================================
 |  B — Required PHP extensions
 =========================================================================== */
$B = array();
$reqExts = array(
    'json'    => array(T('قلب ارتباط داخلی و REST API وردپرس — بدون آن وردپرس اجرا نمی‌شود',
                         'Core of WP internals and the REST API — WordPress will not run without it'), true),
    'mysqli'  => array(T('اتصال به دیتابیس MySQL/MariaDB', 'Connection to the MySQL/MariaDB database'), true),
    'pcre'    => array(T('موتور عبارات باقاعده — در سراسر هسته استفاده می‌شود', 'Regex engine — used throughout core'), true),
    'hash'    => array(T('هش رمز عبور و کوکی‌های ورود', 'Password hashing and login cookies'), true),
    'filter'  => array(T('اعتبارسنجی و پاک‌سازی ورودی‌ها', 'Input validation and sanitisation'), true),
    'ctype'   => array(T('بررسی نوع کاراکتر', 'Character-type checks'), true),
    'date'    => array(T('توابع تاریخ و زمان', 'Date and time functions'), true),
);
foreach ($reqExts as $ext => $meta) {
    $has = nvd_ext($ext);
    $B[] = nvd_item(
        T('افزونه ', 'Extension ') . $ext,
        $has ? 'pass' : 'fail',
        $has ? T('نصب است', 'Loaded') : T('نصب نیست', 'Missing'),
        T('الزامی', 'Required'),
        $meta[0], 3, true,
        $has ? '' : T('فعال‌سازی افزونه PHP: ', 'Enable PHP extension: ') . $ext
    );
}
$sections[] = array(
    'id' => 'ext-req',
    'title' => T('افزونه‌های الزامی PHP', 'Required PHP extensions'),
    'desc'  => T('نبود هرکدام یعنی وردپرس اجرا نمی‌شود. در cPanel از Select PHP Version → Extensions فعال می‌شوند.',
                 'Missing any of these means WordPress will not run. Enable in cPanel → Select PHP Version → Extensions.'),
    'items' => $B
);

/* ===========================================================================
 |  C — Recommended PHP extensions (WordPress Site Health list)
 =========================================================================== */
$C = array();
$recExts = array(
    'mbstring'  => array(T('پشتیبانی صحیح از متن فارسی و عربی — عملاً برای سایت فارسی حیاتی است',
                           'Correct handling of Persian/Arabic text — effectively vital for a Persian site'), 3),
    'curl'      => array(T('به‌روزرسانی هسته، نصب افزونه و قالب، ارتباط با API‌ها',
                           'Core updates, plugin/theme installs, API communication'), 3),
    'gd'        => array(T('پردازش و تغییر اندازه‌ی تصویر — بدون آن بندانگشتی ساخته نمی‌شود',
                           'Image processing/resizing — no thumbnails without it'), 3),
    'imagick'   => array(T('کیفیت بالاتر تصویر و پشتیبانی از فرمت‌های بیشتر (مکمل GD)',
                           'Higher image quality and more formats (complements GD)'), 2),
    'openssl'   => array(T('اتصال HTTPS، SMTP امن و رمزنگاری', 'HTTPS, secure SMTP, and encryption'), 3),
    'dom'       => array(T('پردازش HTML و XML در ویرایشگر و درون‌ریزی', 'HTML/XML processing in editor and importers'), 2),
    'fileinfo'  => array(T('تشخیص نوع فایل هنگام آپلود رسانه', 'Detecting file type on media upload'), 3),
    'zip'       => array(T('نصب و به‌روزرسانی بسته‌های افزونه و قالب (ZIP)', 'Installing/updating plugin & theme ZIP packages'), 3),
    'exif'      => array(T('خواندن اطلاعات تصویر و چرخش خودکار', 'Reading image metadata and auto-rotation'), 1),
    'intl'      => array(T('تاریخ، زبان و مرتب‌سازی چندزبانه', 'Multilingual date, locale and sorting'), 2),
    'iconv'     => array(T('تبدیل کدگذاری متن', 'Text encoding conversion'), 2),
    'simplexml' => array(T('خواندن فیدها و فایل‌های XML', 'Reading feeds and XML files'), 2),
    'xml'       => array(T('پردازش XML و درون‌ریزی/برون‌بری', 'XML processing and import/export'), 2),
    'sodium'    => array(T('رمزنگاری مدرن برای امضای به‌روزرسانی‌ها', 'Modern crypto for signed updates'), 2),
    'zlib'      => array(T('فشرده‌سازی خروجی و بسته‌ها', 'Output and package compression'), 2),
);
foreach ($recExts as $ext => $meta) {
    $has = nvd_ext($ext);
    $C[] = nvd_item(
        T('افزونه ', 'Extension ') . $ext,
        $has ? 'pass' : 'warn',
        $has ? T('نصب است', 'Loaded') : T('نصب نیست', 'Missing'),
        T('توصیه‌شده', 'Recommended'),
        $meta[0], $meta[1], false,
        $has ? '' : T('فعال‌سازی افزونه PHP: ', 'Enable PHP extension: ') . $ext
    );
}
// OPcache
$opOn = nvd_ini_on('opcache.enable') || function_exists('opcache_get_status');
$C[] = nvd_item(
    'OPcache', $opOn ? 'pass' : 'warn', nvd_onoff($opOn), T('فعال (کارایی)', 'Enabled (performance)'),
    $opOn ? T('کد PHP کش می‌شود؛ سرعت وردپرس به‌مراتب بهتر است.', 'PHP code is cached; WordPress is markedly faster.')
          : T('بدون OPcache هر درخواست دوباره کل کد را کامپایل می‌کند؛ سایت کند می‌ماند.',
              'Without OPcache every request recompiles all code; the site stays slow.'),
    2, false, $opOn ? '' : T('فعال‌سازی OPcache در تنظیمات PHP', 'Enable OPcache in PHP settings')
);
$sections[] = array(
    'id' => 'ext-rec',
    'title' => T('افزونه‌های توصیه‌شده', 'Recommended PHP extensions'),
    'desc'  => T('همان فهرستی که وردپرس در «سلامت سایت» بررسی می‌کند. نبودشان قابلیت‌ها را لنگ می‌کند.',
                 'The same list WordPress checks in “Site Health”. Missing ones cripple features.'),
    'items' => $C
);

/* ===========================================================================
 |  D — php.ini settings
 =========================================================================== */
$D = array();
$iniFixes = array();

// memory_limit
$memRaw = nvd_ini('memory_limit');
$memB   = nvd_bytes($memRaw);
$memMB  = ($memB < 0) ? 99999 : nvd_mb($memB);
if ($memMB >= $WP['memory_rec'])      { $st='pass'; $nt=array('fa'=>'حافظه برای افزونه‌ها، قالب‌ها و به‌روزرسانی کافی است.','en'=>'Memory is enough for plugins, themes and updates.'); }
elseif ($memMB >= $WP['memory_min'])  { $st='warn'; $nt=array('fa'=>'برای سایت ساده کافی است، اما با چند افزونه یا ووکامرس خطای حافظه می‌گیرید.','en'=>'Fine for a simple site, but a few plugins or WooCommerce will hit memory errors.'); }
else                                  { $st='fail'; $nt=array('fa'=>'کمتر از حداقل وردپرس؛ پیشخوان با خطای سفید متوقف می‌شود.','en'=>'Below the WordPress floor; the dashboard breaks with a white screen.'); }
if ($st !== 'pass') $iniFixes[] = 'memory_limit = 256M';
$D[] = nvd_item('memory_limit', $st, ($memB < 0 ? T('نامحدود','Unlimited') : $memRaw),
    T('256M یا بیشتر','256M or more'), $nt, 5, ($st==='fail'), ($st==='pass'?'':'memory_limit = 256M'));

// max_execution_time
$maxExec = (int)nvd_ini('max_execution_time');
if ($maxExec === 0)                   { $st='pass'; $nt=array('fa'=>'بدون محدودیت زمانی — مناسب به‌روزرسانی و درون‌ریزی.','en'=>'No time limit — good for updates and imports.'); }
elseif ($maxExec >= $WP['exec_rec'])  { $st='pass'; $nt=array('fa'=>'زمان اجرا برای به‌روزرسانی و درون‌ریزی کافی است.','en'=>'Execution time is enough for updates and imports.'); }
elseif ($maxExec >= $WP['exec_min'])  { $st='warn'; $nt=array('fa'=>'برای کار روزمره کافی است، اما درون‌ریزی بزرگ یا بکاپ ممکن است Timeout شود.','en'=>'OK for daily use, but large imports/backups may time out.'); }
else                                  { $st='fail'; $nt=array('fa'=>'زمان اجرا خیلی کم است؛ نصب و به‌روزرسانی نیمه‌کاره می‌ماند.','en'=>'Too short; installs/updates will be left half-done.'); }
if ($st !== 'pass') $iniFixes[] = 'max_execution_time = 300';
$D[] = nvd_item('max_execution_time', $st, ($maxExec===0?T('نامحدود','Unlimited'):$maxExec.T(' ثانیه',' s')),
    T('حداقل ۳۰ — پیشنهادی ۶۰+','Min 30 — recommended 60+'), $nt, 4, ($st==='fail'), ($st==='pass'?'':'max_execution_time = 300'));

// upload_max_filesize
$upB=nvd_bytes(nvd_ini('upload_max_filesize')); $upMB=nvd_mb($upB);
if ($upMB >= $WP['upload_rec'])     { $st='pass'; $nt=array('fa'=>'برای آپلود قالب، افزونه و رسانه‌ی حجیم کافی است.','en'=>'Enough for large themes, plugins and media.'); }
elseif ($upMB >= $WP['upload_min']) { $st='warn'; $nt=array('fa'=>'فایل‌های بزرگ (قالب کامل، ویدیو، بکاپ) آپلود نمی‌شوند.','en'=>'Large files (full themes, video, backups) will not upload.'); }
else                                { $st='fail'; $nt=array('fa'=>'محدودیت آپلود بسیار کم است؛ حتی افزونه‌ها هم آپلود نمی‌شوند.','en'=>'Upload limit is very low; even plugins will not upload.'); }
if ($st !== 'pass') $iniFixes[] = 'upload_max_filesize = 64M';
$D[] = nvd_item('upload_max_filesize', $st, nvd_ini('upload_max_filesize'),
    T('64M یا بیشتر','64M or more'), $nt, 4, ($st==='fail'), ($st==='pass'?'':'upload_max_filesize = 64M'));

// post_max_size
$postB=nvd_bytes(nvd_ini('post_max_size')); $postMB=nvd_mb($postB);
if ($postB > 0 && $postB < $upB)      { $st='fail'; $nt=array('fa'=>'post_max_size از upload_max_filesize کمتر است؛ آپلود فایل بزرگ بی‌صدا شکست می‌خورد.','en'=>'post_max_size is smaller than upload_max_filesize; large uploads fail silently.'); }
elseif ($postMB >= $WP['upload_rec']) { $st='pass'; $nt=array('fa'=>'حجم مجاز ارسال فرم متناسب با آپلود است.','en'=>'POST size matches the upload limit.'); }
else                                  { $st='warn'; $nt=array('fa'=>'برای صفحه‌سازها و فرم‌های سنگین کم است.','en'=>'Too low for page builders and heavy forms.'); }
if ($st !== 'pass') $iniFixes[] = 'post_max_size = 64M';
$D[] = nvd_item('post_max_size', $st, nvd_ini('post_max_size'),
    T('مساوی یا بیشتر از upload و حداقل 64M','≥ upload_max_filesize and at least 64M'), $nt, 4, ($st==='fail'), ($st==='pass'?'':'post_max_size = 64M'));

// max_input_vars
$miv=(int)nvd_ini('max_input_vars'); if ($miv===0) $miv=1000;
if ($miv >= $WP['inputvars_rec'])     { $st='pass'; $nt=array('fa'=>'منوهای بزرگ و تنظیمات افزونه‌ها کامل ذخیره می‌شوند.','en'=>'Large menus and plugin settings save completely.'); }
elseif ($miv >= $WP['inputvars_min']) { $st='warn'; $nt=array('fa'=>'تنظیم پنهانِ خطرناک: منوی بزرگ یا تنظیمات صفحه‌ساز ممکن است بی‌خبر ناقص ذخیره شود.','en'=>'A sneaky one: big menus or builder settings may save incompletely without warning.'); }
else                                  { $st='fail'; $nt=array('fa'=>'مقدار بسیار پایین است؛ ذخیره‌ی تنظیمات ناقص می‌شود.','en'=>'Very low; settings will save incompletely.'); }
if ($st !== 'pass') $iniFixes[] = 'max_input_vars = 3000';
$D[] = nvd_item('max_input_vars', $st, $miv, T('3000 یا بیشتر','3000 or more'), $nt, 4, false, ($st==='pass'?'':'max_input_vars = 3000'));

// max_input_time
$mit=(int)nvd_ini('max_input_time');
$D[] = nvd_item('max_input_time',
    ($mit===-1 || $mit>=60) ? 'pass' : 'warn',
    ($mit===-1 ? T('نامحدود','Unlimited') : $mit.T(' ثانیه',' s')),
    T('حداقل ۶۰','At least 60'),
    ($mit===-1 || $mit>=60)
        ? array('fa'=>'زمان دریافت داده‌ی فرم کافی است.','en'=>'Enough time to receive form data.')
        : array('fa'=>'آپلود فایل بزرگ ممکن است پیش از اتمام قطع شود.','en'=>'Large uploads may cut off before finishing.'),
    2, false, ($mit===-1 || $mit>=60) ? '' : 'max_input_time = 60');

// file_uploads
$fu = nvd_ini_on('file_uploads');
$D[] = nvd_item('file_uploads', $fu?'pass':'fail', nvd_onoff($fu), T('فعال (On)','Enabled (On)'),
    $fu ? array('fa'=>'آپلود رسانه و نصب افزونه ممکن است.','en'=>'Media upload and plugin install work.')
        : array('fa'=>'بدون این گزینه نه افزونه‌ای نصب می‌شود نه تصویری آپلود می‌شود.','en'=>'Without this, no plugin installs and no image uploads.'),
    5, true, $fu?'':'file_uploads = On');
if (!$fu) $iniFixes[] = 'file_uploads = On';

// allow_url_fopen
$aufo=nvd_ini_on('allow_url_fopen'); $hasCurl=nvd_ext('curl');
$D[] = nvd_item('allow_url_fopen', ($aufo||$hasCurl)?'pass':'warn', nvd_onoff($aufo),
    T('فعال یا وجود cURL','Enabled or cURL present'),
    ($aufo||$hasCurl)
        ? array('fa'=>'وردپرس راه ارتباطی با بیرون برای به‌روزرسانی دارد.','en'=>'WordPress has an outbound path for updates.')
        : array('fa'=>'نه allow_url_fopen فعال است نه cURL؛ به‌روزرسانی و نصب از مخزن کار نمی‌کند.','en'=>'Neither allow_url_fopen nor cURL; updates and repo installs will not work.'),
    3, false, ($aufo||$hasCurl)?'':T('فعال‌سازی cURL یا allow_url_fopen','Enable cURL or allow_url_fopen'));

// display_errors
$de=nvd_ini_on('display_errors');
$D[] = nvd_item('display_errors', $de?'warn':'pass', nvd_onoff($de), T('غیرفعال روی سایت زنده','Off on production'),
    $de ? array('fa'=>'نمایش خطاها مسیر فایل و اطلاعات سرور را لو می‌دهد؛ روی سایت زنده باید خاموش باشد.','en'=>'Showing errors leaks paths and server info; must be off on a live site.')
        : array('fa'=>'خطاها به بازدیدکننده نشان داده نمی‌شود.','en'=>'Errors are hidden from visitors.'),
    2, false, $de?'display_errors = Off':'');
if ($de) $iniFixes[] = 'display_errors = Off';

// date.timezone
$tz=nvd_ini('date.timezone');
$D[] = nvd_item('date.timezone', ($tz!=='')?'pass':'warn', ($tz!==''?$tz:T('تعیین نشده','Not set')),
    T('مثلاً Asia/Tehran','e.g. Asia/Tehran'),
    ($tz!=='') ? array('fa'=>'منطقه‌ی زمانی سرور مشخص است.','en'=>'Server timezone is set.')
               : array('fa'=>'بدون آن، زمان انتشار نوشته‌ها و زمان‌بندی اشتباه ثبت می‌شود.','en'=>'Without it, post scheduling and timestamps will be wrong.'),
    1, false, ($tz!=='')?'':'date.timezone = Asia/Tehran');
if ($tz==='') $iniFixes[] = 'date.timezone = Asia/Tehran';

$sections[] = array(
    'id' => 'ini',
    'title' => T('تنظیمات php.ini', 'php.ini settings'),
    'desc'  => T('این مقادیر در cPanel → MultiPHP INI Editor یا فایل php.ini کاربر قابل تغییرند.',
                 'These are editable in cPanel → MultiPHP INI Editor or a user php.ini file.'),
    'items' => $D
);

/* ===========================================================================
 |  E — Shared-hosting limits
 =========================================================================== */
$E = array();

// disable_functions
$disabledRaw  = nvd_ini('disable_functions');
$disabledList = array_filter(array_map('trim', explode(',', $disabledRaw)));
$watch = array(
    'ini_set'        => 'fail','set_time_limit' => 'fail','error_reporting'=> 'warn',
    'fopen'          => 'fail','file_get_contents'=>'warn','fsockopen'    => 'warn',
    'proc_open'      => 'warn','exec'           => 'warn','symlink'        => 'warn',
);
$blocked = array(); $blockedSeverity = 'pass';
foreach ($watch as $fn => $sev) {
    if (in_array($fn, $disabledList, true)) {
        $blocked[] = $fn;
        if ($sev === 'fail') $blockedSeverity = 'fail';
        elseif ($blockedSeverity !== 'fail') $blockedSeverity = 'warn';
    }
}
$E[] = nvd_item(
    T('توابع غیرفعال‌شده (disable_functions)', 'Disabled functions (disable_functions)'),
    $blockedSeverity,
    $disabledRaw !== '' ? $disabledRaw : T('هیچ تابعی غیرفعال نیست', 'None disabled'),
    T('ini_set و set_time_limit نباید بسته باشند', 'ini_set and set_time_limit must not be disabled'),
    empty($blocked)
        ? array('fa'=>'هیچ‌کدام از توابع مهم وردپرس مسدود نشده است.','en'=>'No functions WordPress relies on are blocked.')
        : array('fa'=>'این توابعِ موردنیاز مسدود شده‌اند: '.implode(' , ',$blocked).' — از هاست بخواهید حداقل ini_set و set_time_limit را آزاد کند.',
                'en'=>'These needed functions are blocked: '.implode(' , ',$blocked).' — ask your host to free at least ini_set and set_time_limit.'),
    3, false,
    empty($blocked) ? '' : T('آزادسازی توابع: ','Unblock functions: ').implode(', ',$blocked)
);

// open_basedir
$obd=nvd_ini('open_basedir');
$E[] = nvd_item('open_basedir', ($obd==='')?'pass':'warn', ($obd!==''?$obd:T('محدودیتی ندارد','No restriction')),
    T('بدون محدودیت یا شامل مسیر سایت و پوشه‌ی موقت','Unrestricted, or includes site path and temp dir'),
    ($obd==='') ? array('fa'=>'دسترسی فایل‌سیستم محدود نشده است.','en'=>'Filesystem access is not restricted.')
                : array('fa'=>'اگر پوشه‌ی موقت و مسیر سایت در این لیست نباشند، آپلود و باز کردن بسته شکست می‌خورد.','en'=>'If temp and site paths are missing here, uploads and package extraction fail.'),
    2);

// PHP CLI (cron)
$cliVersion = '';
if (nvd_func('exec')) { $o=array(); @exec('php -v 2>&1',$o); if(!empty($o[0])&&preg_match('/PHP\s+([\d\.]+)/i',$o[0],$m)) $cliVersion=$m[1]; }
$E[] = nvd_item(
    T('PHP خط فرمان (برای کرون)','PHP CLI (for cron)'),
    ($cliVersion!=='') ? (version_compare($cliVersion,$WP['php_min'],'>=')?'pass':'warn') : 'info',
    ($cliVersion!==''?'PHP '.$cliVersion:T('قابل تشخیص نیست','Not detectable')),
    T('هم‌نسخه با PHP وب','Same version as web PHP'),
    ($cliVersion!=='')
        ? (version_compare($cliVersion,$WP['php_min'],'>=')
            ? array('fa'=>'کرون‌جاب سیستمی برای وردپرس قابل استفاده است (توصیه: DISABLE_WP_CRON).','en'=>'System cron is usable for WordPress (tip: set DISABLE_WP_CRON).')
            : array('fa'=>'نسخه‌ی CLI از وب قدیمی‌تر است؛ در کرون‌جاب مسیر کامل باینری صحیح PHP را بنویسید.','en'=>'CLI PHP is older than web PHP; use the full path to the correct PHP binary in cron.'))
        : array('fa'=>'اجرای دستور روی این هاست مجاز نیست. اگر کرون ندارید، از کرون داخلی وردپرس یا سرویس Web-Cron استفاده کنید.','en'=>'Shell exec is not allowed here. If you have no cron, use WP-Cron or a Web-Cron service.'),
    1);

// mail()
$E[] = nvd_item(T('تابع mail()','mail() function'), nvd_func('mail')?'pass':'warn',
    nvd_func('mail')?T('در دسترس','Available'):T('غیرفعال','Disabled'),
    T('در دسترس یا استفاده از SMTP','Available or use SMTP'),
    nvd_func('mail')
        ? array('fa'=>'ارسال ایمیل با تابع داخلی ممکن است، اما برای تحویل بهتر افزونه‌ی SMTP توصیه می‌شود.','en'=>'Built-in mail works, but an SMTP plugin gives better delivery.')
        : array('fa'=>'ایمیل‌های وردپرس (بازیابی رمز، اعلان) ارسال نمی‌شود؛ افزونه‌ی SMTP نصب کنید.','en'=>'WordPress emails (password reset, notifications) will not send; install an SMTP plugin.'),
    2, false, nvd_func('mail')?'':T('نصب افزونه SMTP','Install an SMTP plugin'));

$sections[] = array(
    'id' => 'limits',
    'title' => T('محدودیت‌های هاست اشتراکی', 'Shared-hosting limits'),
    'desc'  => T('همان تنظیماتی که میزبان‌ها برای امنیت می‌گذارند و باعث خطاهای مبهم وردپرس می‌شوند.',
                 'The security limits hosts apply that cause WordPress\'s most cryptic errors.'),
    'items' => $E
);

/* ===========================================================================
 |  F — Filesystem, permissions & space
 =========================================================================== */
$F = array();

$testFile = $here . '/nvd_wtest_' . mt_rand(1000,9999) . '.tmp';
$canWriteFile = @file_put_contents($testFile,'ok') !== false;
$fileOwner = $canWriteFile ? @fileowner($testFile) : null;
$filePerm  = $canWriteFile ? substr(sprintf('%o', @fileperms($testFile)), -4) : '';
if ($canWriteFile) @unlink($testFile);

$F[] = nvd_item(T('امکان ایجاد فایل در مسیر سایت','Can create files in site path'),
    $canWriteFile?'pass':'fail', $canWriteFile?T('موفق','Success'):T('ناموفق','Failed'), T('قابل نوشتن','Writable'),
    $canWriteFile ? array('fa'=>'وردپرس می‌تواند wp-config.php و فایل‌های آپلود را بسازد.','en'=>'WordPress can create wp-config.php and upload files.')
                  : array('fa'=>'بدون اجازه‌ی نوشتن، نصب و آپلود شکست می‌خورد. مجوز پوشه را 755 و مالکیت را روی کاربر هاست بگذارید.','en'=>'Without write access, install and uploads fail. Set folder to 755 and owner to your hosting user.'),
    6, !$canWriteFile, $canWriteFile?'':T('اصلاح مجوز و مالکیت پوشه','Fix folder permissions/ownership'));

$testDir = $here . '/nvd_dtest_' . mt_rand(1000,9999);
$canMkdir = @mkdir($testDir, 0755);
if ($canMkdir) @rmdir($testDir);
$F[] = nvd_item(T('امکان ایجاد پوشه','Can create folders'),
    $canMkdir?'pass':'fail', $canMkdir?T('موفق','Success'):T('ناموفق','Failed'), T('مجاز','Allowed'),
    $canMkdir ? array('fa'=>'ساخت پوشه‌های wp-content/uploads بر اساس ماه ممکن است.','en'=>'Monthly wp-content/uploads folders can be created.')
              : array('fa'=>'وردپرس نمی‌تواند پوشه‌های رسانه را بسازد.','en'=>'WordPress cannot create media folders.'),
    5, !$canMkdir, $canMkdir?'':T('اصلاح مجوز پوشه','Fix folder permissions'));

$dirOwner = @fileowner($here);
$ownerMatch = ($fileOwner !== null && $dirOwner !== false && $fileOwner === $dirOwner);
$F[] = nvd_item(T('تطابق مالک فایل‌های ساخته‌شده','Created-file owner match'),
    $ownerMatch?'pass':($canWriteFile?'warn':'info'),
    $canWriteFile ? ('UID '.T('فایل','file').': '.$fileOwner.' | UID '.T('پوشه','dir').': '.$dirOwner.' | '.T('مجوز','perm').': '.$filePerm) : T('قابل بررسی نیست','Not checkable'),
    T('یکسان بودن مالک فایل و پوشه','File and folder owned by same user'),
    $ownerMatch ? array('fa'=>'فایل‌هایی که وردپرس می‌سازد با همان کاربر هاست ساخته می‌شوند.','en'=>'Files WordPress creates belong to your hosting user.')
                : array('fa'=>'مالک فایل با مالک پوشه فرق دارد (حالت mod_php/nobody)؛ بعداً برای به‌روزرسانی خودکار به مشکل مجوز می‌خورید. از هاست بخواهید PHP را روی FPM/suEXEC اجرا کند.','en'=>'Owner mismatch (mod_php/nobody); auto-updates will hit permission issues later. Ask your host to run PHP as FPM/suEXEC.'),
    2);

// wp-config template writability (parent folder already covered; note the security)
$hasWpConfig = @is_file($here . '/wp-config.php');
if ($hasWpConfig) {
    $wcPerm = substr(sprintf('%o', @fileperms($here.'/wp-config.php')), -3);
    $wcSafe = in_array($wcPerm, array('400','440','600','640','644'), true);
    $F[] = nvd_item(T('امنیت wp-config.php','wp-config.php security'),
        $wcSafe?'pass':'warn', T('مجوز','Perm').' '.$wcPerm, T('440 یا 400 (سخت‌گیرانه)','440 or 400 (strict)'),
        $wcSafe ? array('fa'=>'مجوز فایل پیکربندی در محدوده‌ی امن است.','en'=>'Config file permission is within a safe range.')
                : array('fa'=>'مجوز wp-config.php بازتر از حد امن است؛ آن را روی 440 بگذارید.','en'=>'wp-config.php is more open than safe; set it to 440.'),
        2, false, $wcSafe?'':T('تنظیم مجوز wp-config.php روی 440','Set wp-config.php to 440'));
}

// session/tmp writable (WP itself is stateless but many plugins need tmp)
$tmpDir = nvd_ini('upload_tmp_dir'); if ($tmpDir==='') $tmpDir = sys_get_temp_dir();
$tmpOk = @is_writable($tmpDir);
$F[] = nvd_item(T('پوشه‌ی موقت آپلود','Upload temp folder'),
    $tmpOk?'pass':'warn', $tmpDir.($tmpOk?T(' (قابل نوشتن)',' (writable)'):T(' (غیرقابل نوشتن)',' (not writable)')), T('قابل نوشتن','Writable'),
    $tmpOk ? array('fa'=>'فایل‌های آپلودی به‌درستی دریافت می‌شوند.','en'=>'Uploaded files are received correctly.')
           : array('fa'=>'آپلود رسانه و نصب افزونه شکست می‌خورد؛ یک مسیر موقت قابل نوشتن تعریف کنید.','en'=>'Media upload and plugin install fail; define a writable temp path.'),
    3, false, $tmpOk?'':T('تعریف upload_tmp_dir قابل نوشتن','Define a writable upload_tmp_dir'));

// disk space
$freeB = function_exists('disk_free_space') ? @disk_free_space($here) : false;
if ($freeB !== false && $freeB !== null) {
    $freeMB = nvd_mb($freeB);
    if ($freeMB >= $WP['disk_rec'])      { $st='pass'; $nt=array('fa'=>'فضا برای وردپرس، رسانه و بکاپ کافی است.','en'=>'Enough space for WordPress, media and backups.'); }
    elseif ($freeMB >= $WP['disk_min'])  { $st='warn'; $nt=array('fa'=>'برای نصب کافی است، اما جای کافی برای رشد سایت و بکاپ ندارید.','en'=>'Enough to install, but tight for growth and backups.'); }
    else                                 { $st='fail'; $nt=array('fa'=>'فضای آزاد کمتر از حد لازم است.','en'=>'Free space is below the needed level.'); }
    $F[] = nvd_item(T('فضای آزاد دیسک','Free disk space'), $st, nvd_hsize($freeB),
        T('حداقل 500MB — پیشنهادی 2GB','Min 500MB — recommended 2GB'), $nt, 3, ($st==='fail'), ($st==='pass'?'':T('ارتقای فضای هاست','Upgrade hosting storage')));
}

$F[] = nvd_item(T('تعداد فایل مجاز (inode)','File count limit (inode)'), 'info',
    T('از داخل PHP قابل اندازه‌گیری نیست','Not measurable from PHP'),
    T('حداقل ۲۵٬۰۰۰ inode آزاد','At least 25,000 free inodes'),
    array('fa'=>'وردپرس با یک قالب و چند افزونه به‌راحتی از ۲۰٬۰۰۰ فایل عبور می‌کند. سقف inode پلن را از هاست بپرسید.',
          'en'=>'A theme plus a few plugins easily exceeds 20,000 files. Ask your host about the inode cap.'),
    0);

// existing content warning
$hasIndex = @is_file($here.'/index.php');
if (($hasIndex || $hasWpConfig) && $existingWp === '') {
    $F[] = nvd_item(T('محتوای فعلی پوشه‌ی نصب','Current install-folder contents'), 'warn',
        ($hasWpConfig?'wp-config.php '.T('موجود است','present'):'index.php '.T('موجود است','present')),
        T('پوشه‌ی خالی برای نصب تازه','Empty folder for a fresh install'),
        array('fa'=>'در این مسیر از قبل فایل‌هایی هست؛ نصب تازه‌ی وردپرس ممکن است آن‌ها را خراب کند.','en'=>'This path already contains files; a fresh WordPress install may clobber them.'),
        1);
}

$F[] = nvd_item(T('اتصال HTTPS','HTTPS connection'), $isHttps?'pass':'fail',
    $isHttps?T('فعال','Active'):T('غیرفعال (HTTP)','Off (HTTP)'), T('گواهی SSL معتبر — الزامی وردپرس','Valid SSL — required by WordPress'),
    $isHttps ? array('fa'=>'ارتباط رمزگذاری‌شده است؛ ورود به پیشخوان امن انجام می‌شود.','en'=>'Connection is encrypted; dashboard login is secure.')
             : array('fa'=>'HTTPS اکنون از الزامات رسمی وردپرس است. بدون آن رمز مدیر متن ساده منتقل می‌شود و گوگل سایت را «ناامن» علامت می‌زند. گواهی رایگان Let\'s Encrypt را فعال کنید.',
                     'en'=>'HTTPS is now an official WordPress requirement. Without it the admin password travels in plain text and Google flags the site “Not secure.” Enable free Let\'s Encrypt SSL.'),
    4, false, $isHttps?'':T('فعال‌سازی گواهی SSL روی دامنه','Enable an SSL certificate on the domain'));

$sections[] = array(
    'id' => 'fs',
    'title' => T('فایل‌سیستم، مجوزها و فضا', 'Filesystem, permissions & space'),
    'desc'  => T('بیشترین خطاهای وردپرس از همین‌جاست: مجوز نوشتن، مالکیت فایل و پوشه‌ی uploads.',
                 'Most WordPress errors start here: write permission, file ownership and the uploads folder.'),
    'items' => $F
);

/* ===========================================================================
 |  G — Deep tests (network, rewrite, loopback) — opt-in
 =========================================================================== */
$G = array();

function nvd_http_head($url, $timeout = 5) {
    if (function_exists('curl_init')) {
        $ch = @curl_init($url);
        if ($ch) {
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_NOBODY, true);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            @curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            @curl_setopt($ch, CURLOPT_USERAGENT, 'NavidIranians-WP-Checker/1.0');
            @curl_exec($ch);
            $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = @curl_error($ch);
            @curl_close($ch);
            return array('code' => $code, 'error' => $err);
        }
    }
    if (nvd_ini_on('allow_url_fopen')) {
        $ctx = @stream_context_create(array('http' => array('timeout' => $timeout, 'method' => 'HEAD')));
        $h = @get_headers($url, 0, $ctx);
        if ($h && isset($h[0]) && preg_match('/\s(\d{3})\s/', $h[0], $m)) return array('code' => (int)$m[1], 'error' => '');
    }
    return array('code' => 0, 'error' => T('ابزار ارتباط شبکه در دسترس نیست', 'No network tool available'));
}
function nvd_rmdir_all($dir) {
    if (!@is_dir($dir)) return;
    $items = @scandir($dir);
    if ($items) foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $p = $dir . '/' . $it;
        if (@is_dir($p)) nvd_rmdir_all($p); else @unlink($p);
    }
    @rmdir($dir);
}

if ($deep) {
    // WordPress.org services
    $targets = array(
        array(T('به‌روزرسانی و افزونه‌ها (api.wordpress.org)','Updates & plugins (api.wordpress.org)'), 'https://api.wordpress.org/'),
        array(T('دانلود هسته (downloads.wordpress.org)','Core download (downloads.wordpress.org)'), 'https://downloads.wordpress.org/'),
        array(T('مخزن افزونه‌ها (wordpress.org)','Plugin directory (wordpress.org)'), 'https://wordpress.org/plugins/'),
    );
    foreach ($targets as $tgt) {
        $r  = nvd_http_head($tgt[1]);
        $ok = ($r['code'] >= 200 && $r['code'] < 400);
        $G[] = nvd_item(T('دسترسی به ','Access to ').$tgt[0], $ok?'pass':'fail',
            $ok ? T('پاسخ ','HTTP ').$r['code'] : (T('ناموفق','Failed').($r['error']?' — '.$r['error']:'')),
            T('پاسخ 200','HTTP 200'),
            $ok ? array('fa'=>'ارتباط خروجی سرور با این سرویس برقرار است.','en'=>'Outbound connection to this service works.')
                : array('fa'=>'سرور به این آدرس دسترسی ندارد (فیلترینگ خروجی یا فایروال). نتیجه: به‌روزرسانی و نصب افزونه از پیشخوان کار نمی‌کند و باید بسته‌ها را دستی آپلود کنید.',
                        'en'=>'Server cannot reach this address (egress filtering or firewall). Result: dashboard updates and plugin installs fail; upload packages manually.'),
            3, false, $ok?'':T('باز کردن دسترسی خروجی HTTPS به wordpress.org','Open outbound HTTPS to wordpress.org'));
    }

    // mod_rewrite live test (pretty permalinks)
    $probeDir = $here . '/_nvd_probe_' . mt_rand(10000,99999);
    $rwStatus='info'; $rwActual=T('قابل انجام نیست','Not performed'); $rwNote='';
    if (@mkdir($probeDir, 0755)) {
        @file_put_contents($probeDir.'/ok.txt', 'NVD_REWRITE_OK');
        @file_put_contents($probeDir.'/.htaccess', "Options +FollowSymLinks\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule ^probe\\.txt$ ok.txt [L]\n</IfModule>\n");
        $base = ($isHttps?'https://':'http://').$selfHost;
        $path = isset($_SERVER['SCRIPT_NAME']) ? rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/') : '';
        $probeUrl = $base.$path.'/'.basename($probeDir).'/probe.txt';
        $body='';
        if (function_exists('curl_init')) {
            $ch=@curl_init($probeUrl);
            @curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); @curl_setopt($ch,CURLOPT_TIMEOUT,6);
            @curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,3); @curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            @curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
            $body=(string)@curl_exec($ch); @curl_close($ch);
        } elseif (nvd_ini_on('allow_url_fopen')) {
            $ctx=@stream_context_create(array('http'=>array('timeout'=>6)));
            $body=(string)@file_get_contents($probeUrl,false,$ctx);
        }
        if (strpos($body,'NVD_REWRITE_OK') !== false) {
            $rwStatus='pass'; $rwActual=T('فعال و آزمایش‌شده','Active & verified');
            $rwNote=array('fa'=>'فایل htaccess خوانده می‌شود و mod_rewrite کار می‌کند؛ «پیوندهای یکتای» زیبا بدون index.php فعال خواهد شد.',
                          'en'=>'.htaccess is read and mod_rewrite works; pretty permalinks without index.php will function.');
        } elseif ($body !== '') {
            $rwStatus='fail'; $rwActual=T('بازنویسی آدرس انجام نشد','URL rewrite did not run');
            $rwNote=array('fa'=>'درخواست پاسخ گرفت اما قانون بازنویسی اجرا نشد؛ یا mod_rewrite خاموش است یا AllowOverride اجازه‌ی htaccess نمی‌دهد. نتیجه: پیوندهای یکتای زیبا خطای 404 می‌دهند.',
                          'en'=>'Request answered but the rewrite rule did not run; either mod_rewrite is off or AllowOverride blocks .htaccess. Result: pretty permalinks 404.');
        } else {
            $rwStatus='warn'; $rwActual=T('تست ناتمام ماند','Test inconclusive');
            $rwNote=array('fa'=>'سرور نتوانست به خودش درخواست بزند (اغلب بسته بودن ارتباط خروجی). این تست را دستی انجام دهید: در پیشخوان → تنظیمات → پیوندهای یکتا، حالت «نام نوشته» را انتخاب کنید و ذخیره‌ی صفحه را ببینید.',
                          'en'=>'Server could not call itself (usually blocked egress). Test manually: Dashboard → Settings → Permalinks, pick “Post name” and check the front-end.');
        }
        nvd_rmdir_all($probeDir);
    }
    $G[] = nvd_item(T('mod_rewrite و پیوندهای یکتا','mod_rewrite & pretty permalinks'), $rwStatus, $rwActual,
        T('فعال برای پیوندهای یکتای زیبا','Active for pretty permalinks'), $rwNote, 3, false,
        ($rwStatus==='pass')?'':T('فعال‌سازی mod_rewrite و AllowOverride All','Enable mod_rewrite and AllowOverride All'));

    // loopback (WP-Cron / Site Health depends on this)
    $selfBase = ($isHttps?'https://':'http://').$selfHost.(isset($_SERVER['REQUEST_URI'])?strtok($_SERVER['REQUEST_URI'],'?'):'/');
    $lp = nvd_http_head($selfBase, 6);
    $lpOk = ($lp['code'] >= 200 && $lp['code'] < 500);
    $G[] = nvd_item(T('درخواست حلقه‌ای (Loopback)','Loopback request'), $lpOk?'pass':'warn',
        $lpOk ? T('پاسخ ','HTTP ').$lp['code'] : (T('ناموفق','Failed').($lp['error']?' — '.$lp['error']:'')),
        T('سرور بتواند به خودش درخواست بزند','Server can call itself'),
        $lpOk ? array('fa'=>'وردپرس می‌تواند WP-Cron و «سلامت سایت» را اجرا کند.','en'=>'WordPress can run WP-Cron and Site Health.')
              : array('fa'=>'سرور نمی‌تواند به خودش درخواست بزند؛ زمان‌بندی وردپرس (WP-Cron) و انتشار زمان‌بندی‌شده کار نمی‌کند. راه‌حل: DISABLE_WP_CRON و یک کرون سیستمی.',
                      'en'=>'Server cannot call itself; WP-Cron and scheduled posts fail. Fix: set DISABLE_WP_CRON and use a system cron.'),
        2);

    // DNS
    $dnsOk = function_exists('gethostbyname') ? (gethostbyname('api.wordpress.org') !== 'api.wordpress.org') : false;
    $G[] = nvd_item(T('تفکیک نام دامنه (DNS)','DNS resolution'), $dnsOk?'pass':'warn',
        $dnsOk?T('موفق','Success'):T('ناموفق یا مسدود','Failed or blocked'), T('resolve دامنه‌های بیرونی','Resolve external domains'),
        $dnsOk ? array('fa'=>'سرور می‌تواند نام دامنه‌های بیرونی را ترجمه کند.','en'=>'Server can resolve external domains.')
               : array('fa'=>'سرور دامنه‌های بیرونی را ترجمه نمی‌کند؛ هر قابلیت وابسته به اینترنت از کار می‌افتد.','en'=>'Server cannot resolve external domains; anything internet-dependent fails.'),
        2);

    $sections[] = array(
        'id' => 'net',
        'title' => T('تست‌های عمیق: شبکه، بازنویسی و حلقه','Deep tests: network, rewrite & loopback'),
        'desc'  => T('این بخش با اجرای درخواست واقعی سنجیده شد — نه با حدس زدن از روی تنظیمات.',
                     'Measured with real requests — not guessed from settings.'),
        'items' => $G
    );
}

/* ===========================================================================
 |  H — Database connection test (optional form)
 =========================================================================== */
$dbResult = null;
$dbCsrfError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nvd_db_test']) && !nvd_csrf_check()) {
    $dbCsrfError = true;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nvd_db_test']) && !$dbCsrfError) {
    $dbHost=trim(nvd_get($_POST,'db_host','localhost'));
    $dbUser=trim(nvd_get($_POST,'db_user',''));
    $dbPass=(string)nvd_get($_POST,'db_pass','');
    $dbName=trim(nvd_get($_POST,'db_name',''));
    $dbPort=(int)nvd_get($_POST,'db_port',3306); if($dbPort<=0)$dbPort=3306;

    $rows=array();
    if (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true)) {
        try {
            $dsn='mysql:host='.$dbHost.';port='.$dbPort.($dbName!==''?';dbname='.$dbName:'');
            $pdo=new PDO($dsn,$dbUser,$dbPass,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>6));
            $srvVer=(string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $isMaria=(stripos($srvVer,'mariadb')!==false);
            $cleanVer=preg_replace('/[^0-9\.].*$/','',$srvVer);

            $rows[]=nvd_item(T('اتصال به دیتابیس','Database connection'),'pass',T('برقرار شد','Connected'),T('اتصال موفق','Successful connection'),
                array('fa'=>'نام کاربری، رمز و نام دیتابیس درست است — همین مقادیر را در wp-config.php وارد کنید.','en'=>'Username, password and DB name are correct — use these in wp-config.php.'),5,true);

            if ($isMaria) {
                $ok=version_compare($cleanVer,$WP['mariadb_min'],'>='); $rec=version_compare($cleanVer,$WP['mariadb_rec'],'>=');
                $rows[]=nvd_item(T('نسخه MariaDB','MariaDB version'), $ok?($rec?'pass':'warn'):'fail', $srvVer,
                    T('حداقل '.$WP['mariadb_min'].' — پیشنهادی '.$WP['mariadb_rec'],'Min '.$WP['mariadb_min'].' — recommended '.$WP['mariadb_rec']),
                    $ok?($rec?array('fa'=>'نسخه‌ی دیتابیس عالی است.','en'=>'Database version is great.')
                            :array('fa'=>'اجرا می‌شود، اما نسخه‌ی جدیدتر سریع‌تر و امن‌تر است.','en'=>'Runs, but a newer version is faster and safer.'))
                        :array('fa'=>'نسخه‌ی دیتابیس پایین‌تر از حد وردپرس است.','en'=>'Database version is below the WordPress floor.'),5,!$ok);
            } else {
                $ok=version_compare($cleanVer,$WP['mysql_min'],'>='); $rec=version_compare($cleanVer,$WP['mysql_rec'],'>=');
                $rows[]=nvd_item(T('نسخه MySQL','MySQL version'), $ok?($rec?'pass':'warn'):'fail', $srvVer,
                    T('حداقل '.$WP['mysql_min'].' — پیشنهادی '.$WP['mysql_rec'].'+','Min '.$WP['mysql_min'].' — recommended '.$WP['mysql_rec'].'+'),
                    $ok?($rec?array('fa'=>'نسخه‌ی دیتابیس عالی است.','en'=>'Database version is great.')
                            :array('fa'=>'اجرا می‌شود، اما MySQL 8 برای وردپرس مدرن توصیه می‌شود.','en'=>'Runs, but MySQL 8 is recommended for modern WordPress.'))
                        :array('fa'=>'نسخه‌ی دیتابیس پایین‌تر از حد وردپرس است.','en'=>'Database version is below the WordPress floor.'),5,!$ok);
            }

            $cs=$pdo->query("SHOW CHARACTER SET LIKE 'utf8mb4'")->fetchAll();
            $rows[]=nvd_item(T('پشتیبانی utf8mb4','utf8mb4 support'), !empty($cs)?'pass':'fail',
                !empty($cs)?T('پشتیبانی می‌شود','Supported'):T('پشتیبانی نمی‌شود','Not supported'),'utf8mb4',
                !empty($cs)?array('fa'=>'متن فارسی، عربی و ایموجی بدون مشکل ذخیره می‌شود.','en'=>'Persian, Arabic and emoji store without issues.')
                          :array('fa'=>'بدون utf8mb4 ذخیره‌ی برخی کاراکترها با خطا مواجه می‌شود.','en'=>'Without utf8mb4 some characters fail to save.'),3);

            if ($dbName!=='') {
                $tbl='nvd_test_'.mt_rand(1000,9999); $canCreate=false;
                try {
                    $pdo->exec("CREATE TABLE `$tbl` (id INT PRIMARY KEY AUTO_INCREMENT, t VARCHAR(20)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $pdo->exec("INSERT INTO `$tbl` (t) VALUES ('ok')");
                    $pdo->exec("ALTER TABLE `$tbl` ADD COLUMN t2 VARCHAR(10) NULL");
                    $pdo->exec("DROP TABLE `$tbl`"); $canCreate=true;
                } catch (Exception $ex) { @$pdo->exec("DROP TABLE IF EXISTS `$tbl`"); }
                $rows[]=nvd_item(T('سطح دسترسی کاربر دیتابیس','Database user privileges'), $canCreate?'pass':'fail',
                    $canCreate?T('CREATE / INSERT / ALTER / DROP مجاز است','CREATE / INSERT / ALTER / DROP allowed'):T('ناکافی','Insufficient'),
                    T('دسترسی کامل روی همین دیتابیس','Full privileges on this database'),
                    $canCreate?array('fa'=>'کاربر همه‌ی مجوزهای موردنیاز نصب وردپرس را دارد.','en'=>'User has all privileges WordPress needs.')
                             :array('fa'=>'کاربر اجازه‌ی ساخت جدول ندارد؛ در کنترل‌پنل ALL PRIVILEGES را برای این کاربر فعال کنید.','en'=>'User cannot create tables; grant ALL PRIVILEGES in the control panel.'),5,!$canCreate);
            }

            $eng=$pdo->query("SHOW ENGINES")->fetchAll(PDO::FETCH_ASSOC); $innodb=false;
            foreach ($eng as $e) {
                $en=isset($e['Engine'])?strtolower($e['Engine']):''; $su=isset($e['Support'])?strtoupper($e['Support']):'';
                if ($en==='innodb'&&($su==='YES'||$su==='DEFAULT')) $innodb=true;
            }
            $rows[]=nvd_item(T('موتور InnoDB','InnoDB engine'), $innodb?'pass':'warn',
                $innodb?T('فعال','Enabled'):T('غیرفعال','Disabled'), T('فعال','Enabled'),
                $innodb?array('fa'=>'جداول وردپرس با InnoDB (تراکنش و قفل سطری) ساخته می‌شوند.','en'=>'WordPress tables use InnoDB (transactions and row locking).')
                       :array('fa'=>'InnoDB برای کارایی و پایداری وردپرس توصیه می‌شود؛ از هاست بخواهید فعال کند.','en'=>'InnoDB is recommended for WordPress performance and stability; ask your host to enable it.'),3);
        } catch (Exception $ex) {
            $rows[]=nvd_item(T('اتصال به دیتابیس','Database connection'),'fail',T('ناموفق','Failed'),T('اتصال موفق','Successful connection'),
                T('پیام سرور: ','Server said: ').$ex->getMessage().T(' — معمولاً یعنی نام کاربری/رمز اشتباه است یا نام دیتابیس با پیشوند حساب هاست وارد نشده (مثلاً user_dbname).',' — usually wrong username/password, or the DB name lacks your account prefix (e.g. user_dbname).'),5,true);
        }
    } else {
        $rows[]=nvd_item(T('درایور PDO MySQL','PDO MySQL driver'),'fail',T('در دسترس نیست','Unavailable'),'pdo_mysql',
            T('برای تست اتصال، افزونه pdo_mysql باید فعال باشد.','pdo_mysql must be enabled to run the connection test.'),3,true);
    }
    $dbResult=$rows;
}

/* ===========================================================================
 |  5) Scoring & verdict
 =========================================================================== */
$totalW=0; $gotW=0; $fails=array(); $warns=array(); $critFails=array(); $allItems=array();
foreach ($sections as $sec) foreach ($sec['items'] as $it) $allItems[]=$it;
if ($dbResult) foreach ($dbResult as $it) $allItems[]=$it;

foreach ($allItems as $it) {
    if ($it['status']==='info' || (int)$it['weight']===0) continue;
    $totalW += $it['weight'];
    if ($it['status']==='pass') $gotW += $it['weight'];
    elseif ($it['status']==='warn') $gotW += $it['weight']*0.5;
    if ($it['status']==='fail') { $fails[]=$it; if ($it['critical']) $critFails[]=$it; }
    elseif ($it['status']==='warn') $warns[]=$it;
}
$score = ($totalW>0) ? (int)round(($gotW/$totalW)*100) : 0;

if (count($critFails) > 0) {
    $verdict=T('آماده‌ی نصب نیست','Not ready to install'); $verdictClass='v-fail';
    $verdictText=T('حداقل یک پیش‌نیاز حیاتی برقرار نیست. تا رفع موارد قرمز، نصب وردپرس شکست می‌خورد.',
                   'At least one critical requirement is missing. Until the red items are fixed, WordPress install will fail.');
} elseif (count($fails) > 0) {
    $verdict=T('نصب می‌شود، اما ناقص','Installs, but incomplete'); $verdictClass='v-warn';
    $verdictText=T('نصب انجام می‌شود ولی بخشی از قابلیت‌ها (آپلود، به‌روزرسانی یا پیوندهای یکتا) درست کار نمی‌کند.',
                   'Install succeeds, but some features (uploads, updates, permalinks) will not work correctly.');
} elseif (count($warns) > 0) {
    $verdict=T('آماده با نکات قابل بهبود','Ready, with room to improve'); $verdictClass='v-warn';
    $verdictText=T('می‌توانید وردپرس را نصب کنید. موارد نارنجی را برای پایداری و سرعت بیشتر اصلاح کنید.',
                   'You can install WordPress. Fix the amber items for more stability and speed.');
} else {
    $verdict=T('کاملاً آماده','Fully ready'); $verdictClass='v-pass';
    $verdictText=T('همه‌ی پیش‌نیازهای وردپرس روی این هاست برقرار است. با خیال راحت نصب کنید.',
                   'Every WordPress requirement is met on this host. Install with confidence.');
}

// Ticket text
$ticket = T("با سلام\n\nقصد راه‌اندازی وردپرس روی این هاست را دارم. بر اساس گزارش بررسی، لطفاً موارد زیر را اعمال بفرمایید:\n\n",
            "Hello,\n\nI'm setting up WordPress on this host. Based on the readiness report, please apply the following:\n\n");
$tn=1;
foreach ($fails as $it) { if ($it['fix']!=='') { $ticket.=$tn.') '.LV($it['fix'])."\n"; $tn++; } }
foreach ($warns as $it) { if ($it['fix']!=='') { $ticket.=$tn.') '.LV($it['fix'])."\n"; $tn++; } }
if ($tn===1) $ticket .= T("موردی برای اصلاح یافت نشد؛ سرور کاملاً آماده است.\n","No changes needed; the server is fully ready.\n");
$ticket .= "\n".T('دامنه: ','Domain: ').$hostName."\n".T('نسخه فعلی PHP: ','Current PHP: ').$phpVersion."\n".T('با تشکر','Thank you');

$iniFixes = array_values(array_unique($iniFixes));

/* ---------------------------------------------------------------------------
 |  6) Safe self-delete
 --------------------------------------------------------------------------- */
$deleteCsrfError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nvd_selfdestruct']) && !nvd_csrf_check()) {
    $deleteCsrfError = true;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nvd_selfdestruct']) && !$deleteCsrfError) {
    if (@unlink(__FILE__)) {
        echo '<!DOCTYPE html><html lang="'.$LANG.'" dir="'.$DIR.'"><head><meta charset="utf-8">'
           . '<title>'.T('حذف شد','Deleted').'</title><style>body{font-family:Vazirmatn,Tahoma,sans-serif;'
           . 'background:#0B2540;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;'
           . 'margin:0;text-align:center}div{max-width:520px;padding:32px}h1{font-size:22px;margin:0 0 12px}'
           . 'p{color:#9FB3C8;line-height:2}</style></head><body><div>'
           . '<h1>'.T('فایل بررسی با موفقیت حذف شد','The checker file was deleted successfully').'</h1>'
           . '<p>'.T('دیگر هیچ اطلاعاتی از سرور شما در دسترس عموم نیست.','No server information is publicly reachable anymore.').'<br>'
           . T('موفق باشید','Good luck').' — '.nvd_e(T(NVD_COMPANY_FA,NVD_COMPANY_EN)).'</p></div></body></html>';
        exit;
    }
    $deleteError = T('حذف خودکار ممکن نشد؛ فایل را دستی از File Manager پاک کنید.','Auto-delete failed; remove the file manually from File Manager.');
}

/* ---------------------------------------------------------------------------
 |  7) HTML output
 --------------------------------------------------------------------------- */
$statusMeta = array(
    'pass' => array('label' => T('قبول','Pass'),  'cls' => 'st-pass'),
    'warn' => array('label' => T('هشدار','Warn'), 'cls' => 'st-warn'),
    'fail' => array('label' => T('مردود','Fail'), 'cls' => 'st-fail'),
    'info' => array('label' => T('اطلاع','Info'), 'cls' => 'st-info'),
);
$selfUrl  = htmlspecialchars(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '', ENT_QUOTES, 'UTF-8');
$otherLang = $IS_FA ? 'en' : 'fa';
$qs = $_GET; $qs['lang'] = $otherLang; $toggleUrl = $selfUrl . '?' . http_build_query($qs);
$qsDeep = $_GET; $qsDeep['deep'] = '1'; $deepUrl = $selfUrl . '?' . http_build_query($qsDeep);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG; ?>" dir="<?php echo $DIR; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php echo T('بررسی پیش‌نیازهای وردپرس','WordPress Requirements Checker'); ?> | <?php echo nvd_e(T(NVD_COMPANY_FA,NVD_COMPANY_EN)); ?></title>
<style>
:root{
  --navy:#0B2540; --navy-2:#123A5C; --fz:#16BDB3; --fz-dark:#0E8C85;
  --gold:#D4A03C; --bg:#EDF1F5; --card:#FFFFFF; --line:#DCE4EC;
  --ink:#12212F; --muted:#5D7183;
  --pass:#12A150; --warn:#DF8600; --fail:#DC2A4B; --info:#2C6BD8; --r:14px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--ink);
  font-family:Vazirmatn,"IRANSans","Segoe UI",Tahoma,sans-serif;
  font-size:15px;line-height:1.9;-webkit-font-smoothing:antialiased}
.wrap{max-width:1080px;margin:0 auto;padding:0 18px}
code,.mono{font-family:ui-monospace,"SFMono-Regular",Menlo,Consolas,monospace;direction:ltr;unicode-bidi:embed}

.top{background:var(--navy);color:#fff;padding:30px 0 96px;position:relative;overflow:hidden;border-bottom:3px solid var(--gold)}
.top:before{content:"";position:absolute;inset:0;opacity:.13;
  background-image:
    repeating-linear-gradient(45deg,transparent 0 22px,rgba(22,189,179,.8) 22px 23px),
    repeating-linear-gradient(-45deg,transparent 0 22px,rgba(212,160,60,.55) 22px 23px)}
.top>*{position:relative}
.brandbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:12px}
.mark{width:46px;height:46px;flex:none;border-radius:13px;background:linear-gradient(140deg,var(--fz),var(--fz-dark));
  display:flex;align-items:center;justify-content:center;font-weight:800;font-size:19px;color:#04252B;
  box-shadow:0 6px 18px rgba(22,189,179,.35)}
.brand b{display:block;font-size:16px}
.brand span{display:block;font-size:12px;color:#9FB8CC;letter-spacing:.03em}
.hbtns{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.tag{font-size:12px;color:#9FB8CC;border:1px solid rgba(255,255,255,.18);padding:5px 12px;border-radius:999px}
.lang{font-size:13px;font-weight:700;color:#04252B;background:var(--fz);border:0;padding:7px 15px;border-radius:999px;
  text-decoration:none;transition:.16s;display:inline-flex;align-items:center;gap:6px}
.lang:hover{background:#fff}
.title{margin:26px 0 6px;font-size:27px;font-weight:800;letter-spacing:-.02em}
.subtitle{margin:0;color:#A9C1D4;max-width:660px}

.verdict{margin-top:-70px;background:var(--card);border:1px solid var(--line);border-radius:20px;
  padding:26px;display:flex;gap:26px;align-items:center;flex-wrap:wrap;box-shadow:0 18px 40px rgba(11,37,64,.10)}
.gauge{--p:0;width:132px;height:132px;flex:none;border-radius:50%;display:grid;place-items:center;
  background:conic-gradient(var(--gaugecolor) calc(var(--p)*1%),#E6ECF2 0);position:relative}
.gauge:after{content:"";position:absolute;inset:11px;background:var(--card);border-radius:50%}
.gauge b{position:relative;font-size:31px;font-weight:800;line-height:1}
.gauge i{position:relative;font-style:normal;font-size:11px;color:var(--muted);display:block;margin-top:2px}
.vbody{flex:1;min-width:260px}
.vbody h2{margin:0 0 6px;font-size:22px}
.vbody p{margin:0;color:var(--muted)}
.v-pass h2{color:var(--pass)} .v-warn h2{color:var(--warn)} .v-fail h2{color:var(--fail)}
.counts{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.pill{border-radius:999px;padding:5px 14px;font-size:13px;font-weight:600;border:1px solid}
.p-pass{color:var(--pass);border-color:rgba(18,161,80,.3);background:rgba(18,161,80,.07)}
.p-warn{color:var(--warn);border-color:rgba(223,134,0,.3);background:rgba(223,134,0,.07)}
.p-fail{color:var(--fail);border-color:rgba(220,42,75,.3);background:rgba(220,42,75,.07)}

.actions{display:flex;gap:10px;flex-wrap:wrap;margin:22px 0 6px}
.btn{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--line);background:var(--card);
  color:var(--ink);padding:10px 17px;border-radius:11px;font:inherit;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:.16s}
.btn:hover{border-color:var(--fz);color:var(--fz-dark);transform:translateY(-1px)}
.btn-p{background:var(--navy);color:#fff;border-color:var(--navy)}
.btn-p:hover{background:var(--navy-2);color:#fff}
.btn-d{color:var(--fail);border-color:rgba(220,42,75,.35)}
.btn-d:hover{background:var(--fail);color:#fff;border-color:var(--fail)}

.sec{background:var(--card);border:1px solid var(--line);border-radius:var(--r);margin:18px 0;overflow:hidden}
.sec>header{padding:17px 20px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#FAFCFE,#F3F7FA)}
.sec h3{margin:0;font-size:17px;display:flex;align-items:center;gap:9px}
.sec h3 em{width:7px;height:20px;border-radius:4px;background:var(--fz);font-style:normal;flex:none}
.sec header p{margin:5px 0 0;font-size:13px;color:var(--muted)}
.row{display:grid;grid-template-columns:1.15fr 1fr 1fr 92px;gap:14px;padding:15px 20px;border-top:1px solid #EEF2F6;align-items:start}
.row:first-of-type{border-top:0}
.row:hover{background:#FBFDFE}
.rl{font-weight:700}
.rl small{display:block;font-weight:400;font-size:12.5px;color:var(--muted);margin-top:4px;line-height:1.8}
.rv{font-size:13px}
.rv span{display:block;font-size:11px;color:var(--muted);margin-bottom:2px}
.rv code{background:#F1F5F9;border:1px solid #E2E8F0;border-radius:7px;padding:2px 7px;display:inline-block;font-size:12.5px;word-break:break-all}
.badge{justify-self:start;font-size:12px;font-weight:700;padding:5px 12px;border-radius:8px;white-space:nowrap}
.st-pass{background:rgba(18,161,80,.1);color:var(--pass)}
.st-warn{background:rgba(223,134,0,.12);color:var(--warn)}
.st-fail{background:rgba(220,42,75,.1);color:var(--fail)}
.st-info{background:rgba(44,107,216,.09);color:var(--info)}

.box{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:20px;margin:18px 0}
.box h3{margin:0 0 6px;font-size:17px}
.box p.hint{margin:0 0 14px;color:var(--muted);font-size:13.5px}
pre.snip{background:#0B2540;color:#CFE3F2;border-radius:11px;padding:16px;overflow:auto;font-size:13px;
  direction:ltr;text-align:left;margin:0;line-height:1.9;font-family:ui-monospace,Menlo,Consolas,monospace}
textarea.snip{width:100%;min-height:200px;background:#F7FAFC;border:1px solid var(--line);border-radius:11px;
  padding:14px;font:inherit;font-size:13.5px;line-height:2;color:var(--ink);resize:vertical}
.grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}
label.fld{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
input.inp{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:14px;background:#F9FBFD}
input.inp:focus{outline:2px solid rgba(22,189,179,.35);border-color:var(--fz)}
.note{border-inline-start:4px solid var(--gold);background:#FFF9EE;padding:12px 15px;border-radius:9px;font-size:13.5px;color:#6B5320;margin-top:14px}

.foot{background:var(--navy);color:#C6D8E6;margin-top:34px;padding:40px 0 0;border-top:3px solid var(--gold)}
.fgrid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:30px}
.foot h4{color:#fff;font-size:15px;margin:0 0 12px}
.foot p{margin:0 0 10px;font-size:13.5px;line-height:2.1;color:#9FB8CC}
.foot ul{list-style:none;margin:0;padding:0}
.foot li{font-size:13.5px;padding:5px 0;color:#9FB8CC;display:flex;gap:8px}
.foot li:before{content:"◆";color:var(--fz);font-size:9px;line-height:2.4}
.weblinks{margin:0 0 10px;font-size:13px;direction:ltr;text-align:left}
.weblinks a{color:var(--fz);text-decoration:none;font-weight:600;margin-inline-end:10px;white-space:nowrap}
.weblinks a:hover{text-decoration:underline}
.tel{display:flex;align-items:center;gap:9px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);
  border-radius:11px;padding:10px 14px;margin-bottom:9px;color:#fff;text-decoration:none;transition:.16s}
.tel:hover{background:var(--fz);border-color:var(--fz);color:#04252B}
.tel b{font-size:15px;letter-spacing:.03em;direction:ltr}
.tel span{font-size:11px;color:#9FB8CC}
.tel:hover span{color:#04353B}
.copy{margin-top:34px;border-top:1px solid rgba(255,255,255,.1);padding:16px 0;display:flex;
  justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:12.5px;color:#7F97AC}
@media (max-width:820px){
  .row{grid-template-columns:1fr;gap:7px}
  .badge{justify-self:end;margin-top:-30px}
  .fgrid{grid-template-columns:1fr}.title{font-size:22px}
}
@media print{body{background:#fff}.actions,.box form,.btn,.lang{display:none}.sec,.box{break-inside:avoid}}
</style>
</head>
<body>

<header class="top">
  <div class="wrap">
    <div class="brandbar">
      <div class="brand">
        <div class="mark"><?php echo T('نـ','N'); ?></div>
        <div>
          <b><?php echo nvd_e(T(NVD_COMPANY_FA,NVD_COMPANY_EN)); ?></b>
          <span><?php echo T('طراحی وب‌سایت · سئو · میزبانی وب · ثبت دامنه','Web Design · SEO · Hosting · Domains'); ?></span>
        </div>
      </div>
      <div class="hbtns">
        <span class="tag"><?php echo T('نسخه ابزار','Tool'); ?> <?php echo nvd_num(NVD_VERSION); ?> · WordPress</span>
        <a class="lang" href="<?php echo nvd_e($toggleUrl); ?>"><?php echo T('English','فارسی'); ?></a>
      </div>
    </div>
    <h1 class="title"><?php echo T('بررسی پیش‌نیازهای نصب و راه‌اندازی وردپرس','WordPress Installation Readiness Check'); ?></h1>
    <p class="subtitle">
      <?php echo T('این ابزار سرور شما را در برابر الزامات رسمی وردپرس می‌سنجد و دقیقاً می‌گوید چه چیزی باید تغییر کند.',
                   'This tool checks your server against the official WordPress requirements and tells you exactly what to change.'); ?>
      <?php echo T('دامنه‌ی بررسی‌شده:','Domain checked:'); ?> <code style="color:#CFE3F2"><?php echo nvd_e($hostName); ?></code>
    </p>
  </div>
</header>

<main class="wrap">

  <section class="verdict <?php echo $verdictClass; ?>">
    <div class="gauge" style="--p:<?php echo (int)$score; ?>;--gaugecolor:<?php
        echo $score>=90?'var(--pass)':($score>=65?'var(--warn)':'var(--fail)'); ?>">
      <b><?php echo nvd_num($score); ?><small style="font-size:15px"><?php echo T('٪','%'); ?></small></b>
      <i><?php echo T('آمادگی سرور','Readiness'); ?></i>
    </div>
    <div class="vbody">
      <h2><?php echo nvd_e($verdict); ?></h2>
      <p><?php echo nvd_e($verdictText); ?></p>
      <div class="counts">
        <span class="pill p-fail"><?php echo T('مردود:','Fail:'); ?> <?php echo nvd_num(count($fails)); ?></span>
        <span class="pill p-warn"><?php echo T('هشدار:','Warn:'); ?> <?php echo nvd_num(count($warns)); ?></span>
        <span class="pill p-pass"><?php echo T('بررسی‌شده:','Checked:'); ?> <?php echo nvd_num(count($allItems)); ?> <?php echo T('مورد','items'); ?></span>
      </div>
    </div>
  </section>

  <?php if (!$nvdLocked): ?>
  <div class="note" style="border-inline-start-color:var(--fail);background:#FDEEF1;color:#7A1230">
    <b><?php echo T('این گزارش بدون قفل و عمومی است!','This report is public and unlocked!'); ?></b><br>
    <?php echo T('هر کسی که آدرس این فایل را بداند می‌تواند اطلاعات سرور شما (مسیرها، تنظیمات PHP، وضعیت وردپرس) را ببیند. مقدار NVD_ACCESS_KEY را در ابتدای فایل تنظیم کنید و صفحه را با ‎?key=...‎ باز کنید، یا بلافاصله پس از پایان کار همین فایل را حذف کنید.',
        'Anyone who knows this file’s URL can see your server details (paths, PHP settings, WordPress status). Set NVD_ACCESS_KEY at the top of the file and open the page with ?key=..., or delete this file as soon as you are done.'); ?>
  </div>
  <?php endif; ?>

  <div class="actions">
    <?php if (!$deep): ?>
      <a class="btn btn-p" href="<?php echo nvd_e($deepUrl); ?>#net"><?php echo T('اجرای تست‌های عمیق (شبکه و mod_rewrite)','Run deep tests (network & mod_rewrite)'); ?></a>
    <?php else: ?>
      <a class="btn" href="<?php echo $selfUrl.'?lang='.$LANG; ?>"><?php echo T('بازگشت به حالت سریع','Back to quick mode'); ?></a>
    <?php endif; ?>
    <button class="btn" onclick="nvdCopy()"><?php echo T('کپی متن آماده برای پشتیبانی هاست','Copy ready-made text for host support'); ?></button>
    <button class="btn" onclick="window.print()"><?php echo T('چاپ / ذخیره PDF','Print / Save PDF'); ?></button>
    <a class="btn" href="#dbtest"><?php echo T('تست اتصال دیتابیس','Database connection test'); ?></a>
    <form method="post" style="display:inline" onsubmit="return confirm('<?php echo T('این فایل برای همیشه حذف می‌شود. مطمئن هستید؟','This file will be permanently deleted. Are you sure?'); ?>')">
      <input type="hidden" name="nvd_selfdestruct" value="1">
      <input type="hidden" name="nvd_csrf" value="<?php echo nvd_e(nvd_csrf_token()); ?>">
      <button class="btn btn-d" type="submit"><?php echo T('حذف این فایل از سرور','Delete this file'); ?></button>
    </form>
  </div>
  <?php if ($deleteCsrfError): ?><div class="note"><?php echo T('درخواست نامعتبر بود (نشانه CSRF نامعتبر یا منقضی‌شده)؛ صفحه را تازه‌سازی کرده و دوباره تلاش کنید.','Invalid request (missing or expired CSRF token); refresh the page and try again.'); ?></div><?php endif; ?>
  <?php if (isset($deleteError)): ?><div class="note"><?php echo nvd_e($deleteError); ?></div><?php endif; ?>

<?php foreach ($sections as $sec): ?>
  <section class="sec" id="<?php echo nvd_e($sec['id']); ?>">
    <header>
      <h3><em></em><?php echo nvd_e($sec['title']); ?></h3>
      <p><?php echo nvd_e($sec['desc']); ?></p>
    </header>
    <?php foreach ($sec['items'] as $it): $m=$statusMeta[$it['status']]; ?>
      <div class="row">
        <div class="rl"><?php echo nvd_e(LV($it['label'])); ?><small><?php echo nvd_e(LV($it['note'])); ?></small></div>
        <div class="rv"><span><?php echo T('وضعیت فعلی','Current'); ?></span><code><?php echo nvd_e(LV($it['actual'])); ?></code></div>
        <div class="rv"><span><?php echo T('مقدار موردنیاز','Required'); ?></span><code><?php echo nvd_e(LV($it['expected'])); ?></code></div>
        <div class="badge <?php echo $m['cls']; ?>"><?php echo $m['label']; ?></div>
      </div>
    <?php endforeach; ?>
  </section>
<?php endforeach; ?>

  <!-- Database test -->
  <section class="box" id="dbtest">
    <h3><?php echo T('تست اتصال دیتابیس','Database connection test'); ?></h3>
    <p class="hint"><?php echo T('همان اطلاعاتی را وارد کنید که می‌خواهید در wp-config.php استفاده کنید. نسخه، utf8mb4، InnoDB و سطح دسترسی بررسی می‌شود. هیچ اطلاعاتی ذخیره یا ارسال نمی‌شود.',
        'Enter the credentials you plan to use in wp-config.php. Version, utf8mb4, InnoDB and privileges are checked. Nothing is stored or sent.'); ?></p>
    <?php if ($dbCsrfError): ?><div class="note"><?php echo T('درخواست نامعتبر بود (نشانه CSRF نامعتبر یا منقضی‌شده)؛ صفحه را تازه‌سازی کرده و دوباره تلاش کنید.','Invalid request (missing or expired CSRF token); refresh the page and try again.'); ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="nvd_db_test" value="1">
      <input type="hidden" name="nvd_csrf" value="<?php echo nvd_e(nvd_csrf_token()); ?>">
      <input type="hidden" name="lang" value="<?php echo $LANG; ?>">
      <div class="grid2">
        <div><label class="fld"><?php echo T('میزبان دیتابیس','DB host'); ?></label>
          <input class="inp" name="db_host" dir="ltr" value="<?php echo nvd_e(nvd_get($_POST,'db_host','localhost')); ?>"></div>
        <div><label class="fld"><?php echo T('پورت','Port'); ?></label>
          <input class="inp" name="db_port" dir="ltr" value="<?php echo nvd_e(nvd_get($_POST,'db_port','3306')); ?>"></div>
        <div><label class="fld"><?php echo T('نام کاربری','Username'); ?></label>
          <input class="inp" name="db_user" dir="ltr" value="<?php echo nvd_e(nvd_get($_POST,'db_user','')); ?>"></div>
        <div><label class="fld"><?php echo T('رمز عبور','Password'); ?></label>
          <input class="inp" name="db_pass" type="password" dir="ltr"></div>
        <div><label class="fld"><?php echo T('نام دیتابیس','Database name'); ?></label>
          <input class="inp" name="db_name" dir="ltr" value="<?php echo nvd_e(nvd_get($_POST,'db_name','')); ?>"></div>
        <div style="display:flex;align-items:flex-end">
          <button class="btn btn-p" type="submit" style="width:100%;justify-content:center"><?php echo T('اجرای تست','Run test'); ?></button></div>
      </div>
    </form>
    <?php if ($dbResult): ?>
      <div style="margin-top:18px;border:1px solid var(--line);border-radius:12px;overflow:hidden">
        <?php foreach ($dbResult as $it): $m=$statusMeta[$it['status']]; ?>
          <div class="row">
            <div class="rl"><?php echo nvd_e(LV($it['label'])); ?><small><?php echo nvd_e(LV($it['note'])); ?></small></div>
            <div class="rv"><span><?php echo T('نتیجه','Result'); ?></span><code><?php echo nvd_e(LV($it['actual'])); ?></code></div>
            <div class="rv"><span><?php echo T('مقدار موردنیاز','Required'); ?></span><code><?php echo nvd_e(LV($it['expected'])); ?></code></div>
            <div class="badge <?php echo $m['cls']; ?>"><?php echo $m['label']; ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- php.ini fixes -->
  <?php if (!empty($iniFixes)): ?>
  <section class="box">
    <h3><?php echo T('تنظیمات php.ini که باید اصلاح شود','php.ini settings to fix'); ?></h3>
    <p class="hint"><?php echo T('این خطوط را در cPanel → MultiPHP INI Editor یا فایل php.ini کنار سایت اعمال کنید. اگر دسترسی ندارید، همین متن را برای پشتیبانی بفرستید.',
        'Apply these in cPanel → MultiPHP INI Editor, or a php.ini next to your site. No access? Send this to support.'); ?></p>
    <pre class="snip"><?php foreach ($iniFixes as $f) echo nvd_e($f)."\n"; ?></pre>
    <div class="note"><?php echo T('روی هاست‌های LiteSpeed و اجرای PHP در حالت CGI/FPM، دستورهای php_value در .htaccess کار نمی‌کنند و باید از php.ini استفاده شود.',
        'On LiteSpeed and CGI/FPM PHP, php_value directives in .htaccess do not work — use php.ini instead.'); ?></div>
  </section>
  <?php endif; ?>

  <!-- Ticket -->
  <section class="box">
    <h3><?php echo T('متن آماده برای ارسال به پشتیبانی هاست','Ready-made text for host support'); ?></h3>
    <p class="hint"><?php echo T('این متن از روی نتایج همین گزارش ساخته شده است. کپی کنید و در تیکت پشتیبانی بفرستید.',
        'Generated from this report. Copy it into your support ticket.'); ?></p>
    <textarea class="snip" id="nvdTicket" readonly><?php echo nvd_e($ticket); ?></textarea>
    <div style="margin-top:12px"><button class="btn btn-p" onclick="nvdCopy()"><?php echo T('کپی متن','Copy text'); ?></button></div>
  </section>

  <!-- Requirements at a glance -->
  <section class="box">
    <h3><?php echo T('الزامات رسمی وردپرس در یک نگاه','Official WordPress requirements at a glance'); ?></h3>
    <p class="hint"><?php echo T('مرجع: صفحه‌ی رسمی الزامات در wordpress.org','Source: the official requirements page at wordpress.org'); ?></p>
    <div class="grid2">
      <div class="note" style="border-color:var(--fz);background:#F2FBFA;color:#0E5B57">
        <b>PHP</b><br><?php echo T('حداقل ۷.۴ · پیشنهادی ۸.۳ به بالا','Min 7.4 · recommended 8.3+'); ?><br>
        <?php echo T('افزونه‌های کلیدی: json، mysqli، mbstring، curl، gd، zip','Key extensions: json, mysqli, mbstring, curl, gd, zip'); ?>
      </div>
      <div class="note" style="border-color:var(--fz);background:#F2FBFA;color:#0E5B57">
        <b><?php echo T('دیتابیس','Database'); ?></b><br>
        <?php echo T('MariaDB از ۱۰.۱۱ (حداقل ۱۰.۴)','MariaDB 10.11+ (min 10.4)'); ?><br>
        <?php echo T('MySQL از ۸.۰ (حداقل ۵.۵.۵)','MySQL 8.0+ (min 5.5.5)'); ?>
      </div>
      <div class="note" style="border-color:var(--fz);background:#F2FBFA;color:#0E5B57">
        <b><?php echo T('وب‌سرور و HTTPS','Web server & HTTPS'); ?></b><br>
        <?php echo T('Apache یا Nginx با mod_rewrite','Apache or Nginx with mod_rewrite'); ?><br>
        <?php echo T('HTTPS برای هر نصب الزامی است','HTTPS required for every install'); ?>
      </div>
      <div class="note" style="border-color:var(--fz);background:#F2FBFA;color:#0E5B57">
        <b><?php echo T('تنظیمات کمینه','Baseline settings'); ?></b><br>
        <?php echo T('memory_limit ۲۵۶M · upload ۶۴M','memory_limit 256M · upload 64M'); ?><br>
        <?php echo T('max_input_vars ۳۰۰۰ · max_execution_time ۶۰+','max_input_vars 3000 · max_execution_time 60+'); ?>
      </div>
    </div>
  </section>

</main>

<footer class="foot">
  <div class="wrap">
    <div class="fgrid">
      <div>
        <h4><?php echo nvd_e(T(NVD_COMPANY_FA,NVD_COMPANY_EN)); ?></h4>
        <p><?php echo T('ما سایت‌ها را نمی‌سازیم که فقط بالا بیایند؛ می‌سازیم که کار کنند. از انتخاب دامنه و میزبانی تا طراحی، سئو و نگهداری ماهانه — همه‌ی مسیر حضور آنلاین کسب‌وکار شما زیر یک سقف مدیریت می‌شود. این ابزار هم بخشی از همان نگاه است: پیش از نصب، مطمئن شوید زیرساخت آماده است.',
            'We don\'t just build sites that launch — we build sites that work. From domain and hosting to design, SEO and monthly care, your entire online presence is handled under one roof. This tool is part of that: make sure the ground is ready before you install.'); ?></p>
        <p style="color:#7F97AC;font-size:12.5px"><?php echo T('تخصص ما در وردپرس، جوملا و توسعه‌ی اختصاصی؛ با پشتیبانی فارسی و عربی برای بازار ایران و عراق.',
            'Expertise in WordPress, Joomla and custom development; with Persian and Arabic support for Iran and Iraq.'); ?></p>
        <p class="weblinks">
          <a href="https://<?php echo nvd_e(NVD_WEB1); ?>" target="_blank" rel="noopener"><?php echo nvd_e(NVD_WEB1); ?></a>
          <a href="https://<?php echo nvd_e(NVD_WEB2); ?>" target="_blank" rel="noopener"><?php echo nvd_e(NVD_WEB2); ?></a>
          <a href="https://<?php echo nvd_e(NVD_WEB3); ?>" target="_blank" rel="noopener"><?php echo nvd_e(NVD_WEB3); ?></a>
          <a href="https://<?php echo nvd_e(NVD_WEB4); ?>" target="_blank" rel="noopener"><?php echo nvd_e(NVD_WEB4); ?></a>
        </p>
      </div>
      <div>
        <h4><?php echo T('خدمات ما','Our services'); ?></h4>
        <ul>
          <li><?php echo T('طراحی و توسعه‌ی وب‌سایت','Website design & development'); ?></li>
          <li><?php echo T('بهینه‌سازی و سئو (SEO)','Search engine optimisation (SEO)'); ?></li>
          <li><?php echo T('میزبانی وب پرسرعت','High-speed web hosting'); ?></li>
          <li><?php echo T('ثبت و انتقال دامنه','Domain registration & transfer'); ?></li>
          <li><?php echo T('بهینه‌سازی نرخ تبدیل (CRO)','Conversion rate optimisation (CRO)'); ?></li>
          <li><?php echo T('تبلیغات و بازاریابی دیجیتال','Advertising & digital marketing'); ?></li>
          <li><?php echo T('پشتیبانی و نگهداری سایت','Site support & maintenance'); ?></li>
          <li><?php echo T('مهاجرت و بهینه‌سازی وردپرس','WordPress migration & tuning'); ?></li>
        </ul>
      </div>
      <div>
        <h4><?php echo T('مشاوره‌ی رایگان','Free consultation'); ?></h4>
        <a class="tel" href="tel:<?php echo nvd_e(NVD_PHONE1); ?>"><b><?php echo nvd_e(NVD_PHONE1); ?></b><span><?php echo T('همراه · واتساپ','Mobile · WhatsApp'); ?></span></a>
        <a class="tel" href="tel:<?php echo nvd_e(NVD_PHONE2); ?>"><b><?php echo nvd_e(NVD_PHONE2); ?></b><span><?php echo T('دفتر مرکزی','Head office'); ?></span></a>
        <p style="font-size:12.5px;margin-top:12px"><?php echo T('گزارش این صفحه را برای ما بفرستید؛ در کمتر از یک روز کاری وضعیت هاست شما را بررسی می‌کنیم.',
            'Send us this report; we\'ll review your hosting within one business day.'); ?></p>
      </div>
    </div>
    <div class="copy">
      <span>© <?php echo nvd_num(date('Y')); ?> <?php echo nvd_e(T(NVD_COMPANY_FA,NVD_COMPANY_EN)); ?> — <?php echo T('کلیه حقوق محفوظ است.','All rights reserved.'); ?></span>
      <span><?php echo nvd_e(NVD_COMPANY_EN); ?> · WordPress Readiness Checker v<?php echo nvd_e(NVD_VERSION); ?></span>
    </div>
  </div>
</footer>

<script>
function nvdCopy(){
  var t=document.getElementById('nvdTicket');
  t.select(); t.setSelectionRange(0,99999);
  var done=false;
  try{done=document.execCommand('copy');}catch(e){}
  if(!done&&navigator.clipboard){navigator.clipboard.writeText(t.value);done=true;}
  alert(done?'<?php echo T('متن کپی شد. آن را در تیکت پشتیبانی بفرستید.','Copied. Paste it into your support ticket.'); ?>':'<?php echo T('کپی نشد؛ متن را دستی انتخاب کنید.','Copy failed; select the text manually.'); ?>');
}
</script>
</body>
</html>
