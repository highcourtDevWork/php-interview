<?php

/*
================================================================================
 TOPIC 05 - EXAMPLE 3 : VALIDATING INPUT AT THE BOUNDARY  (project-style code)
================================================================================

 WHAT THIS DEMONSTRATES
   The single most important type rule in web development:

       EVERYTHING that arrives from outside PHP is a STRING.
       $_GET, $_POST, $_COOKIE, CSV files, API bodies, environment variables.

   So you convert and validate ONCE, at the boundary. After that, the rest of
   the application works with real ints, floats and bools.

   It also demonstrates the trap that catches most developers: the valid values
   0, '0' and 'off', which naive truthiness checks silently reject.

 HOW TO RUN
   php 03-validate-input.php

================================================================================
*/

declare(strict_types=1);

/**
 * Validate and CONVERT a product form submission.
 *
 * Returns both the clean typed data and the list of errors, which is the
 * standard shape for a validation function: the caller checks errors first,
 * then uses the data with confidence.
 *
 * @param  array<string, mixed> $input Raw input, as it arrives from $_POST
 * @return array{data: array<string, mixed>, errors: array<int, string>}
 */
function validateProduct(array $input): array
{
    $errors = [];
    $data   = [];

    // ---- id : required, positive integer --------------------------------
    // FILTER_VALIDATE_INT returns the INT, or false when the value is not a
    // valid integer. It rejects "12abc", "1.5" and "" - unlike (int) casting,
    // which would silently turn "12abc" into 12.
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {          // === false, NOT !$id
        $errors[] = 'id must be a positive integer';
    } else {
        $data['id'] = $id;
    }

    // ---- name : required, 3-100 characters -------------------------------
    // Cast to string first: a request like name[]=x would make this an ARRAY,
    // and trim() on an array throws a TypeError.
    $name = is_string($input['name'] ?? null) ? trim($input['name']) : '';

    if ($name === '') {                        // === '' not empty()
        $errors[] = 'name is required';
    } elseif (mb_strlen($name, 'UTF-8') < 3) { // mb_strlen counts CHARACTERS
        $errors[] = 'name must be at least 3 characters';
    } elseif (mb_strlen($name, 'UTF-8') > 100) {
        $errors[] = 'name must not exceed 100 characters';
    } else {
        $data['name'] = $name;
    }

    // ---- price : required, float >= 0, max 2 decimals ---------------------
    $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($price === false) {
        $errors[] = 'price must be a number';
    } elseif ($price < 0) {
        $errors[] = 'price must be 0 or greater';
    } elseif (round($price, 2) !== $price) {
        $errors[] = 'price must not have more than 2 decimal places';
    } else {
        $data['price'] = $price;
    }

    // ---- quantity : required, integer >= 0  (0 IS VALID - the classic trap)
    $quantity = filter_var($input['quantity'] ?? null, FILTER_VALIDATE_INT);

    if ($quantity === false || $quantity < 0) {
        $errors[] = 'quantity must be 0 or a positive integer';
    } else {
        $data['quantity'] = $quantity;         // 0 must survive validation
    }

    // ---- email : required, valid address ----------------------------------
    $email = filter_var($input['email'] ?? null, FILTER_VALIDATE_EMAIL);

    if ($email === false) {
        $errors[] = 'a valid email address is required';
    } else {
        $data['email'] = $email;
    }

    // ---- active : optional bool ('off' and '0' mean FALSE, not invalid) ----
    // FILTER_NULL_ON_FAILURE makes invalid input return NULL, so we can tell
    // "invalid" apart from a legitimate false. Without the flag, both are false.
    $active = filter_var(
        $input['active'] ?? false,
        FILTER_VALIDATE_BOOL,
        FILTER_NULL_ON_FAILURE
    );

    if ($active === null) {
        $errors[] = 'active must be a boolean value';
    } else {
        $data['active'] = $active;
    }

    // ---- tags : optional array of non-empty strings ------------------------
    $tags = $input['tags'] ?? [];

    if (!is_array($tags)) {
        $errors[] = 'tags must be an array';
    } else {
        $clean = [];

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;                       // ignore non-strings silently
            }
            $tag = trim($tag);
            if ($tag !== '') {
                $clean[] = $tag;
            }
        }

        // array_values() re-indexes so the result is a proper list (0,1,2...)
        // rather than an array with gaps, which json_encode would render as an
        // object instead of an array.
        $data['tags'] = array_values(array_unique($clean));
    }

    return ['data' => $data, 'errors' => $errors];
}

// =============================================================================
// TEST CASES - note that EVERY value is a string, as it really would be
// =============================================================================

$cases = [
    'VALID SUBMISSION' => [
        'id'       => '42',
        'name'     => '  Blue Gel Pen  ',
        'price'    => '99.50',
        'quantity' => '25',
        'email'    => 'seller@example.com',
        'active'   => 'on',
        'tags'     => ['stationery', '  pens  ', '', 'stationery'],
    ],

    'EVERYTHING WRONG' => [
        'id'       => 'abc',
        'name'     => '   ',
        'price'    => '-5',
        'quantity' => '-1',
        'email'    => 'not-an-email',
        'active'   => 'maybe',
    ],

    'THE TRAP: 0 AND "off" ARE VALID' => [
        'id'       => '7',
        'name'     => 'Out of stock item',
        'price'    => '0',          // free item - valid!
        'quantity' => '0',          // sold out - valid!
        'email'    => 'seller@example.com',
        'active'   => 'off',        // deactivated - valid FALSE, not invalid
    ],
];

foreach ($cases as $label => $input) {
    echo '=============== ' . $label . ' ===============' . PHP_EOL;

    $result = validateProduct($input);

    if ($result['errors'] !== []) {
        echo '  ERRORS:' . PHP_EOL;
        foreach ($result['errors'] as $error) {
            echo '    - ' . $error . PHP_EOL;
        }
    } else {
        echo '  VALID. Typed data:' . PHP_EOL;
        foreach ($result['data'] as $key => $value) {
            printf('    %-9s %-8s %s%s',
                $key,
                '(' . get_debug_type($value) . ')',
                is_array($value) ? '[' . implode(', ', $value) . ']' : var_export($value, true),
                PHP_EOL
            );
        }
    }

    echo PHP_EOL;
}

// =============================================================================
// WHY  === false  INSTEAD OF  !$value
// =============================================================================

echo '=============== WHY  === false  AND NOT  !$value ===============' . PHP_EOL;

$zeroInput = '0';
$intZero   = filter_var($zeroInput, FILTER_VALIDATE_INT);   // int(0) - VALID

printf('  filter_var("0", FILTER_VALIDATE_INT) returns %s (%s)%s',
    var_export($intZero, true), get_debug_type($intZero), PHP_EOL);

printf('  if (!$result)        -> %s   <- WRONG: rejects a valid 0%s',
    !$intZero ? 'rejected' : 'accepted', PHP_EOL);

printf('  if ($result === false) -> %s   <- CORRECT%s',
    $intZero === false ? 'rejected' : 'accepted', PHP_EOL);

echo PHP_EOL . '  The same trap applies to strpos(), array_search() and any' . PHP_EOL;
echo '  function that can legitimately return 0 or "" but uses false to mean' . PHP_EOL;
echo '  failure. ALWAYS compare with === false.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 filter_var($value, FILTER_VALIDATE_INT)
     Does TWO jobs in one call: it checks that the value really represents an
     integer, and it RETURNS it as an int. It returns false when it does not.
     Compare with (int) casting, which never fails - (int)"12abc" is 12 and
     (int)"abc" is 0, so a cast silently accepts garbage. That is why casting is
     not validation.

 if ($id === false || $id <= 0)      <- THE MOST IMPORTANT LINE IN THIS FILE
     The === is essential. Writing "if (!$id)" would also reject the perfectly
     valid integer 0, because 0 is falsy. Any function that returns false for
     failure but can also return a falsy success value (0, '', '0') must be
     compared with === false. This is one of the most common bugs in PHP code.

 is_string($input['name'] ?? null) ? trim(...) : ''
     Defends against a request like name[]=x, which makes $_POST['name'] an
     ARRAY. Calling trim() on an array throws a TypeError under strict_types.
     Never assume a request field is a string - an attacker controls its shape.

 $name === ''  instead of  empty($name)
     empty('0') is TRUE, so empty() would reject a product legitimately named
     "0". Comparing with === '' checks exactly what we mean: an empty string.

 mb_strlen($name, 'UTF-8')
     Counts CHARACTERS, not bytes. With plain strlen(), a name in Hindi or with
     accented characters would be measured 2-3x too long and rejected by a
     100-character limit. Any length rule applied to human text must use mb_*.

 round($price, 2) !== $price
     Rejects values like 99.999 that carry more than 2 decimal places. Note this
     works because we compare floats for exact equality only AFTER rounding -
     see file 06 for why float equality is otherwise unsafe, and why real money
     should be stored as integer paise.

 filter_var(..., FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
     FILTER_VALIDATE_BOOL recognises "1", "true", "on", "yes" as TRUE and "0",
     "false", "off", "no", "" as FALSE. Without FILTER_NULL_ON_FAILURE, invalid
     input such as "maybe" also returns false - indistinguishable from a genuine
     false. The flag makes invalid input return null instead, so the three
     outcomes (true / false / invalid) stay distinct. This is exactly what an
     unchecked HTML checkbox needs.

 array_values(array_unique($clean))
     array_unique() removes duplicates but PRESERVES the original keys, leaving
     gaps like [0 => 'a', 2 => 'c']. json_encode() renders an array with gaps as
     a JSON OBJECT {"0":"a","2":"c"} instead of an ARRAY - which breaks the
     client. array_values() re-indexes to a clean list. This pairing is a
     standard idiom; see file 17.

 THE RETURN SHAPE ['data' => ..., 'errors' => ...]
     One value describing the whole outcome, rather than out-parameters. The
     caller checks errors, then trusts the data. In a larger project this would
     be a small readonly Result class instead of an array.

 WHY THE THIRD TEST CASE MATTERS
     price 0, quantity 0 and active "off" are all LEGITIMATE values that a
     naive check would reject:
         if (!$price)      rejects a free product
         if (empty($qty))  rejects a sold-out product
         if (!$active)     cannot distinguish "off" from invalid input
     Handling this case correctly is what separates working validation from
     validation that mysteriously loses data.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== VALID SUBMISSION ===============
     VALID. Typed data:
       id        (int)    42
       name      (string) 'Blue Gel Pen'
       price     (float)  99.5
       quantity  (int)    25
       email     (string) 'seller@example.com'
       active    (bool)   true
       tags      (array)  [stationery, pens]

   =============== EVERYTHING WRONG ===============
     ERRORS:
       - id must be a positive integer
       - name is required
       - price must be 0 or greater
       - quantity must be 0 or a positive integer
       - a valid email address is required
       - active must be a boolean value

   =============== THE TRAP: 0 AND "off" ARE VALID ===============
     VALID. Typed data:
       id        (int)    7
       name      (string) 'Out of stock item'
       price     (float)  0.0
       quantity  (int)    0
       email     (string) 'seller@example.com'
       active    (bool)   false

   =============== WHY  === false  AND NOT  !$value ===============
     filter_var("0", FILTER_VALIDATE_INT) returns 0 (int)
     if (!$result)        -> rejected   <- WRONG: rejects a valid 0
     if ($result === false) -> accepted   <- CORRECT

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. ALL external input is a string. Convert and validate ONCE, at the boundary.
  2. filter_var validates AND converts. Casting does not validate.
  3. Always compare filter_var results with === false. Never with !$value.
  4. Use === '' instead of empty() for strings, because empty('0') is true.
  5. Use mb_strlen for any length rule on human text.
  6. FILTER_NULL_ON_FAILURE keeps "false" and "invalid" distinct for booleans.
  7. Never assume a request field is a string - it can be an array.
  8. array_values(array_unique(...)) keeps a list a list for JSON.
  9. Return one structured result: ['data' => ..., 'errors' => ...].

 INTERVIEW LINK
   "Everything from $_POST is a string. Why does that matter?"
   "How do you validate user input in PHP?"
   "Why is if (!filter_var($x, FILTER_VALIDATE_INT)) wrong?"
   "What is the difference between empty() and === ''?"
   "How would you validate a checkbox that may be absent?"

 TRY THIS
   1. Change the id check to  if (!$id)  and re-run. Test case 3 still passes
      (id is 7), so add a case with id '0' to see the bug appear.
   2. Replace mb_strlen with strlen and validate a name in Hindi or with
      accents. Watch a valid 5-character name get measured as 15.
================================================================================
*/
