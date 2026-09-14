<?php

/*
================================================================================
 TOPIC 03 - EXAMPLE 3 : static AS A PER-REQUEST CACHE (A REAL OPTIMISATION)
================================================================================

 WHAT THIS DEMONSTRATES
   The one genuinely good use of static inside a function: memoisation. A
   permission check called 40 times while rendering a page should not run 40
   database queries.

   It also shows the TWO limits you must state in an interview:
       1. the cache dies at the end of the request
       2. under a persistent runtime (Swoole/RoadRunner) it does NOT die,
          which turns the same code into a data-leak bug

 HOW TO RUN
   php 03-static-cache.php

================================================================================
*/

declare(strict_types=1);

// =============================================================================
// A fake "database" so the example runs without MySQL.
// =============================================================================

$queryCount = 0;

/**
 * Pretends to be an expensive query. Prints every time it is really called,
 * so you can COUNT the database hits in the output.
 */
function fetchPermissionsFromDatabase(int $userId): array
{
    global $queryCount;      // only to count calls in this demo - not a pattern
    $queryCount++;

    echo "      [DB QUERY #{$queryCount}] SELECT permission FROM user_permissions WHERE user_id = {$userId}" . PHP_EOL;
    usleep(30_000);          // simulate 30 ms of database latency

    return match ($userId) {
        1 => ['orders.view', 'orders.edit', 'users.manage'],
        2 => ['orders.view'],
        default => [],
    };
}

// =============================================================================
// VERSION A - no cache. Every call hits the database.
// =============================================================================

function canUncached(int $userId, string $permission): bool
{
    $permissions = fetchPermissionsFromDatabase($userId);

    return in_array($permission, $permissions, true);
}

// =============================================================================
// VERSION B - static cache. One query per user, per request.
// =============================================================================

function canCached(int $userId, string $permission): bool
{
    static $cache = [];      // survives between CALLS in this request

    // ??= evaluates the right side ONLY when the key is missing or null.
    // That short-circuit is what prevents the second query.
    $cache[$userId] ??= fetchPermissionsFromDatabase($userId);

    return in_array($permission, $cache[$userId], true);
}

// =============================================================================
// THE COMPARISON
// =============================================================================

$checks = [
    [1, 'orders.view'],
    [1, 'orders.edit'],
    [1, 'users.manage'],
    [2, 'orders.view'],
    [1, 'orders.view'],      // repeat - user 1 again
    [2, 'orders.edit'],      // repeat - user 2 again
];

echo '=============== VERSION A : NO CACHE ===============' . PHP_EOL;

$queryCount = 0;
$start = hrtime(true);

foreach ($checks as [$userId, $permission]) {
    $allowed = canUncached($userId, $permission);
    printf('  user %d needs %-14s -> %s%s', $userId, $permission, $allowed ? 'allowed' : 'denied', PHP_EOL);
}

$uncachedMs      = (hrtime(true) - $start) / 1_000_000;
$uncachedQueries = $queryCount;

echo PHP_EOL . '=============== VERSION B : STATIC CACHE ===============' . PHP_EOL;

$queryCount = 0;
$start = hrtime(true);

foreach ($checks as [$userId, $permission]) {
    $allowed = canCached($userId, $permission);
    printf('  user %d needs %-14s -> %s%s', $userId, $permission, $allowed ? 'allowed' : 'denied', PHP_EOL);
}

$cachedMs      = (hrtime(true) - $start) / 1_000_000;
$cachedQueries = $queryCount;

echo PHP_EOL . '=============== RESULT ===============' . PHP_EOL;
printf('  without cache : %d queries, %6.2f ms%s', $uncachedQueries, $uncachedMs, PHP_EOL);
printf('  with cache    : %d queries, %6.2f ms%s', $cachedQueries, $cachedMs, PHP_EOL);
printf('  saved         : %d queries (%.0f%% fewer)%s',
    $uncachedQueries - $cachedQueries,
    ($uncachedQueries - $cachedQueries) / $uncachedQueries * 100,
    PHP_EOL
);

echo PHP_EOL . '=============== LIMIT 1 : IT DIES WITH THE REQUEST ===============' . PHP_EOL;
echo '  Run this script again. The query counter starts from #1 every time.' . PHP_EOL;
echo '  static is a PER-REQUEST cache. For cross-request caching you need' . PHP_EOL;
echo '  Redis, Memcached or APCu.' . PHP_EOL;

echo PHP_EOL . '=============== LIMIT 2 : SHARED BY ALL INSTANCES ===============' . PHP_EOL;

class Counter
{
    public function next(): int
    {
        static $n = 0;       // NOT per-object - shared by every instance
        return ++$n;
    }
}

$a = new Counter();
$b = new Counter();

printf('  $a->next() = %d%s', $a->next(), PHP_EOL);
printf('  $b->next() = %d   <- NOT 1: the static is shared by both objects%s', $b->next(), PHP_EOL);
printf('  $a->next() = %d%s', $a->next(), PHP_EOL);

echo PHP_EOL . '=============== LIMIT 3 : THE PERSISTENT-RUNTIME BUG ===============' . PHP_EOL;
echo '  On PHP-FPM this cache is safe: the process dies after each request.' . PHP_EOL;
echo '  On Swoole / RoadRunner / FrankenPHP the worker STAYS ALIVE, so' . PHP_EOL;
echo '  $cache would still hold user 1 permissions when user 2 arrives.' . PHP_EOL;
echo '  That is not a performance bug - it is a SECURITY bug: one user seeing' . PHP_EOL;
echo '  another user permissions. Under those runtimes, caches must be' . PHP_EOL;
echo '  request-scoped (held in a container that is rebuilt per request).' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 static $cache = [];
     One array shared by every call to this function within the request. The
     "= []" initialiser runs only on the first call. Keyed by user id so
     different users cannot collide within a request.

 $cache[$userId] ??= fetchPermissionsFromDatabase($userId);
     The NULL COALESCING ASSIGNMENT operator (PHP 7.4+). It means:
         if $cache[$userId] is not set or is null, evaluate the right side and
         assign it; otherwise do nothing.
     The key detail is SHORT-CIRCUITING: the right side is not evaluated when
     the key already exists, so the database function is never called again.

     This does NOT work the same way:
         $cache[$userId] = $cache[$userId] ?? fetchPermissionsFromDatabase($userId);
     There the function call is an argument being evaluated before ??, so the
     query runs every single time and the cache saves nothing. This is a real
     mistake people make.

 in_array($permission, $permissions, true)
     The third argument true means STRICT comparison - types must match, no
     juggling. Always pass it. Without it, in_array('0', ['a','b']) could behave
     surprisingly in older PHP, and strict mode is simply correct.

 match ($userId) { 1 => ..., default => ... }
     The match expression (PHP 8.0). It uses STRICT comparison, returns a value,
     and requires a default branch (otherwise it throws UnhandledMatchError).
     Covered fully in file 13.

 foreach ($checks as [$userId, $permission])
     ARRAY DESTRUCTURING inside foreach. Each element is a two-element array and
     is unpacked straight into two named variables - much more readable than
     $check[0] and $check[1].

 usleep(30_000)
     Sleeps 30,000 microseconds = 30 ms, simulating database latency. Without a
     realistic delay the timing comparison would be meaningless.

 hrtime(true)
     Nanoseconds from a monotonic clock. The correct function for measuring
     durations.

 global $queryCount;
     Used ONLY to count queries for this demonstration. In real code you would
     return the count or use an object. It is included here to make the saving
     measurable, and it is a good reminder of why globals are convenient and
     still wrong.

 static inside a METHOD (class Counter)
     The static variable belongs to the FUNCTION, not to the object. Both $a and
     $b share it, which is why $b->next() returns 2 rather than 1. If you want
     per-object state, use a property. This is a favourite interview trap.

================================================================================
 EXPECTED OUTPUT  (abbreviated)
================================================================================

   =============== VERSION A : NO CACHE ===============
         [DB QUERY #1] SELECT permission ... user_id = 1
     user 1 needs orders.view    -> allowed
         [DB QUERY #2] SELECT permission ... user_id = 1
     user 1 needs orders.edit    -> allowed
         ... 6 queries in total ...

   =============== VERSION B : STATIC CACHE ===============
         [DB QUERY #1] SELECT permission ... user_id = 1
     user 1 needs orders.view    -> allowed
     user 1 needs orders.edit    -> allowed      <- no query
     user 1 needs users.manage   -> allowed      <- no query
         [DB QUERY #2] SELECT permission ... user_id = 2
     user 2 needs orders.view    -> allowed
     user 1 needs orders.view    -> allowed      <- no query
     user 2 needs orders.edit    -> denied       <- no query

   =============== RESULT ===============
     without cache : 6 queries, 185.xx ms
     with cache    : 2 queries,  62.xx ms
     saved         : 4 queries (67% fewer)

   =============== LIMIT 2 : SHARED BY ALL INSTANCES ===============
     $a->next() = 1
     $b->next() = 2   <- NOT 1: the static is shared by both objects
     $a->next() = 3

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. static + ??= is the idiomatic per-request memoisation cache in PHP.
  2. ??= short-circuits. That is what makes it a cache rather than decoration.
  3. LIMIT 1: it lasts one request. Cross-request caching needs Redis/APCu.
  4. LIMIT 2: in a method it is shared by ALL instances of the class.
  5. LIMIT 3: under Swoole/RoadRunner it survives between requests and leaks
     one user data into another user request. Know this before claiming
     "static caching is safe".
  6. Only cache data that cannot change during the request.

 INTERVIEW LINK
   "What does static do inside a function?"
   "How would you avoid running the same query 40 times in one page?"
   "Does static persist between requests?"                  -> No.
   "Is a static variable per-object or per-class?"           -> Per function/class.
   "What breaks when you move a PHP app to Swoole?"          -> Exactly this.

 TRY THIS
   Replace ??= with the long form
       $cache[$userId] = $cache[$userId] ?? fetchPermissionsFromDatabase($userId);
   and run again. The query count jumps back to 6 - proving that the saving
   comes from short-circuit evaluation, not from the array itself.
================================================================================
*/
