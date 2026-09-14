<?php

/*
================================================================================
 TOPIC 04 - EXAMPLE 2 : BY-VALUE vs BY-REFERENCE FUNCTION PARAMETERS
================================================================================

 WHAT THIS DEMONSTRATES
   What &$param really does, why the call site gives no warning that your
   variable is about to change, which built-in PHP functions use references,
   and why "return a new value" is almost always the better design.

 HOW TO RUN
   php 02-param-reference.php

================================================================================
*/

declare(strict_types=1);

echo '=============== 1. BY VALUE (the default, and the right default) ===============' . PHP_EOL;

/**
 * Takes input, returns output. The caller keeps full control.
 *
 * @param  array<int, array{name: string, price: float}> $items
 * @return array<int, array{name: string, price: float}>
 */
function withDiscount(array $items, float $percent): array
{
    foreach ($items as $index => $item) {
        $items[$index]['price'] = round($item['price'] * (1 - $percent / 100), 2);
    }

    return $items;      // a NEW array - the caller's array is untouched
}

$original = [
    ['name' => 'Pen',  'price' => 100.00],
    ['name' => 'Book', 'price' => 250.00],
];

$discounted = withDiscount($original, 10);

printf('  original[0]   : %.2f   <- unchanged%s', $original[0]['price'], PHP_EOL);
printf('  discounted[0] : %.2f   <- the returned copy%s', $discounted[0]['price'], PHP_EOL);

echo PHP_EOL . '=============== 2. BY REFERENCE (&) ===============' . PHP_EOL;

/**
 * Modifies the caller's array in place. Returns nothing, because the mutation
 * IS the result.
 *
 * @param array<int, array{name: string, price: float}> $items
 */
function applyDiscountInPlace(array &$items, float $percent): void
{
    foreach ($items as $index => $item) {
        $items[$index]['price'] = round($item['price'] * (1 - $percent / 100), 2);
    }
}

applyDiscountInPlace($original, 10);      // <- note: NO & at the call site

printf('  original[0]   : %.2f   <- silently modified%s', $original[0]['price'], PHP_EOL);

echo PHP_EOL . '  ^ THE PROBLEM: applyDiscountInPlace($original, 10) looks exactly' . PHP_EOL;
echo '    like a read-only call. Nothing at the call site warns you that your' . PHP_EOL;
echo '    variable is about to change. You must open the function to find out.' . PHP_EOL;

echo PHP_EOL . '=============== 3. SCALARS BY REFERENCE ===============' . PHP_EOL;

function addTaxByValue(float $price): float
{
    $price = round($price * 1.18, 2);
    return $price;
}

function addTaxByReference(float &$price): void
{
    $price = round($price * 1.18, 2);
}

$amount = 100.00;
$returned = addTaxByValue($amount);
printf('  after addTaxByValue     : $amount = %.2f, returned = %.2f%s', $amount, $returned, PHP_EOL);

addTaxByReference($amount);
printf('  after addTaxByReference : $amount = %.2f%s', $amount, PHP_EOL);

echo PHP_EOL . '=============== 4. BUILT-IN FUNCTIONS THAT USE REFERENCES ===============' . PHP_EOL;

// These are the legitimate cases: modifying in place IS the purpose.
$numbers = [5, 3, 9, 1];
sort($numbers);                      // takes &$array - modifies in place
printf('  sort($numbers)          -> [%s]%s', implode(', ', $numbers), PHP_EOL);

$stack = ['a', 'b'];
array_push($stack, 'c');             // takes &$array
$popped = array_pop($stack);         // takes &$array
printf('  array_push / array_pop  -> [%s], popped "%s"%s', implode(', ', $stack), $popped, PHP_EOL);

// preg_match fills $matches BY REFERENCE - this is why you pass an
// undeclared variable and it magically contains the results afterwards.
preg_match('/(\d{4})-(\d{2})-(\d{2})/', 'Order placed on 2026-09-05', $matches);
printf('  preg_match(..., $matches) -> year=%s month=%s day=%s%s',
    $matches[1], $matches[2], $matches[3], PHP_EOL);

// headers_sent() fills two by-reference arguments.
$file = ''; $line = 0;
headers_sent($file, $line);
printf('  headers_sent($file, $line) -> output began at line %d%s', $line, PHP_EOL);

echo PHP_EOL . '=============== 5. THE BETTER ALTERNATIVE TO MULTIPLE OUT-PARAMS ===============' . PHP_EOL;

/**
 * BAD: using references to return several values.
 */
function registerBad(array $data, ?array &$user, ?array &$errors): bool
{
    $errors = [];
    if (($data['email'] ?? '') === '') {
        $errors[] = 'email is required';
    }
    if ($errors !== []) {
        $user = null;
        return false;
    }
    $user = ['id' => 1, 'email' => $data['email']];
    return true;
}

/**
 * GOOD: return a single structured result. One value, fully typed, and the
 * signature tells you everything.
 *
 * @return array{ok: bool, user: ?array<string, mixed>, errors: array<int, string>}
 */
function registerGood(array $data): array
{
    $errors = [];
    if (($data['email'] ?? '') === '') {
        $errors[] = 'email is required';
    }

    if ($errors !== []) {
        return ['ok' => false, 'user' => null, 'errors' => $errors];
    }

    return ['ok' => true, 'user' => ['id' => 1, 'email' => $data['email']], 'errors' => []];
}

$user = null; $errors = null;
$okBad = registerBad(['email' => ''], $user, $errors);
printf('  registerBad  -> ok=%s errors=%s%s', var_export($okBad, true), implode('; ', $errors), PHP_EOL);

$result = registerGood(['email' => '']);
printf('  registerGood -> ok=%s errors=%s%s',
    var_export($result['ok'], true), implode('; ', $result['errors']), PHP_EOL);

echo PHP_EOL . '  The second version needs no pre-declared variables, is easy to' . PHP_EOL;
echo '  type-hint, and can be used directly:  if (!registerGood($d)["ok"]) ...' . PHP_EOL;

echo PHP_EOL . '=============== 6. THE PERFORMANCE MYTH ===============' . PHP_EOL;

$big = range(1, 300_000);

function readOnlyByValue(array $numbers): int
{
    return count($numbers);       // only READS
}

function readOnlyByReference(array &$numbers): int
{
    return count($numbers);
}

$before = memory_get_usage(true);
readOnlyByValue($big);
$afterValue = memory_get_usage(true);
readOnlyByReference($big);
$afterRef = memory_get_usage(true);

printf('  memory before          : %.2f MB%s', $before / 1048576, PHP_EOL);
printf('  after by-VALUE call    : %.2f MB   <- no increase%s', $afterValue / 1048576, PHP_EOL);
printf('  after by-REFERENCE call: %.2f MB   <- no difference%s', $afterRef / 1048576, PHP_EOL);

echo PHP_EOL . '  ^ Passing a 300,000-element array BY VALUE cost nothing, because' . PHP_EOL;
echo '    copy-on-write means no copy happens until something writes.' . PHP_EOL;
echo '    So "&$array for performance" is a myth. See example 03.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 function withDiscount(array $items, float $percent): array
     By value. Because of copy-on-write no copy exists until the first write
     inside the function, so this is cheap AND safe. The caller's array is never
     touched, which makes the function easy to reason about and to test.

 function applyDiscountInPlace(array &$items, float $percent): void
     The & in the PARAMETER binds the caller's variable directly.
     : void is the honest return type - there is nothing to return because the
     effect IS the mutation.

     THE KEY OBSERVATION: the CALL looks identical to a normal call.
         applyDiscountInPlace($original, 10);
     Nothing there hints that $original changes. In a 2,000-line legacy file
     this is how "who modified my array?" bugs are born.

 sort($numbers)
     A built-in that legitimately takes &$array. sort() returns bool (success),
     not the sorted array - so writing $sorted = sort($x) is a classic bug that
     assigns true. This is why knowing which functions use references matters.

 preg_match('/pattern/', $subject, $matches)
     $matches is a by-reference OUT parameter. You pass a variable that does not
     exist yet and PHP fills it. This is the standard PHP idiom for "return a
     second value", and it is exactly the pattern modern code avoids in its own
     functions.

 registerBad(array $data, ?array &$user, ?array &$errors): bool
     Two out-parameters plus a bool. The caller must declare $user and $errors
     before calling, cannot chain the call, and static analysis struggles to
     follow it. It also reads badly: three things come back through three
     different mechanisms.

 registerGood(array $data): array  with @return array{ok: ..., user: ..., errors: ...}
     One return value describing the whole outcome. The array shape annotation
     gives the IDE and PHPStan the exact structure. In a larger project this
     would be a small readonly RESULT CLASS instead of an array - same idea,
     even better typing.

 THE MEMORY TEST
     memory_get_usage(true) reports memory allocated from the OS. Passing a
     300,000-element array by value produces no increase, because copy-on-write
     defers the copy until a write - and a read-only function never writes.
     Therefore adding & buys nothing. Worse, a reference disables some internal
     optimisations and invites accidental mutation.

     RULE: use & only when in-place modification is the documented PURPOSE of
     the function - never as a performance tweak.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== 1. BY VALUE (the default, and the right default) ===============
     original[0]   : 100.00   <- unchanged
     discounted[0] : 90.00   <- the returned copy

   =============== 2. BY REFERENCE (&) ===============
     original[0]   : 90.00   <- silently modified

   =============== 3. SCALARS BY REFERENCE ===============
     after addTaxByValue     : $amount = 100.00, returned = 118.00
     after addTaxByReference : $amount = 118.00

   =============== 4. BUILT-IN FUNCTIONS THAT USE REFERENCES ===============
     sort($numbers)          -> [1, 3, 5, 9]
     array_push / array_pop  -> [a, b], popped "c"
     preg_match(..., $matches) -> year=2026 month=09 day=05

   =============== 6. THE PERFORMANCE MYTH ===============
     after by-VALUE call    : X.XX MB   <- no increase
     after by-REFERENCE call: X.XX MB   <- no difference

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Parameters are BY VALUE by default. That is the correct default.
  2. &$param binds the caller's variable. The call site gives NO hint, which is
     the main argument against it.
  3. Legitimate uses: sort(), array_push(), preg_match($p,$s,$matches) - where
     in-place modification is the whole point.
  4. Do NOT use & to return multiple values. Return one structured result.
  5. Do NOT use & for performance. Copy-on-write already makes read-only
     passing free - the memory test in section 6 proves it.
  6. If a function takes &$param, say so loudly in its name and DocBlock.

 INTERVIEW LINK
   "What is the difference between passing by value and by reference?"
   "Should you pass large arrays by reference for performance?"   -> No.
   "Name PHP functions that take arguments by reference."
   "How would you return two values from a function?"   -> one array/DTO.

 TRY THIS
   Write $sorted = sort($numbers); and var_dump($sorted). You get bool(true),
   not the array - because sort() modifies in place and returns success. This
   single mistake appears in production code constantly.
================================================================================
*/
