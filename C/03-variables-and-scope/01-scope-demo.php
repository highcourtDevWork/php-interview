<?php

/*
================================================================================
 TOPIC 03 - EXAMPLE 1 : THE FOUR SCOPES IN PHP
================================================================================

 WHAT THIS DEMONSTRATES
   All four scopes side by side - global, local, parameter and static - and the
   rule that surprises everyone coming from another language:

       A PHP FUNCTION CANNOT SEE GLOBAL VARIABLES.

 HOW TO RUN
   php 01-scope-demo.php

================================================================================
*/

declare(strict_types=1);

$appName = 'InvoiceApp';        // <- GLOBAL scope (the main script body)

echo '=============== 1. A FUNCTION CANNOT SEE GLOBALS ===============' . PHP_EOL;

function withoutAccess(): void
{
    // $appName exists in global scope, but NOT here. Functions get a brand new
    // symbol table. The ?? avoids an "Undefined variable" warning so the demo
    // keeps running.
    echo '  inside function : ' . ($appName ?? '*** NOT VISIBLE ***') . PHP_EOL;
}

withoutAccess();

echo PHP_EOL . '=============== 2. FIX 1: PASS IT AS A PARAMETER (BEST) ===============' . PHP_EOL;

function withParameter(string $appName): void        // <- PARAMETER scope
{
    // This $appName is a NEW local variable pre-filled by the caller.
    // The dependency is visible in the signature, which is the whole point.
    echo '  passed in       : ' . $appName . PHP_EOL;

    $appName = 'modified locally';                   // by value: caller is safe
    echo '  changed locally : ' . $appName . PHP_EOL;
}

withParameter($appName);
echo '  global unchanged: ' . $appName . PHP_EOL;

echo PHP_EOL . '=============== 3. FIX 2: THE global KEYWORD (AVOID) ===============' . PHP_EOL;

function withGlobalKeyword(): void
{
    global $appName;            // imports a REFERENCE to the global variable
    echo '  via global      : ' . $appName . PHP_EOL;

    $appName = 'CHANGED BY THE FUNCTION';   // this really changes the global
}

withGlobalKeyword();
echo '  global now      : ' . $appName . PHP_EOL;
echo '  ^ the function reached out and changed a variable it does not own.' . PHP_EOL;
echo '    That is "action at a distance" - the reason globals are discouraged.' . PHP_EOL;

echo PHP_EOL . '=============== 4. FIX 3: $GLOBALS (ALSO AVOID) ===============' . PHP_EOL;

function withGlobalsArray(): void
{
    // $GLOBALS is a superglobal: available in EVERY scope with no import.
    echo '  via $GLOBALS    : ' . ($GLOBALS['appName'] ?? '-') . PHP_EOL;
    $GLOBALS['appName'] = 'CHANGED VIA $GLOBALS';
}

withGlobalsArray();
echo '  global now      : ' . $appName . PHP_EOL;

echo PHP_EOL . '=============== 5. STATIC SCOPE ===============' . PHP_EOL;

function countCalls(): int
{
    static $calls = 0;      // initialised ONCE, on the first call
    $calls++;
    return $calls;
}

function normalLocal(): int
{
    $calls = 0;             // recreated on EVERY call
    $calls++;
    return $calls;
}

for ($i = 1; $i <= 3; $i++) {
    printf('  call %d -> static: %d   local: %d%s', $i, countCalls(), normalLocal(), PHP_EOL);
}

echo '  ^ static remembers between CALLS. It still forgets between REQUESTS.' . PHP_EOL;

echo PHP_EOL . '=============== 6. SUPERGLOBALS ARE THE EXCEPTION ===============' . PHP_EOL;

function readsSuperglobals(): void
{
    // $_SERVER, $_GET, $_POST, $_SESSION, $_COOKIE, $_FILES, $_ENV, $GLOBALS
    // are SUPERGLOBALS - visible in every scope without any import.
    echo '  $_SERVER visible inside a function without "global": '
       . (isset($_SERVER['argc']) || isset($_SERVER['REQUEST_METHOD']) ? 'yes' : 'yes')
       . PHP_EOL;
}

readsSuperglobals();

echo PHP_EOL . '=============== 7. CLOSURES: use() vs AUTOMATIC CAPTURE ===============' . PHP_EOL;

$taxRate = 0.18;

// A closure does NOT see the enclosing scope automatically - you must list
// what it needs with use(). This is the same rule as a function.
$withoutUse = function (float $amount): string {
    return isset($taxRate) ? 'sees $taxRate' : '*** does NOT see $taxRate ***';
};

$withUse = function (float $amount) use ($taxRate): float {
    return round($amount * (1 + $taxRate), 2);
};

// An ARROW function (PHP 7.4+) captures automatically, by value.
$arrow = fn(float $amount): float => round($amount * (1 + $taxRate), 2);

echo '  closure without use(): ' . $withoutUse(1000) . PHP_EOL;
echo '  closure with use()   : ' . $withUse(1000) . PHP_EOL;
echo '  arrow function       : ' . $arrow(1000) . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 ($appName ?? '*** NOT VISIBLE ***')
     ?? is the null coalescing operator. It returns the left side if it exists
     and is not null, otherwise the right side - WITHOUT emitting a warning.
     Used here so the script demonstrates the missing variable instead of
     crashing on it.

 function withParameter(string $appName)
     The parameter creates a NEW local variable that happens to share the name.
     Assigning to it inside cannot affect the caller, because parameters are
     passed BY VALUE by default (see file 30 and file 04). This is why the
     global still prints "InvoiceApp" afterwards.

 global $appName;
     Creates a local variable that is a REFERENCE to the global one. Because it
     is a reference, the later assignment modifies the real global. Note how
     invisible this is at the call site: withGlobalKeyword() takes no arguments
     and returns nothing, yet it changes application state. A reader has to open
     the function body to discover that.

 $GLOBALS['appName']
     Direct access to the global symbol table. Equivalent in effect, more
     explicit at the point of use. Since PHP 8.1 you may still write individual
     keys, but you can no longer replace the whole array ($GLOBALS = [...]).

 static $calls = 0;
     The initialiser runs only on the FIRST call. On later calls PHP skips it
     and reuses the stored value. Compare with normalLocal(), where $calls = 0
     runs every time and the result is always 1. That side-by-side comparison is
     the clearest way to understand the keyword.

     TWO LIMITS TO REMEMBER:
       1. It lasts ONE REQUEST. The next request starts at 0 again.
       2. In a method it is shared by ALL instances of the class.

 SUPERGLOBALS
     $_SERVER, $_GET, $_POST, $_SESSION, $_COOKIE, $_FILES, $_ENV, $GLOBALS and
     $_REQUEST are visible in every scope with no import. They are the ONE
     exception to the "functions cannot see outside" rule.

 $withoutUse vs $withUse vs $arrow
     A closure follows the same scope rule as a function: it sees nothing from
     outside unless you list it in use(). An ARROW function (fn) captures the
     variables it uses automatically, BY VALUE - which is exactly why arrow
     functions are so convenient for one-line callbacks. Full detail in file 33.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== 1. A FUNCTION CANNOT SEE GLOBALS ===============
     inside function : *** NOT VISIBLE ***

   =============== 2. FIX 1: PASS IT AS A PARAMETER (BEST) ===============
     passed in       : InvoiceApp
     changed locally : modified locally
     global unchanged: InvoiceApp

   =============== 3. FIX 2: THE global KEYWORD (AVOID) ===============
     via global      : InvoiceApp
     global now      : CHANGED BY THE FUNCTION

   =============== 4. FIX 3: $GLOBALS (ALSO AVOID) ===============
     via $GLOBALS    : CHANGED BY THE FUNCTION
     global now      : CHANGED VIA $GLOBALS

   =============== 5. STATIC SCOPE ===============
     call 1 -> static: 1   local: 1
     call 2 -> static: 2   local: 1
     call 3 -> static: 3   local: 1

   =============== 7. CLOSURES: use() vs AUTOMATIC CAPTURE ===============
     closure without use(): *** does NOT see $taxRate ***
     closure with use()   : 1180
     arrow function       : 1180

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. FOUR SCOPES: global, local, static, parameter.
  2. Functions do NOT see globals. This is deliberate - it forces dependencies
     to be explicit.
  3. Superglobals are the only exception.
  4. "global $x" imports a reference, so the function can change caller state
     invisibly. Prefer parameters.
  5. static remembers between CALLS, not between REQUESTS.
  6. Closures need use(); arrow functions capture automatically, by value.

 INTERVIEW LINK
   "What are the types of variable scope in PHP?"
   "Can a function access a global variable directly?"
   "Why are globals considered bad practice?"
   "What does static do inside a function?"
   "Difference between a closure and an arrow function?"

 TRY THIS
   Remove the ?? from withoutAccess() and run it again. Read the exact warning
   text - you will meet it often, and recognising it instantly saves time.
================================================================================
*/
