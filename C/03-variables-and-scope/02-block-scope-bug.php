<?php

/*
================================================================================
 TOPIC 03 - EXAMPLE 2 : PHP HAS NO BLOCK SCOPE (AND THE BUG IT CAUSES)
================================================================================

 WHAT THIS DEMONSTRATES
   In JavaScript, Java and C#, a variable declared inside { } disappears when
   the block ends. In PHP it does NOT - only functions create scope.

   Then it shows the production bug this causes: code that works perfectly for
   customers WITH data and crashes for customers WITHOUT data. That failure
   pattern - passes testing, fails for a subset of real users - is exactly why
   this topic matters.

 HOW TO RUN
   php 02-block-scope-bug.php

 NOTE ON THE YELLOW SQUIGGLES IN YOUR EDITOR
   Your IDE will flag several lines in this file:
       "Possible undefined variable '$lastLoopValue'"
       "Loop variable $i is also declared in an outer loop"
       "Unreachable code detected"
   Those warnings are CORRECT and they are the point of the file. The IDE is
   statically detecting exactly the bugs demonstrated here - before the code
   ever runs. That is a preview of what PHPStan does for a whole project, and
   it is the real-world answer to "how do I catch this class of bug early".
   Do not fix them; read them.

================================================================================
*/

declare(strict_types=1);

echo '=============== 1. BLOCKS DO NOT CREATE SCOPE ===============' . PHP_EOL;

if (true) {
    $insideIf = 'created inside an if block';
}

for ($i = 0; $i < 3; $i++) {
    $lastLoopValue = $i * 10;
}

foreach (['a', 'b', 'c'] as $letter) {
    // nothing needed here
}

while (false) {
    $neverRuns = 'this line never executes';
}

echo '  $insideIf      : ' . $insideIf . PHP_EOL;
echo '  $i after loop  : ' . $i . '        <- the COUNTER survived' . PHP_EOL;
echo '  $lastLoopValue : ' . $lastLoopValue . '       <- the body variable survived' . PHP_EOL;
echo '  $letter        : ' . $letter . '        <- the foreach value survived' . PHP_EOL;
echo '  $neverRuns     : ' . ($neverRuns ?? '*** never created ***') . PHP_EOL;

echo PHP_EOL . '  In JavaScript with let, every one of those would be undefined.' . PHP_EOL;

echo PHP_EOL . '=============== 2. THE BUG THIS CAUSES ===============' . PHP_EOL;

/**
 * BUGGY VERSION - this is real code people write.
 *
 * It looks defensive because of the ?? inside the loop, but the ?? only
 * protects the INSIDE of the loop body. If the array is empty the body never
 * runs at all, so $total is never created and the function returns null -
 * which violates the declared : float return type.
 */
function calculateTotalBuggy(array $items): float
{
    foreach ($items as $item) {
        $total = ($total ?? 0) + $item['price'];
    }

    return $total;      // <- TypeError when $items is empty
}

/**
 * CORRECT VERSION - initialise BEFORE the loop, with the right type.
 */
function calculateTotal(array $items): float
{
    $total = 0.0;       // <- 0.0 not 0, so the type matches the return type

    foreach ($items as $item) {
        $total += $item['price'];
    }

    return $total;
}

$withItems = [
    ['name' => 'Pen',  'price' => 100.50],
    ['name' => 'Book', 'price' => 249.50],
];
$empty = [];

echo '  Customer WITH orders:' . PHP_EOL;
echo '    buggy   : ' . calculateTotalBuggy($withItems) . PHP_EOL;
echo '    correct : ' . calculateTotal($withItems) . PHP_EOL;

echo PHP_EOL . '  Customer with NO orders (a new signup, a filtered report):' . PHP_EOL;
echo '    correct : ' . calculateTotal($empty) . PHP_EOL;

echo '    buggy   : ';
try {
    echo calculateTotalBuggy($empty) . PHP_EOL;
} catch (TypeError $e) {
    echo '*** TypeError ***' . PHP_EOL;
    echo '      ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . '  ^ THIS is why the bug survives testing: developers test with' . PHP_EOL;
echo '    data. Production has empty carts, new users and filtered reports.' . PHP_EOL;

echo PHP_EOL . '=============== 3. THE SAME BUG WITH AN if BLOCK ===============' . PHP_EOL;

/**
 * BUGGY: $discount only exists when the customer is a member.
 */
function priceBuggy(float $price, bool $isMember): float
{
    if ($isMember) {
        $discount = 10.0;
    }

    return $price - ($price * $discount / 100);   // undefined for non-members
}

/**
 * CORRECT: a safe default before the branch.
 */
function priceCorrect(float $price, bool $isMember): float
{
    $discount = 0.0;                              // always defined

    if ($isMember) {
        $discount = 10.0;
    }

    return round($price - ($price * $discount / 100), 2);
}

echo '  member     -> correct: ' . priceCorrect(1000, true)  . PHP_EOL;
echo '  non-member -> correct: ' . priceCorrect(1000, false) . PHP_EOL;

echo '  non-member -> buggy  : ';
// PHP 8 raises a Warning (not an error) for the undefined variable, then
// treats it as null, so the arithmetic silently produces the WRONG NUMBER.
// A wrong number is worse than a crash - nobody notices it.
$previousLevel = error_reporting(E_ALL & ~E_WARNING);
echo priceBuggy(1000, false) . '   <- no crash, just a silently wrong result' . PHP_EOL;
error_reporting($previousLevel);

echo PHP_EOL . '=============== 4. THE NESTED LOOP COUNTER TRAP ===============' . PHP_EOL;

// Because there is no block scope, an inner loop that reuses $i destroys the
// outer loop's counter. The outer loop then exits early or runs forever.
echo '  BROKEN (both loops use $i):' . PHP_EOL;
$rows = 0;
for ($i = 0; $i < 3; $i++) {
    for ($i = 0; $i < 2; $i++) {          // <- overwrites the OUTER $i
        $rows++;
        if ($rows > 10) {                 // safety brake for the demo
            break 2;
        }
    }
}
echo '    iterations run: ' . $rows . '   (expected 6)' . PHP_EOL;

echo '  CORRECT (distinct names):' . PHP_EOL;
$rows = 0;
for ($row = 0; $row < 3; $row++) {
    for ($col = 0; $col < 2; $col++) {
        $rows++;
    }
}
echo '    iterations run: ' . $rows . '   (expected 6)' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 if (true) { $insideIf = '...'; }  then echo $insideIf;
     Works, because the if block shares the surrounding scope. ONLY functions,
     methods and closures create a new scope in PHP. This single fact explains
     every behaviour in this file.

 echo $i after the for loop prints 3, not 2
     The loop increments $i to 3, the condition 3 < 3 fails, and the loop exits
     leaving $i at 3. The variable is still there afterwards.

 $neverRuns ?? '*** never created ***'
     The while (false) body never executed, so the variable was never created.
     This is the crucial half of the rule: no block scope does NOT mean the
     variable always exists - it means the variable exists only if the line that
     creates it actually RAN.

 ($total ?? 0) + $item['price']
     A very common "defensive" pattern that does not defend against the real
     problem. It handles the first iteration, but when the array is empty there
     are no iterations at all, so $total is never created.

 return $total;  with a : float return type
     Returning null from a function declared : float throws a TypeError. Without
     the return type it would silently return null, and the caller would print
     an empty total on an invoice. The type declaration turned a silent data bug
     into a loud error - which is one of the best arguments for always declaring
     return types.

 catch (TypeError $e)
     TypeError extends Error, which implements Throwable. Since PHP 7 these can
     be caught with try/catch like exceptions (see file 47).

 $total = 0.0;  (not 0)
     0 is an int, 0.0 is a float. With declare(strict_types=1) and a : float
     return type, returning an int from an empty array would still work here
     because int-to-float widening is always allowed - but starting with the
     correct type is clearer and avoids surprises elsewhere.

 error_reporting(E_ALL & ~E_WARNING)
     Temporarily hides warnings so the demo can show the silent wrong result.
     & ~ means "everything EXCEPT warnings" (bitwise AND with the NOT of
     E_WARNING). NEVER do this in real code - it hides exactly the warnings you
     need. It is used here purely to make the point that a warning does not stop
     execution: PHP treats the undefined variable as null, so
     1000 - (1000 * null / 100) = 1000, and the customer is charged full price
     with no visible error.

 break 2;
     Breaks out of TWO nested loops at once. Used here as a safety brake so the
     deliberately broken loop cannot run away.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== 1. BLOCKS DO NOT CREATE SCOPE ===============
     $insideIf      : created inside an if block
     $i after loop  : 3        <- the COUNTER survived
     $lastLoopValue : 20       <- the body variable survived
     $letter        : c        <- the foreach value survived
     $neverRuns     : *** never created ***

   =============== 2. THE BUG THIS CAUSES ===============
     Customer WITH orders:
       buggy   : 350
       correct : 350

     Customer with NO orders (a new signup, a filtered report):
       correct : 0
       buggy   :
     Warning: Undefined variable $total in ...02-block-scope-bug.php on line 67
     *** TypeError ***
         calculateTotalBuggy(): Return value must be of type float, null returned

     Note the ORDER: PHP first emits a WARNING (execution continues, $total is
     treated as null), and only then does the return type declaration reject the
     null. Without the : float return type there would be no error at all - just
     an invoice showing an empty total.

   =============== 3. THE SAME BUG WITH AN if BLOCK ===============
     member     -> correct: 900
     non-member -> correct: 1000
     non-member -> buggy  : 1000   <- no crash, just a silently wrong result

   =============== 4. THE NESTED LOOP COUNTER TRAP ===============
     BROKEN (both loops use $i):
       iterations run: 2   (expected 6)
     CORRECT (distinct names):
       iterations run: 6   (expected 6)

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. PHP HAS NO BLOCK SCOPE. Only functions create scope.
  2. Variables from if/for/foreach/while blocks survive the block...
  3. ...but ONLY if the line creating them actually ran. Empty arrays and
     untaken branches are where this bites.
  4. THE RULE: initialise every variable BEFORE the branch or loop that may
     fill it, with a default of the correct type (0.0, [], null, '').
  5. Declare return types. They convert silent wrong values into loud errors.
  6. Never reuse a counter name in nested loops.

 INTERVIEW LINK
   "Does PHP have block scope?"                    -> No, only functions.
   "Why does $i still exist after the for loop?"
   "A warning says 'Undefined variable $total' but only for some users. Why?"
   -> the loop or branch that creates it did not run for those users.

 TRY THIS
   Delete the "$total = 0.0;" line from calculateTotal() and run again. Watch a
   working function turn into the buggy one. That one line IS the fix.
================================================================================
*/
