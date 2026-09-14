<?php

/*
================================================================================
 TOPIC 01 - EXAMPLE 1 : PROVING THE SHARED-NOTHING ARCHITECTURE
================================================================================

 WHAT THIS DEMONSTRATES
   PHP destroys ALL memory at the end of every request. This script proves it by
   running four counters side by side and showing which ones survive a reload
   and which ones reset to zero.

 HOW TO RUN
   Command line :  php 01-shared-nothing.php      <- run it 3 or 4 times
   Browser      :  http://localhost/php_learning_interview_prep/C/01-how-php-works/01-shared-nothing.php
                   <- refresh 3 or 4 times

   Run it MORE THAN ONCE. A single run proves nothing - the whole point is what
   happens on the second and third run.

================================================================================
*/

declare(strict_types=1);

// In CLI there is no browser cookie, so PHP would create a brand new session on
// every run and the demo would not work. Fixing the session id makes the CLI
// behave like a browser that keeps sending the same PHPSESSID cookie.
if (PHP_SAPI === 'cli') {
    session_id('cli-demo-session');
}
session_start();

// ---------------------------------------------------------------------------
// COUNTER 1 : a plain variable  ->  lives in request memory
// ---------------------------------------------------------------------------
$plainCounter = 0;
$plainCounter++;

// ---------------------------------------------------------------------------
// COUNTER 2 : a static variable inside a function
//             -> survives between CALLS, but still dies with the request
// ---------------------------------------------------------------------------
function countCalls(): int
{
    static $calls = 0;   // the "= 0" runs only on the FIRST call
    $calls++;
    return $calls;
}

countCalls();                    // call 1
countCalls();                    // call 2
$staticCounter = countCalls();   // call 3  -> returns 3, every single request

// ---------------------------------------------------------------------------
// COUNTER 3 : the session  ->  stored OUTSIDE PHP memory (file / Redis / DB)
// ---------------------------------------------------------------------------
$_SESSION['visits'] = ($_SESSION['visits'] ?? 0) + 1;
$sessionCounter = $_SESSION['visits'];

// ---------------------------------------------------------------------------
// COUNTER 4 : a file on disk  ->  the simplest possible external storage
// ---------------------------------------------------------------------------
$counterFile = __DIR__ . '/.hits.txt';                 // __DIR__ = this folder
$fileCounter = (int) @file_get_contents($counterFile); // 0 if the file is absent
$fileCounter++;
file_put_contents($counterFile, (string) $fileCounter, LOCK_EX);

// ---------------------------------------------------------------------------
// OUTPUT
// ---------------------------------------------------------------------------
$isCli = PHP_SAPI === 'cli';
$nl    = $isCli ? PHP_EOL : '<br>' . PHP_EOL;

if (!$isCli) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<pre>';
}

echo 'SAPI                     : ' . PHP_SAPI . $nl;
echo '-----------------------------------------------' . $nl;
echo 'Plain variable           : ' . $plainCounter   . '   (always 1)' . $nl;
echo 'Static inside function   : ' . $staticCounter  . '   (always 3)' . $nl;
echo 'Session value            : ' . $sessionCounter . '   (grows)'    . $nl;
echo 'File on disk             : ' . $fileCounter    . '   (grows)'    . $nl;
echo '-----------------------------------------------' . $nl;
echo 'Run this script again and compare the numbers.'  . $nl;

if (!$isCli) {
    echo '</pre>';
}

/*
================================================================================
 CODE EXPLANATION
================================================================================

 session_id('cli-demo-session')
     A session is normally identified by the PHPSESSID cookie the browser sends
     back. The CLI has no cookies, so without this line every run would start a
     fresh session and the session counter would always show 1. Setting a fixed
     id makes the CLI reuse the same session file each run.
     NOTE: never hard-code a session id in real code. This is a teaching trick.

 session_start()
     Loads the stored session data from the save path (a file in PHP's temp
     directory by default) and fills $_SESSION. At the end of the request PHP
     writes $_SESSION back to that file. That write/read cycle is the ONLY
     reason the value survives - PHP memory itself does not.

 static $calls = 0;
     "static" means: keep this variable alive between calls to this function,
     within this request. The initialiser runs once. This is why the value is 3
     after three calls - but it is 3 on EVERY run, never 6 or 9, because the
     variable is destroyed when the request ends.

 ($_SESSION['visits'] ?? 0) + 1
     ?? is the null coalescing operator: use the left value if it exists and is
     not null, otherwise use 0. Without it the first visit would emit
     "Warning: Undefined array key".

 (int) @file_get_contents($counterFile)
     file_get_contents returns the file contents as a STRING, so the (int) cast
     converts "5" to 5. The @ suppresses the warning when the file does not
     exist yet on the very first run. (@ is normally bad practice - here it
     keeps the example to one line. The correct production form is:
        $n = is_file($f) ? (int) file_get_contents($f) : 0;)

 file_put_contents($counterFile, (string) $fileCounter, LOCK_EX)
     LOCK_EX takes an exclusive lock while writing so two simultaneous requests
     cannot corrupt the file. Without it, concurrent hits can lose counts.

 PHP_SAPI === 'cli'
     Detects how the script is being run so the output uses \n in the terminal
     and <br> in the browser. Real applications do this in a small helper.

================================================================================
 EXPECTED OUTPUT  (3 consecutive runs)
================================================================================

   RUN 1                            RUN 2                            RUN 3
   Plain variable         : 1       Plain variable         : 1       Plain variable         : 1
   Static inside function : 3       Static inside function : 3       Static inside function : 3
   Session value          : 1       Session value          : 2       Session value          : 3
   File on disk           : 1       File on disk           : 2       File on disk           : 3

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Request memory (plain and static variables) NEVER survives a request.
  2. Only EXTERNAL storage survives: session, file, database, Redis, cookie.
  3. "static" means per-request, not per-server. This is the single most common
     misunderstanding of the keyword.
  4. This is why sessions exist at all, and why caching is a separate system.

 INTERVIEW LINK
   "Why doesn't my variable keep its value on the next page?"
   "What does shared-nothing mean?"
   "Does static persist between requests?"      -> No. This script proves it.

 CLEAN UP
   Delete the generated .hits.txt when you are done experimenting.
================================================================================
*/
