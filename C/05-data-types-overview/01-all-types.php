<?php

/*
================================================================================
 TOPIC 05 - EXAMPLE 1 : ALL 8 PHP TYPES AND HOW TO CHECK THEM
================================================================================

 WHAT THIS DEMONSTRATES
   Every PHP data type in one table, with what each checking function reports
   for it - so you can see exactly where gettype() and get_debug_type()
   disagree, and which is_*() functions match which types.

 THE ANSWER TO "HOW MANY TYPES DOES PHP HAVE?"
   Eight:
     SCALAR   bool, int, float, string
     COMPOUND array, object
     SPECIAL  null, resource

 HOW TO RUN
   php 01-all-types.php

================================================================================
*/

declare(strict_types=1);

final class Product
{
    public function __construct(public readonly string $name)
    {
    }
}

$stream = fopen('php://memory', 'r');      // a real resource

$values = [
    'bool'      => true,
    'int'       => 42,
    'float'     => 99.99,
    'string'    => 'hello',
    'array'     => ['a', 'b'],
    'object'    => new Product('Pen'),
    'stdClass'  => (object) ['x' => 1],
    'null'      => null,
    'resource'  => $stream,
    'closure'   => fn(int $n): int => $n * 2,
    'numstring' => '42',
];

echo '=============== 1. gettype() vs get_debug_type() ===============' . PHP_EOL . PHP_EOL;

printf("  %-10s | %-10s | %-18s | %s%s", 'LABEL', 'gettype', 'get_debug_type', 'is_* matches', PHP_EOL);
echo '  ' . str_repeat('-', 86) . PHP_EOL;

$checks = ['is_bool', 'is_int', 'is_float', 'is_string', 'is_array', 'is_object',
           'is_null', 'is_numeric', 'is_callable', 'is_scalar', 'is_iterable'];

foreach ($values as $label => $value) {
    $matched = [];

    foreach ($checks as $fn) {
        if ($fn($value)) {
            $matched[] = substr($fn, 3);      // strip the "is_" prefix
        }
    }

    printf("  %-10s | %-10s | %-18s | %s%s",
        $label,
        gettype($value),
        get_debug_type($value),
        implode(', ', $matched) ?: '-',
        PHP_EOL
    );
}

fclose($stream);

echo PHP_EOL . '  WHERE THEY DISAGREE (this is the interview point):' . PHP_EOL;
echo '    gettype        get_debug_type' . PHP_EOL;
echo '    "integer"  ->  "int"' . PHP_EOL;
echo '    "double"   ->  "float"     <- "double"! never matches a type keyword' . PHP_EOL;
echo '    "boolean"  ->  "bool"' . PHP_EOL;
echo '    "NULL"     ->  "null"      <- uppercase in gettype' . PHP_EOL;
echo '    "object"   ->  "Product"   <- the actual CLASS NAME' . PHP_EOL;
echo '  Use get_debug_type(): its names match the keywords you write in type' . PHP_EOL;
echo '  declarations, so error messages are directly actionable.' . PHP_EOL;

echo PHP_EOL . '=============== 2. THE CLASSIC gettype() TRAP ===============' . PHP_EOL;

$price = 99.99;

// This is ALWAYS false. gettype() returns "double", never "float".
var_dump(gettype($price) === 'float');

// These are the correct checks:
var_dump(is_float($price));
var_dump(get_debug_type($price) === 'float');

echo PHP_EOL . '=============== 3. SCALAR TYPE DETAILS ===============' . PHP_EOL;

printf('  PHP_INT_MAX       : %d%s', PHP_INT_MAX, PHP_EOL);
printf('  PHP_INT_SIZE      : %d bytes (64-bit)%s', PHP_INT_SIZE, PHP_EOL);

// INTEGER OVERFLOW: PHP has no overflow error - the value silently becomes a
// float, and floats lose precision beyond ~15 significant digits.
$overflow = PHP_INT_MAX + 1;
printf('  PHP_INT_MAX + 1   : %s  (type: %s)  <- silently became a float%s',
    var_export($overflow, true), get_debug_type($overflow), PHP_EOL);

// Integer literal forms
printf('  0xFF / 0b11111111 / 0o377 / 1_000 : %d / %d / %d / %d%s',
    0xFF, 0b11111111, 0o377, 1_000, PHP_EOL);

// STRINGS ARE BYTES, NOT CHARACTERS
$multibyte = 'héllo';
printf('  strlen("héllo")    : %d  <- BYTES%s', strlen($multibyte), PHP_EOL);
printf('  mb_strlen("héllo") : %d  <- CHARACTERS%s', mb_strlen($multibyte, 'UTF-8'), PHP_EOL);
printf('  substr("héllo",0,2): "%s"  <- cut a character in half%s', substr($multibyte, 0, 2), PHP_EOL);
printf('  mb_substr(...,0,2) : "%s"  <- correct%s', mb_substr($multibyte, 0, 2, 'UTF-8'), PHP_EOL);

echo PHP_EOL . '=============== 4. null IS NOT 0, "" OR false ===============' . PHP_EOL;

$candidates = ['null' => null, 'zero' => 0, 'empty string' => '', 'false' => false, 'empty array' => []];

printf("  %-14s | %-8s | %-8s | %s%s", 'VALUE', 'falsy?', 'isset?', 'is_null?', PHP_EOL);
echo '  ' . str_repeat('-', 52) . PHP_EOL;

foreach ($candidates as $label => $value) {
    printf("  %-14s | %-8s | %-8s | %s%s",
        $label,
        $value ? 'no' : 'YES',
        isset($value) ? 'yes' : 'NO',
        is_null($value) ? 'yes' : 'no',
        PHP_EOL
    );
}

echo PHP_EOL . '  All five are FALSY, so if() treats them identically.' . PHP_EOL;
echo '  Only === can tell them apart:' . PHP_EOL;
var_dump(null === 0);
var_dump(null === false);
var_dump('' === null);

echo PHP_EOL . '=============== 5. CLOSURES ARE OBJECTS ===============' . PHP_EOL;

$fn = fn(int $n): int => $n * 2;

printf('  gettype($fn)        : %s%s', gettype($fn), PHP_EOL);
printf('  get_debug_type($fn) : %s%s', get_debug_type($fn), PHP_EOL);
printf('  $fn instanceof Closure : %s%s', var_export($fn instanceof Closure, true), PHP_EOL);
echo '  ^ PHP has no separate "function" type. A closure is an OBJECT of' . PHP_EOL;
echo '    class Closure that happens to be callable.' . PHP_EOL;

echo PHP_EOL . '=============== 6. resource IS BEING REPLACED BY OBJECTS ===============' . PHP_EOL;

$fp = fopen('php://memory', 'r');
printf('  fopen()      -> %s   (still a resource)%s', get_debug_type($fp), PHP_EOL);
fclose($fp);

if (function_exists('curl_init')) {
    $ch = curl_init();
    printf('  curl_init()  -> %s   (an OBJECT since PHP 8.0)%s', get_debug_type($ch), PHP_EOL);
    curl_close($ch);
}

echo '  Also converted to objects in 8.0: GdImage, OpenSSL handles.' . PHP_EOL;
echo '  Correct answer today: "resource is a legacy type being replaced by' . PHP_EOL;
echo '  objects, because objects can be typed and can have methods."' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 fopen('php://memory', 'r')
     php://memory is a stream that lives in RAM, used here to create a genuine
     resource without touching the disk. Always fclose() what you fopen().

 (object) ['x' => 1]
     Casting an array to an object produces a stdClass - PHP's generic empty
     class. json_decode() without the associative flag returns these too.

 $fn = fn(int $n): int => $n * 2;
     An arrow function (7.4+). Note what the table shows: gettype() says
     "object", and get_debug_type() says "Closure". There is no "callable" or
     "function" TYPE in PHP - callable is a pseudo-type used in declarations.

 substr($fn, 3) inside the loop
     $fn holds a function NAME as a string, and $fn($value) calls it - the
     "callable string" form. substr(..., 3) removes the "is_" prefix so the
     column stays readable.

 gettype($price) === 'float' is ALWAYS false
     gettype() returns "double" for floats, a historical name from C. This is
     one of the most common silent bugs in old code, because the comparison
     never matches and the branch never runs. Use is_float() or
     get_debug_type().

 PHP_INT_MAX + 1
     No overflow error, no exception - the value silently becomes a float.
     Because floats carry only ~15-16 significant digits, very large integers
     stop being exactly representable, so two different large ids can compare
     equal. For values beyond PHP_INT_MAX use strings with bcmath or gmp.

 strlen('héllo') is 6, mb_strlen('héllo') is 5
     A PHP string is a sequence of BYTES. In UTF-8, é occupies 2 bytes. So
     strlen counts bytes, and substr can slice a multibyte character in half,
     producing a broken character (shown in the output as a replacement
     glyph). Any function that handles user-facing text must be the mb_*
     version: mb_strlen, mb_substr, mb_strtoupper, mb_str_split.

 THE null TABLE
     null, 0, '', false and [] are ALL falsy, so if() cannot distinguish them.
     isset() is false only for null. is_null() is true only for null. This is
     why === matters: '' === null is false, even though both are falsy. Getting
     this wrong is the root of "why did my empty check reject a valid 0?".

 curl_init() returning CurlHandle
     Evidence for the "resource is legacy" answer. In PHP 8.0 curl, GD and
     OpenSSL handles all became real objects, which can be type-declared
     (function f(CurlHandle $ch)) - impossible with a resource.

================================================================================
 EXPECTED OUTPUT  (the main table)
================================================================================

   LABEL      | gettype    | get_debug_type     | is_* matches
   --------------------------------------------------------------------------
   bool       | boolean    | bool               | bool, scalar
   int        | integer    | int                | int, numeric, scalar
   float      | double     | float              | float, numeric, scalar
   string     | string     | string             | string, scalar
   array      | array      | array              | array, iterable
   object     | object     | Product            | object
   stdClass   | object     | stdClass           | object
   null       | NULL       | null               | null
   resource   | resource   | resource (stream)  | -
   closure    | object     | Closure            | object, callable
   numstring  | string     | string             | string, numeric, scalar

   Note the last row: '42' is a STRING but is_numeric() is true for it. That
   is exactly what is_numeric() is for - checking raw input before conversion.

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. 8 TYPES: bool, int, float, string | array, object | null, resource.
  2. get_debug_type() (8.0+) beats gettype(): its names match type declarations
     and it returns the CLASS NAME for objects. Use it in error messages.
  3. gettype() returns "double" for floats - so gettype($x) === 'float' never
     matches.
  4. Integers silently overflow into floats. No unsigned ints in PHP.
  5. Strings are BYTES. Use mb_* functions for any human-readable text.
  6. null, 0, '', false, [] are all falsy but all different. Use ===.
  7. Closures are objects of class Closure. There is no "function" type.
  8. resource is legacy; new APIs return objects.

 INTERVIEW LINK
   "How many data types does PHP have? Name them."
   "Difference between gettype() and get_debug_type()?"
   "Why is strlen('é') equal to 2?"
   "What happens when an int exceeds PHP_INT_MAX?"
   "Difference between null, 0 and empty string?"
   "Is a closure a function or an object?"

 TRY THIS
   Add   var_dump(is_numeric(' 42'), is_numeric('42 '), is_numeric('1e3'));
   Leading whitespace is allowed, trailing whitespace has been allowed since
   PHP 8.0, and '1e3' counts as numeric. Knowing the edges of is_numeric()
   matters when validating input.
================================================================================
*/
