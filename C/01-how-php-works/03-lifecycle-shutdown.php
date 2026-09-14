<?php

/*
================================================================================
 TOPIC 01 - EXAMPLE 3 : THE REQUEST SHUTDOWN STAGE (DESTRUCTORS + CALLBACKS)
================================================================================

 WHAT THIS DEMONSTRATES
   Stage 5 of the request lifecycle. You will SEE the exact order in which PHP
   tears a request down: normal code finishes, then shutdown callbacks run,
   then objects are destroyed. You will also see that a shutdown function runs
   even when the script exits early or hits a fatal error - which is why it is
   the standard place to catch and log fatal errors.

 HOW TO RUN
   php 03-lifecycle-shutdown.php            <- normal run
   php 03-lifecycle-shutdown.php exit       <- run that calls exit() early
   php 03-lifecycle-shutdown.php fatal      <- run that triggers a fatal error

   Run all three and compare. That comparison is the whole lesson.

================================================================================
*/

declare(strict_types=1);

$mode = $argv[1] ?? ($_GET['mode'] ?? 'normal');   // normal | exit | fatal

// ---------------------------------------------------------------------------
// A class whose destructor announces itself, so we can see WHEN it runs.
// ---------------------------------------------------------------------------
final class Connection
{
    // Constructor property promotion (PHP 8.0): declares AND assigns $name.
    public function __construct(private string $name)
    {
        echo "  [OPEN ]  connection '{$this->name}' created" . PHP_EOL;
    }

    public function query(string $sql): void
    {
        echo "  [QUERY]  {$this->name}: {$sql}" . PHP_EOL;
    }

    // Magic method. Runs when the last reference disappears, or at shutdown.
    public function __destruct()
    {
        echo "  [CLOSE]  connection '{$this->name}' destroyed" . PHP_EOL;
    }
}

// ---------------------------------------------------------------------------
// SHUTDOWN CALLBACK 1 : always runs, even after exit() or a fatal error
// ---------------------------------------------------------------------------
register_shutdown_function(function () use ($mode): void {
    echo PHP_EOL . '--- SHUTDOWN STAGE ---' . PHP_EOL;
    echo "  [SHUT ]  first registered callback (mode was: {$mode})" . PHP_EOL;
});

// ---------------------------------------------------------------------------
// SHUTDOWN CALLBACK 2 : the real-world pattern - catching FATAL errors
// A fatal error cannot be caught with try/catch. This is how you log it.
// ---------------------------------------------------------------------------
register_shutdown_function(function (): void {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo "  [FATAL]  caught by shutdown handler: {$error['message']}" . PHP_EOL;
        echo "           in {$error['file']} line {$error['line']}" . PHP_EOL;
        // In production you would log this, not echo it:
        //   error_log(sprintf('[FATAL] %s in %s:%d', $error['message'], $error['file'], $error['line']));
    }

    $ms = (hrtime(true) - START_TIME) / 1_000_000;
    printf("  [STATS]  %.2f ms, peak memory %.2f MB%s",
        $ms,
        memory_get_peak_usage(true) / 1048576,
        PHP_EOL
    );
});

// hrtime(true) returns nanoseconds from a monotonic clock - the correct tool
// for measuring durations (unlike time(), it cannot jump when the clock syncs).
define('START_TIME', hrtime(true));

// ---------------------------------------------------------------------------
// THE ACTUAL WORK OF THE "REQUEST"
// ---------------------------------------------------------------------------
echo '--- EXECUTION STAGE ---' . PHP_EOL;

$primary = new Connection('primary');
$primary->query('SELECT * FROM orders LIMIT 10');

// This object is created and immediately becomes unreachable, so its destructor
// runs straight away - NOT at shutdown. Destructors run when the refcount hits
// zero, whichever comes first.
$temporary = new Connection('temporary');
unset($temporary);                       // <- watch where [CLOSE] appears

echo '  [WORK ]  request work finished' . PHP_EOL;

if ($mode === 'exit') {
    echo '  [EXIT ]  calling exit() now' . PHP_EOL;
    exit(0);                             // shutdown callbacks STILL run
}

if ($mode === 'fatal') {
    echo '  [FATAL]  calling an undefined function now' . PHP_EOL;
    /** @phpstan-ignore-next-line - deliberate fatal error for the demo */
    this_function_does_not_exist();      // Fatal error: shutdown STILL runs
}

echo '  [END  ]  end of script reached normally' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 $argv[1] ?? ($_GET['mode'] ?? 'normal')
     Reads the mode from the command line if present, otherwise from the query
     string, otherwise defaults. The ?? chain avoids "undefined index" warnings
     in both environments.

 public function __construct(private string $name)
     Constructor property promotion. Writing "private string $name" in the
     signature declares the property, types it, and assigns it - replacing
     three lines of boilerplate. (PHP 8.0+)

 __destruct()
     A magic method PHP calls automatically when an object is destroyed. That
     happens either when the last variable pointing to it goes away (see the
     unset($temporary) line) or during request shutdown for everything still
     alive. Real uses: closing file handles, releasing locks, flushing buffers.

 register_shutdown_function(callable)
     Registers a function to run in the RSHUTDOWN stage. Key properties:
       - it runs even after exit() / die()
       - it runs even after a FATAL error
       - multiple callbacks run in the order they were registered
     That combination is why it is the standard way to catch fatal errors.

 error_get_last()
     Returns the last error as an array with keys type, message, file, line -
     or null if there was none. Inside a shutdown function this is how you find
     out whether the request died from a fatal error.

 in_array($error['type'], [E_ERROR, E_PARSE, ...], true)
     Filters for FATAL error types only, so ordinary warnings and notices do
     not trigger the handler. The third argument true means strict comparison
     (no type juggling) - always pass it to in_array().

 hrtime(true)
     Returns nanoseconds from a high-resolution MONOTONIC clock. Use it for
     measuring elapsed time. Do not use time() or microtime() for durations:
     those follow the system clock, which can jump backwards during an NTP
     sync and produce negative durations.

 (hrtime(true) - START_TIME) / 1_000_000
     Nanoseconds to milliseconds. The underscores are numeric literal
     separators (PHP 7.4+) and are ignored by PHP - they exist purely so a
     human can read 1_000_000 at a glance.

 define('START_TIME', hrtime(true))
     define() is used rather than const because the value is computed at
     RUNTIME. const would be a compile-time error here.

================================================================================
 EXPECTED OUTPUT
================================================================================

 RUN 1 - normal:
   --- EXECUTION STAGE ---
     [OPEN ]  connection 'primary' created
     [QUERY]  primary: SELECT * FROM orders LIMIT 10
     [OPEN ]  connection 'temporary' created
     [CLOSE]  connection 'temporary' destroyed     <- immediately, not at shutdown
     [WORK ]  request work finished
     [END  ]  end of script reached normally

   --- SHUTDOWN STAGE ---
     [SHUT ]  first registered callback (mode was: normal)
     [STATS]  0.9x ms, peak memory 2.00 MB
     [CLOSE]  connection 'primary' destroyed       <- last, at teardown

 RUN 2 - exit:  identical, except [END] is replaced by [EXIT] and the shutdown
                stage STILL runs afterwards.

 RUN 3 - fatal: PHP prints its "Fatal error: Uncaught Error: Call to undefined
                function" message, and the shutdown stage STILL runs, with the
                [FATAL] line showing that error_get_last() captured it.

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Shutdown order: your code -> registered shutdown callbacks -> remaining
     destructors. Do not depend on destructor order at shutdown; if the order
     matters, close the resource explicitly.
  2. A destructor can run EARLY - as soon as the last reference is gone.
  3. register_shutdown_function() survives exit() and fatal errors. That is the
     ONLY way to log a fatal error, because try/catch cannot catch one.
  4. hrtime() is the correct clock for measuring durations.

 INTERVIEW LINK
   "How do you catch a fatal error in PHP?"          -> shutdown function
   "When does a destructor run?"                     -> refcount zero, or shutdown
   "Describe the request lifecycle."                 -> this script is stage 5
   "How would you log slow requests?"                -> see example 04

 SAFETY NOTE
   Mode 'fatal' deliberately triggers a fatal error. That is intentional and
   safe here - the script does nothing but print.
================================================================================
*/
