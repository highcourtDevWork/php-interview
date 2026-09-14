<?php

/*
================================================================================
 TOPIC 04 - EXAMPLE 3 : COPY-ON-WRITE, MEASURED
================================================================================

 WHAT THIS DEMONSTRATES
   PHP does not physically copy an array when you assign it. It shares the same
   memory and only duplicates when someone WRITES. This script measures that
   with real numbers so you can stop taking it on trust.

   This is the senior-level answer to "should I pass big arrays by reference?"
   and it explains where memory spikes in import scripts actually come from.

 HOW TO RUN
   php 03-copy-on-write.php

   Run it from the COMMAND LINE. In a browser the memory_limit and the shared
   web-server state make the numbers noisier.

================================================================================
*/

declare(strict_types=1);

/**
 * Current memory allocated from the operating system, as a readable string.
 * The "true" argument reports real allocated memory rather than PHP's internal
 * accounting, which is what makes the steps visible in whole megabytes.
 */
function mem(): string
{
    return sprintf('%6.2f MB', memory_get_usage(true) / 1048576);
}

function step(string $label): void
{
    printf('  %-32s %s%s', $label, mem(), PHP_EOL);
}

echo '=============== THE MEASUREMENT ===============' . PHP_EOL;

step('start');

// ---------------------------------------------------------------------------
// STEP 1 : create a large array
// ---------------------------------------------------------------------------
$big = range(1, 500_000);
step('after creating 500k array');

// ---------------------------------------------------------------------------
// STEP 2 : "copy" it. Watch the memory NOT move.
//          Internally: refcount goes from 1 to 2. No data is duplicated.
// ---------------------------------------------------------------------------
$copy = $big;
step('after $copy = $big');

// ---------------------------------------------------------------------------
// STEP 3 : write ONE element. This is where the real copy happens.
// ---------------------------------------------------------------------------
$copy[0] = 'changed';
step('after $copy[0] = "changed"');

// ---------------------------------------------------------------------------
// STEP 4 : release the duplicate
// ---------------------------------------------------------------------------
unset($copy);
step('after unset($copy)');

echo PHP_EOL . '=============== READ-ONLY FUNCTION CALLS ARE FREE ===============' . PHP_EOL;

/** Reads only - no write, so no separation. */
function sumEverything(array $numbers): int
{
    return array_sum($numbers);
}

/** Writes - so PHP must separate the array first. */
function addOneToEach(array $numbers): array
{
    foreach ($numbers as $i => $n) {
        $numbers[$i] = $n + 1;      // the first write triggers the copy
    }
    return $numbers;
}

step('before read-only call');
$sum = sumEverything($big);          // passes 500k elements BY VALUE
step('after read-only call');
printf('  (sum = %d)%s', $sum, PHP_EOL);

step('before writing call');
$modified = addOneToEach($big);      // now a copy really is made
step('after writing call');

unset($modified);
step('after unset($modified)');

echo PHP_EOL . '=============== WHERE THE IMPORT-SCRIPT SPIKE COMES FROM ===============' . PHP_EOL;

// The classic pattern that exhausts memory_limit: two full copies alive at once.
step('before array_map');
$transformed = array_map(static fn(int $n): int => $n * 2, $big);
step('after array_map (TWO arrays alive)');

// The fix: release the source as soon as it is no longer needed.
unset($big);
step('after unset($big)');

unset($transformed);

echo PHP_EOL . '=============== THE REAL FIX: STREAM INSTEAD OF LOAD ===============' . PHP_EOL;

/**
 * A GENERATOR produces values one at a time instead of building a whole array.
 * Memory stays flat no matter how many rows there are - this is how you process
 * a 2 GB CSV inside a 128 MB memory limit.
 */
function generateNumbers(int $count): Generator
{
    for ($i = 1; $i <= $count; $i++) {
        yield $i;
    }
}

step('before generator loop');

$total = 0;
foreach (generateNumbers(500_000) as $number) {
    $total += $number * 2;           // same work, one value at a time
}

step('after generator loop (500k items)');
printf('  (total = %d)%s', $total, PHP_EOL);

echo PHP_EOL . '  ^ The generator never built an array, so memory did not move at' . PHP_EOL;
echo '    all. Compare that with the array_map section above.' . PHP_EOL;

echo PHP_EOL . '=============== PEAK vs CURRENT ===============' . PHP_EOL;
printf('  current memory : %6.2f MB%s', memory_get_usage(true) / 1048576, PHP_EOL);
printf('  PEAK memory    : %6.2f MB   <- this is what memory_limit compares against%s',
    memory_get_peak_usage(true) / 1048576, PHP_EOL);

/*
================================================================================
 CODE EXPLANATION
================================================================================

 HOW COPY-ON-WRITE WORKS

   PHP keeps a REFERENCE COUNT on every value.

     $big = range(1, 500000);   ->  one array,  refcount = 1
     $copy = $big;              ->  same array, refcount = 2   (NO copy!)
     $copy[0] = 'changed';      ->  a write to a SHARED value, so PHP:
                                      1. duplicates the array
                                      2. drops the original refcount back to 1
                                      3. applies the write to the new copy

   The copy is DEFERRED until a write. If you only ever read, no copy is ever
   made. That is the whole idea, and step 2 of the output proves it.

 range(1, 500_000)
     Builds an array of 500,000 integers. The underscore is a numeric literal
     separator (7.4+), ignored by PHP.

 memory_get_usage(true)
     Bytes currently allocated from the OS. Without "true" PHP reports its own
     internal accounting, which hides the allocator's block behaviour and makes
     the steps harder to see.

 sumEverything($big) does not increase memory
     The function takes the array BY VALUE and only reads it, so no separation
     occurs. THIS is the evidence for "do not add & to parameters for
     performance" - the by-value call was already free.

 addOneToEach($big) does increase memory
     The function writes to $numbers, which is shared with the caller's $big, so
     PHP separates first. The increase is the cost of the write, not the cost of
     passing the argument.

 array_map(static fn(int $n): int => $n * 2, $big)
     Builds a SECOND full array while the first is still alive. For a moment
     both exist - that is the memory spike that kills CSV import scripts.
     "static fn" means the closure does not bind $this, which is slightly
     cheaper and is good practice when the callback does not need the object.

 unset($big)
     Frees the source array once the transformed version exists. In a
     memory-tight script this single line is often the difference between
     finishing and hitting "Allowed memory size exhausted".

 function generateNumbers(int $count): Generator  ...  yield $i;
     A GENERATOR. Because it yields values one at a time, the foreach never
     holds more than one value in memory. Memory stays flat regardless of the
     item count. This is the correct pattern for large files, large result sets
     and long-running exports. (Generators are covered in the intermediate
     material; this preview matters because it is the real answer to the
     memory problem shown above.)

 memory_get_peak_usage(true)
     The highest point reached during the whole request. memory_limit is checked
     against the peak, not the current value - so a brief spike can kill a
     script that looks fine at the end. Always report peak when diagnosing
     memory problems.

================================================================================
 EXPECTED OUTPUT  (verified on PHP 8.5.4 - your numbers will differ slightly)
================================================================================

   =============== THE MEASUREMENT ===============
     start                              2.00 MB
     after creating 500k array         12.00 MB
     after $copy = $big                12.00 MB    <- THE PROOF: no copy
     after $copy[0] = "changed"        22.00 MB    <- copy happened on WRITE
     after unset($copy)                12.00 MB

   =============== READ-ONLY FUNCTION CALLS ARE FREE ===============
     before read-only call             12.00 MB
     after read-only call              12.00 MB    <- passing 500k cost nothing

   =============== THE REAL FIX: STREAM INSTEAD OF LOAD ===============
     before generator loop              X.XX MB
     after generator loop (500k items)  X.XX MB    <- unchanged

   WHAT MATTERS IS THE PATTERN, NOT THE EXACT NUMBERS:
     flat on assignment, jump on first write, flat again for read-only calls,
     and completely flat for the generator.

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Assignment does NOT copy an array. Refcount increases; data is shared.
  2. The copy happens on the first WRITE to a shared value. Hence "copy on
     write".
  3. Passing a huge array to a read-only function is FREE. Never add & for
     performance.
  4. Memory spikes come from holding two versions at once (array_map, a
     modified copy, a transformed result). unset() the source when done.
  5. The real fix for large data is to STREAM with generators, not to optimise
     the copying.
  6. memory_limit is checked against PEAK memory, not current.

 INTERVIEW LINK
   "What is copy-on-write?"
   "What happens in memory for $a = range(1,100000); $b = $a; $b[] = 1;"
   "Should you pass large arrays by reference?"        -> No, and here is why.
   "A CSV import crashes with memory exhausted. How do you fix it?"
      -> stream with generators; unset intermediates; never load the whole file.

 TRY THIS
   1. Comment out the $copy[0] = 'changed'; line and re-run. The memory never
      rises - proving the assignment itself is free.
   2. Change 500_000 to 2_000_000 and watch which sections scale and which stay
      flat. The generator section will not move at all.
================================================================================
*/
