<?php

/*
================================================================================
 TOPIC 01 - EXAMPLE 4 : A REAL REQUEST PROFILER  (project-style code)
================================================================================

 WHAT THIS DEMONSTRATES
   Everything from this topic combined into a tool you could genuinely drop into
   a project: it measures how long a request took and how much memory it used,
   reports it in the correct format for CLI or browser, and logs any request
   slower than a threshold to a file.

   This is the solution to Exercise 3 in 00-NOTES.txt. Read it AFTER trying it
   yourself.

 HOW TO RUN
   php 04-request-profiler.php            <- fast request  (not logged)
   php 04-request-profiler.php slow       <- 300 ms request (logged)
   Browser: .../04-request-profiler.php?mode=slow

   Then open the generated slow-requests.log file.

================================================================================
*/

declare(strict_types=1);

// =============================================================================
// THE PROFILER  (in a real project this would be src/Support/Profiler.php)
// =============================================================================

final class Profiler
{
    private const int SLOW_THRESHOLD_MS = 200;

    private int $startTime;
    private int $startMemory;

    public function __construct(
        private readonly string $logFile
    ) {
        $this->startTime   = hrtime(true);            // nanoseconds, monotonic
        $this->startMemory = memory_get_usage(true);  // bytes
    }

    /**
     * Registers the profiler to report itself when the request ends.
     * Because it uses register_shutdown_function(), it reports even if the
     * script calls exit() or dies from a fatal error.
     */
    public function start(): void
    {
        register_shutdown_function([$this, 'report']);
    }

    public function report(): void
    {
        $durationMs = (hrtime(true) - $this->startTime) / 1_000_000;
        $peakMb     = memory_get_peak_usage(true) / 1048576;
        $growthMb   = (memory_get_usage(true) - $this->startMemory) / 1048576;

        $summary = sprintf(
            'time=%.2fms peak=%.2fMB growth=%.2fMB queries=n/a',
            $durationMs,
            $peakMb,
            $growthMb
        );

        // ---- 1. Show it, in the right format for this SAPI ----------------
        if (PHP_SAPI === 'cli') {
            echo PHP_EOL . '[profiler] ' . $summary . PHP_EOL;
        } else {
            // An HTML comment cannot break the page layout, unlike an echo.
            echo PHP_EOL . '<!-- profiler: ' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . ' -->';
        }

        // ---- 2. Log it, but only when it is actually slow ------------------
        if ($durationMs >= self::SLOW_THRESHOLD_MS) {
            $this->logSlowRequest($durationMs, $peakMb);
        }
    }

    private function logSlowRequest(float $durationMs, float $peakMb): void
    {
        $line = sprintf(
            "[%s] %-4s %-45s %8.2fms  peak=%.2fMB%s",
            date('Y-m-d H:i:s'),
            PHP_SAPI === 'cli' ? 'CLI' : ($_SERVER['REQUEST_METHOD'] ?? '?'),
            $this->currentTarget(),
            $durationMs,
            $peakMb,
            PHP_EOL
        );

        // FILE_APPEND adds to the file instead of overwriting it.
        // LOCK_EX prevents two concurrent requests from interleaving lines.
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function currentTarget(): string
    {
        if (PHP_SAPI === 'cli') {
            return basename($_SERVER['argv'][0] ?? 'script')
                 . ' ' . implode(' ', array_slice($_SERVER['argv'] ?? [], 1));
        }

        return $_SERVER['REQUEST_URI'] ?? '/';
    }
}

// =============================================================================
// BOOTSTRAP - this is all the application code has to write
// =============================================================================

$profiler = new Profiler(__DIR__ . '/slow-requests.log');
$profiler->start();

// =============================================================================
// SIMULATED APPLICATION WORK
// =============================================================================

$mode = $_SERVER['argv'][1] ?? ($_GET['mode'] ?? 'fast');

echo 'Handling request in "' . $mode . '" mode...' . PHP_EOL;

if ($mode === 'slow') {
    usleep(300_000);                       // 300 ms - simulates a slow query
    echo 'Did some slow work (300 ms).' . PHP_EOL;
} else {
    $sum = array_sum(range(1, 100_000));   // a few milliseconds of real work
    echo 'Did some fast work. Sum = ' . $sum . PHP_EOL;
}

echo 'Response ready.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 private const int SLOW_THRESHOLD_MS = 200;
     A TYPED class constant (PHP 8.3+). Making the threshold a constant instead
     of a magic number scattered in the code means there is exactly one place to
     change it. "private" because nothing outside the class needs it.

 public function __construct(private readonly string $logFile)
     Promotion + readonly (8.1). The log path is injected rather than hard-coded
     inside the class, so a test can point it at a temporary file. readonly
     guarantees nothing can change it after construction.

 $this->startTime = hrtime(true);
     Captured in the CONSTRUCTOR, so timing starts the moment the profiler is
     created - as early as possible in the request. In a real framework this
     object is created in the very first lines of the bootstrap file.

 register_shutdown_function([$this, 'report']);
     [$this, 'report'] is the ARRAY CALLABLE form: "call the report() method on
     this object". It is one of PHP's four callable forms (see file 33).
     Registering at shutdown means the report is produced even if the
     application later calls exit() or crashes - exactly what you need from a
     profiler.

 (hrtime(true) - $this->startTime) / 1_000_000
     Nanoseconds to milliseconds. hrtime() is monotonic, so unlike microtime()
     it cannot go backwards when the system clock is adjusted.

 memory_get_peak_usage(true) vs memory_get_usage(true)
     PEAK is the highest point reached during the request - the number that
     tells you whether you are near memory_limit. CURRENT is the value right
     now. Reporting both, plus the growth since start, tells you whether the
     request leaked or just briefly spiked.

 sprintf('time=%.2fms ...', ...)
     %.2f formats a float to exactly 2 decimal places. sprintf RETURNS the
     string (printf would print it), which is what we want because the same
     string is used twice - once for display and once for the log.

 htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')
     Even for an HTML comment, output is escaped. The habit matters more than
     this specific case: any value written into an HTML page gets escaped, with
     no exceptions to remember. See file 56.

 The HTML comment trick
     Printing "<!-- profiler: ... -->" adds the information to the page without
     disturbing the layout, and it never appears to end users unless they view
     source. Real debug toolbars do a richer version of this.

 file_put_contents($file, $line, FILE_APPEND | LOCK_EX)
     FILE_APPEND adds to the end instead of replacing the file. LOCK_EX takes an
     exclusive lock so two simultaneous requests cannot interleave half-written
     lines. The | is a bitwise OR combining the two flags - this is the standard
     way PHP passes multiple flags to one argument.

 date('Y-m-d H:i:s')
     A sortable timestamp format. Always log in a sortable format; never
     'd/m/Y', which cannot be sorted as text.

 %-45s in the log format
     Left-aligns the URI in a 45-character column so the log stays readable as a
     table when you open it.

 WHY THE THRESHOLD CHECK MATTERS
     Logging every request would produce gigabytes and hide the real problems.
     Logging only what exceeds a threshold is what production systems do.

================================================================================
 EXPECTED OUTPUT
================================================================================

 php 04-request-profiler.php
   Handling request in "fast" mode...
   Did some fast work. Sum = 5000050000
   Response ready.

   [profiler] time=2.41ms peak=4.00MB growth=0.00MB queries=n/a
   (nothing written to the log - it was under 200 ms)

 php 04-request-profiler.php slow
   Handling request in "slow" mode...
   Did some slow work (300 ms).
   Response ready.

   [profiler] time=302.77ms peak=2.00MB growth=0.00MB queries=n/a

 slow-requests.log then contains:
   [2026-09-05 23:10:44] CLI  04-request-profiler.php slow      302.77ms  peak=2.00MB

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. register_shutdown_function() is how you attach cross-cutting behaviour
     (profiling, logging, error capture) to the END of every request.
  2. Measure with hrtime(), not microtime(), for durations.
  3. Report peak memory, not current memory, when checking against memory_limit.
  4. Threshold-based logging keeps logs useful. Log the slow ones, not all.
  5. Behave correctly under both SAPIs - one code path per SAPI, decided once.

 INTERVIEW LINK
   "How would you find out why a page is slow?"
   "How do you log fatal errors?"
   "What is the difference between memory_get_usage and memory_get_peak_usage?"
   "How would you add profiling to an existing application without touching
    every file?"   -> one object in the bootstrap + a shutdown callback.

 EXTEND IT (good practice)
   - Count database queries by wrapping PDO and incrementing a counter.
   - Add the number of included files: count(get_included_files()).
   - Write the log as JSON lines so a log aggregator can parse it.

 CLEAN UP
   Delete slow-requests.log when you are done experimenting.
================================================================================
*/
