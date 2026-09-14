<?php

/*
================================================================================
 TOPIC 02 - EXAMPLE 2 : echo / print / print_r / var_dump / var_export
================================================================================

 WHAT THIS DEMONSTRATES
   The five ways to output a value, what each one is for, and - most
   importantly - why print_r actively HIDES the bug you are usually hunting,
   while var_dump shows it.

 HOW TO RUN
   php 02-output-functions.php

================================================================================
*/

declare(strict_types=1);

echo '=============== 1. echo vs print ===============' . PHP_EOL;

// echo takes MULTIPLE comma-separated arguments and returns nothing.
echo 'echo can take ', 'several ', 'arguments', PHP_EOL;

// print takes ONE argument and RETURNS 1, so it can sit inside an expression.
$returned = print 'print returns a value' . PHP_EOL;
echo 'print returned : ' . $returned . PHP_EOL;

// Because print returns 1 (truthy), this idiom works. echo could not do it:
$loggedIn = false;
$loggedIn or print 'user is not logged in' . PHP_EOL;

// Both are LANGUAGE CONSTRUCTS, not functions:
echo 'is_callable("echo")  : ' . var_export(is_callable('echo'), true) . PHP_EOL;
echo 'is_callable("printf"): ' . var_export(is_callable('printf'), true) . PHP_EOL;

echo PHP_EOL . '=============== 2. var_dump SHOWS TYPES ===============' . PHP_EOL;

$values = [0, '0', 0.0, false, null, '', [], 'false', '0.0'];

foreach ($values as $value) {
    // str_pad keeps the columns aligned; var_export gives the literal PHP form.
    echo str_pad(var_export($value, true), 12) . ' -> ';
    var_dump($value);
}

echo PHP_EOL . '=============== 3. print_r HIDES THE DIFFERENCE ===============' . PHP_EOL;

foreach ([0, '0', false, null, ''] as $value) {
    // Five DIFFERENT values. print_r renders them almost identically.
    echo 'print_r output: [' . print_r($value, true) . ']' . PHP_EOL;
}

echo PHP_EOL . '=============== 4. THE BUG THIS CAUSES ===============' . PHP_EOL;

/**
 * A realistic mistake: a form field arrives as the STRING "0", but the code
 * compares it against the INTEGER 0.
 */
$posted = ['quantity' => '0'];          // everything from $_POST is a string

$quantity = $posted['quantity'];

echo 'Debugging with print_r : quantity = ' . print_r($quantity, true) . PHP_EOL;
echo '  -> looks like the integer 0, so why does the check fail?' . PHP_EOL;

echo 'Debugging with var_dump: ';
var_dump($quantity);
echo '  -> string(1) "0" - THERE is the answer.' . PHP_EOL . PHP_EOL;

var_dump($quantity === 0);              // false - different types
var_dump($quantity === '0');            // true
var_dump((int) $quantity === 0);        // true - after converting

echo PHP_EOL . '=============== 5. var_export RETURNS VALID PHP ===============' . PHP_EOL;

$config = [
    'debug'    => true,
    'timeout'  => 30,
    'hosts'    => ['db1', 'db2'],
    'fallback' => null,
];

// The second argument true means "return it" instead of printing it.
$phpCode = var_export($config, true);
echo $phpCode . PHP_EOL;

echo PHP_EOL . '  -> this output is valid PHP source, so it can be written to a' . PHP_EOL;
echo '     cache file and later loaded back with require.' . PHP_EOL;

echo PHP_EOL . '=============== 6. ARRAYS AND OBJECTS ===============' . PHP_EOL;

final class Product
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        private readonly string $internalSku = 'SKU-001'
    ) {
    }
}

$product = new Product('Blue Pen', 99.50);

echo '--- print_r (readable, no types) ---' . PHP_EOL;
print_r($product);

echo PHP_EOL . '--- var_dump (types, sizes, visibility) ---' . PHP_EOL;
var_dump($product);

/*
================================================================================
 CODE EXPLANATION
================================================================================

 echo 'a', 'b', 'c';
     echo accepts multiple arguments separated by commas. Using commas is
     marginally faster than the . concatenation operator because PHP does not
     have to build one combined string first. It returns nothing at all.

 $returned = print '...';
     print always returns the integer 1. That is the ONLY functional difference
     from echo, and it is why print can appear inside an expression while echo
     cannot. In practice you will use echo almost always.

 $loggedIn or print '...';
     "or" has very low precedence, so this reads as: evaluate $loggedIn; if it
     is falsy, evaluate the right side. It is an old idiom - readable code would
     use a normal if. It is included here because it only works with print, not
     echo, which proves the "returns 1" point.

 is_callable('echo') is false
     echo and print are LANGUAGE CONSTRUCTS built into the parser, not
     functions. So they cannot be used as callables: array_map('echo', $x)
     fails. printf IS a real function, which is why is_callable('printf') is
     true. This is a classic interview trivia question.

 var_dump($value)
     Prints TYPE, SIZE and VALUE:
        int(0)          the integer zero
        string(1) "0"   a one-character string
        float(0)        the float zero
        bool(false)
        NULL
        string(0) ""    an empty string
     Those are five different values. All of them are FALSY, so an if() treats
     them identically - which is exactly why you need var_dump to tell them
     apart when a condition misbehaves.

 print_r($value, true)
     Renders a readable representation and, with the second argument true,
     RETURNS it instead of printing. But it shows no types: int 0 and string "0"
     both print as 0, while false, null and '' all print as nothing at all. When
     you are debugging "why is this comparison false", print_r hides the answer.

 var_export($config, true)
     Produces valid PHP SOURCE CODE for the value - with quotes around strings,
     true/false/NULL as keywords, and array(...) syntax. This is what config
     caching does: compute an array once, var_export it to a file, then require
     that file on later requests to skip the computation.

 print_r vs var_dump on an OBJECT
     print_r shows property names and values. var_dump additionally shows the
     class name, the number of properties, each property's TYPE, and its
     VISIBILITY - private properties are marked with ":private" and protected
     with ":protected". That visibility information is often exactly what you
     need when debugging inheritance.

================================================================================
 EXPECTED OUTPUT  (key sections)
================================================================================

   =============== 2. var_dump SHOWS TYPES ===============
   0            -> int(0)
   '0'          -> string(1) "0"
   0.0          -> float(0)
   false        -> bool(false)
   NULL         -> NULL
   ''           -> string(0) ""
   array (      -> array(0) {
   )            }
   'false'      -> string(5) "false"
   '0.0'        -> string(3) "0.0"

   =============== 3. print_r HIDES THE DIFFERENCE ===============
   print_r output: [0]
   print_r output: [0]
   print_r output: []
   print_r output: []
   print_r output: []

   (five different values -> two indistinguishable outputs)

   =============== 4. THE BUG THIS CAUSES ===============
   Debugging with var_dump: string(1) "0"
   bool(false)      <- $quantity === 0
   bool(true)       <- $quantity === '0'
   bool(true)       <- (int) $quantity === 0

================================================================================
 KEY TAKEAWAYS
================================================================================

  echo        Normal output. Multiple arguments. Returns nothing. Use this.
  print       One argument, returns 1. Almost never needed.
  print_r     Readable structure, NO types. Fine for eyeballing an array shape.
  var_dump    Type + size + value + visibility. THE debugging tool.
  var_export  Valid PHP source. Use for config caching and code generation.

  RULE: when a comparison behaves unexpectedly, reach for var_dump, never
        print_r. The type is almost always the answer.

  RULE: none of these belong in committed code. They leak internal structure
        and break JSON responses. Use a logger, or Xdebug.

 INTERVIEW LINK
   "Difference between echo and print?"           -> multiple args vs returns 1
   "Is echo a function?"                          -> no, language construct
   "print_r vs var_dump?"                         -> types
   "Why is $_POST['qty'] === 0 false?"            -> it is the string "0"

 TRY THIS
   Add   var_dump('10' == '1e1');   and explain the result.
   (Both are numeric strings, so PHP compares them NUMERICALLY: 10 == 10.)
================================================================================
*/
