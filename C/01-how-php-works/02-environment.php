<?php

/*
================================================================================
 TOPIC 01 - EXAMPLE 2 : INSPECTING THE PHP ENVIRONMENT (SAPI, INI, OPCACHE)
================================================================================

 WHAT THIS DEMONSTRATES
   The SAME script behaves differently depending on HOW it is run. This prints
   the SAPI, the php.ini actually in use, the limits that apply, and whether
   OPcache is active - so you can see the CLI vs web difference with your own
   eyes instead of memorising it.

 HOW TO RUN  (do BOTH, then compare the two outputs line by line)
   Command line :  php 02-environment.php extra-arg-1 extra-arg-2
   Browser      :  http://localhost/php_learning_interview_prep/C/01-how-php-works/02-environment.php?id=5

================================================================================
*/

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';

if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');  // plain text in browser too
}

/**
 * Prints one aligned "label : value" row.
 */
function row(string $label, string|int|bool|null $value): void
{
    if (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    }
    printf("%-24s : %s%s", $label, (string) ($value ?? '(none)'), PHP_EOL);
}

echo str_repeat('=', 60) . PHP_EOL;
echo 'PHP ENVIRONMENT REPORT' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

// ---------------------------------------------------------------------------
// 1. WHICH PHP AND WHICH SAPI
// ---------------------------------------------------------------------------
row('PHP version', PHP_VERSION);
row('SAPI', PHP_SAPI);                       // cli | apache2handler | fpm-fcgi
row('Running in CLI', $isCli);
row('Operating system', PHP_OS_FAMILY);      // Windows | Linux | Darwin
row('Architecture (int size)', PHP_INT_SIZE . ' bytes');

// ---------------------------------------------------------------------------
// 2. CONFIGURATION - the #1 cause of "works in browser, fails in terminal"
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- CONFIGURATION --' . PHP_EOL;
row('Loaded php.ini', php_ini_loaded_file() ?: 'none');
row('Extra .ini files', php_ini_scanned_files() ?: 'none');
row('memory_limit', ini_get('memory_limit'));
row('max_execution_time', ini_get('max_execution_time') . ' s (0 = unlimited)');
row('display_errors', ini_get('display_errors'));
row('error_reporting', (string) error_reporting());
row('date.timezone', ini_get('date.timezone') ?: '(not set)');
row('upload_max_filesize', ini_get('upload_max_filesize'));
row('post_max_size', ini_get('post_max_size'));

// ---------------------------------------------------------------------------
// 3. OPCACHE - caches compiled OPCODES, not page output
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- OPCACHE --' . PHP_EOL;

// function_exists() first: the extension may not be installed at all, and
// calling a missing function is a fatal error.
if (function_exists('opcache_get_status')) {
    // false = "do not include the full list of cached scripts" (keeps it cheap)
    $status = @opcache_get_status(false);

    if (is_array($status) && ($status['opcache_enabled'] ?? false)) {
        $hits   = $status['opcache_statistics']['hits'] ?? 0;
        $misses = $status['opcache_statistics']['misses'] ?? 0;
        $total  = $hits + $misses;

        row('OPcache enabled', true);
        row('Cached scripts', $status['opcache_statistics']['num_cached_scripts'] ?? 0);
        row('Hit rate', $total > 0 ? round($hits / $total * 100, 2) . ' %' : 'n/a');
        row('Memory used', round(($status['memory_usage']['used_memory'] ?? 0) / 1048576, 2) . ' MB');
    } else {
        row('OPcache enabled', false);
        echo '  (normal for CLI - OPcache is usually disabled there because a' . PHP_EOL;
        echo '   CLI process dies immediately and cannot reuse the cache)' . PHP_EOL;
    }
} else {
    row('OPcache extension', 'not installed');
}

// ---------------------------------------------------------------------------
// 4. REQUEST vs COMMAND LINE INPUT
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- INPUT --' . PHP_EOL;

if ($isCli) {
    // CLI receives $argv / $argc. There is no $_GET or $_POST.
    row('Script name ($argv[0])', basename($argv[0]));
    row('Argument count', $argc - 1);
    row('Arguments', implode(', ', array_slice($argv, 1)) ?: '(none passed)');
    row('$_GET exists', isset($_GET) && $_GET !== [] ? 'yes' : 'empty in CLI');
} else {
    // Web receives superglobals. There is no $argv.
    row('Request method', $_SERVER['REQUEST_METHOD'] ?? '-');
    row('Request URI', $_SERVER['REQUEST_URI'] ?? '-');
    row('Client IP', $_SERVER['REMOTE_ADDR'] ?? '-');
    row('$_GET keys', implode(', ', array_keys($_GET)) ?: '(none)');
    row('$argv exists', 'no - CLI only');
}

// ---------------------------------------------------------------------------
// 5. MEMORY USED BY THIS REQUEST
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- MEMORY --' . PHP_EOL;
row('Current usage', round(memory_get_usage(true) / 1048576, 2) . ' MB');
row('Peak usage', round(memory_get_peak_usage(true) / 1048576, 2) . ' MB');

echo str_repeat('=', 60) . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 PHP_SAPI
     A constant holding the SAPI name. Values you will meet:
       cli             run from the terminal
       apache2handler  mod_php inside Apache (this is what XAMPP uses)
       fpm-fcgi        PHP-FPM (the production standard)
     Use it whenever code must behave differently in a terminal and a browser.

 printf("%-24s : %s%s", ...)
     printf writes formatted output. "%-24s" means: a string, left-aligned,
     padded to 24 characters - that is what keeps the colons in a straight
     column. The final %s is PHP_EOL so the line ending is correct on Windows
     and Linux.

 string|int|bool|null $value   (in the row() signature)
     A UNION TYPE (PHP 8.0+). It says the parameter accepts any of these four
     types. Before PHP 8 you could not express this and had to use no type at
     all.

 php_ini_loaded_file()
     The full path of the php.ini actually in use, or false if none was loaded.
     THIS IS THE IMPORTANT LINE OF THE WHOLE SCRIPT. XAMPP's CLI and Apache
     frequently load DIFFERENT ini files, which is why an extension can work in
     the browser and be missing in the terminal.

 php_ini_scanned_files()
     Additional .ini files loaded from a conf.d directory (common on Linux).

 ini_get('memory_limit')
     Reads a directive at runtime and returns it as a STRING such as "512M".
     memory_limit is per request. max_execution_time is 0 (unlimited) in CLI
     but typically 30 seconds for web requests - that difference alone explains
     most "it works in the terminal" bug reports.

 function_exists('opcache_get_status')
     Guards against a fatal error when the extension is absent. Always check
     before calling an optional extension's functions.

 opcache_get_status(false)
     Returns an array of statistics, or false when OPcache is disabled. The
     false argument omits the (potentially huge) list of cached scripts.
     The HIT RATE is the number to watch in production: it should be above 95%.
     A low rate means the cache is too small or files keep changing.

 $argv / $argc
     Only exist in CLI. $argv[0] is always the script filename, so the real
     arguments start at index 1 - which is why array_slice($argv, 1) is used.

 memory_get_usage(true) / 1048576
     Bytes to megabytes (1024 * 1024 = 1048576). Passing true reports the
     memory actually allocated from the operating system rather than PHP's
     internal accounting, which is the more honest figure.

================================================================================
 EXPECTED OUTPUT  (CLI, abbreviated - your paths and numbers will differ)
================================================================================

   ============================================================
   PHP ENVIRONMENT REPORT
   ============================================================
   PHP version              : 8.5.4
   SAPI                     : cli
   Running in CLI           : yes
   Operating system         : Windows
   Architecture (int size)  : 8 bytes

   -- CONFIGURATION --
   Loaded php.ini           : C:\xampp\php\php.ini
   memory_limit             : 512M
   max_execution_time       : 0 s (0 = unlimited)      <- web would show 30
   ...

   -- INPUT --
   Script name ($argv[0])   : 02-environment.php
   Argument count           : 2
   Arguments                : extra-arg-1, extra-arg-2

 In the BROWSER the same script prints apache2handler, a max_execution_time of
 30, a REQUEST_METHOD row instead of arguments, and possibly a different
 php.ini path.

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. CLI and web are two different environments running the same code.
  2. They often load DIFFERENT php.ini files - always check with
     php_ini_loaded_file() or "php --ini" before debugging further.
  3. max_execution_time is unlimited in CLI, limited on the web. Long jobs
     belong in CLI workers, not web requests.
  4. OPcache caches compiled opcodes. It is normally off in CLI because the
     process dies immediately.

 INTERVIEW LINK
   "What is the difference between running PHP in CLI and on the web?"
   "What does OPcache cache?"
   "A script works in the terminal but times out in the browser. Why?"

 TRY THIS
   Run "php --ini" and "php -m" in your terminal, then open
   http://localhost/dashboard/phpinfo.php in XAMPP and compare the two.
================================================================================
*/
