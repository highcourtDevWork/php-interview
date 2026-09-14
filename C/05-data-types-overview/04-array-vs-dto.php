<?php

/*
================================================================================
 TOPIC 05 - EXAMPLE 4 : ASSOCIATIVE ARRAY vs TYPED OBJECT (DTO)
================================================================================

 WHAT THIS DEMONSTRATES
   The decision every PHP developer makes dozens of times a day: should this
   data be an array or a class? The same data is modelled both ways here so you
   can see exactly what the array version costs you.

   THE RULE OF THUMB
     If you find yourself documenting an array's shape in a DocBlock,
     it should be a class.

 HOW TO RUN
   php 04-array-vs-dto.php

 LOOK AT YOUR EDITOR WHILE READING THIS FILE
   Your IDE underlines the deliberate mistakes in SECTION 2 (the DTO version):
       "Expected type 'float'. Found ''not a number''."
       "Cannot modify readonly property Product::$price"
   It says NOTHING about the equivalent mistakes in SECTION 1 (the array
   version), even though they are the same mistakes.

   That contrast IS the lesson, and you can see it before running anything:
   with a typed object the tooling finds the bug as you type; with an array
   nobody finds it until a customer reports a wrong invoice.

================================================================================
*/

declare(strict_types=1);

echo '=============== 1. THE ARRAY VERSION ===============' . PHP_EOL;

/**
 * Note how much documentation is needed just to describe the shape - and
 * nothing enforces any of it at runtime.
 *
 * @param array{name: string, email: string, price: float, quantity: int} $product
 */
function describeArray(array $product): string
{
    return sprintf(
        '%s - Rs. %s x %d = Rs. %s',
        $product['name'],
        number_format($product['price'], 2),
        $product['quantity'],
        number_format($product['price'] * $product['quantity'], 2)
    );
}

$arrayProduct = [
    'name'     => 'Blue Gel Pen',
    'email'    => 'seller@example.com',
    'price'    => 99.50,
    'quantity' => 25,
];

echo '  ' . describeArray($arrayProduct) . PHP_EOL;

echo PHP_EOL . '  Now the four ways this breaks:' . PHP_EOL . PHP_EOL;

// --- BREAKAGE 1: a typo in a key is only found at runtime -------------------
echo '  (a) TYPO IN A KEY' . PHP_EOL;
$typo = $arrayProduct;
unset($typo['quantity']);
$typo['quantiy'] = 25;                      // <- misspelled

$previous = error_reporting(E_ALL & ~E_WARNING);
echo '      ' . @describeArray($typo) . '   <- quantity became 0, silently' . PHP_EOL;
error_reporting($previous);

// --- BREAKAGE 2: wrong types are not caught where they ENTER ---------------
echo PHP_EOL . '  (b) WRONG TYPES FAIL LATE, IN THE WRONG PLACE' . PHP_EOL;
$wrongTypes = [
    'name'     => 'Pen',
    'email'    => 'seller@example.com',
    'price'    => '99.50',                  // a STRING, not a float
    'quantity' => '25',                     // a STRING, not an int
];

try {
    echo '      ' . describeArray($wrongTypes) . PHP_EOL;
} catch (TypeError $e) {
    echo '      TypeError: ' . $e->getMessage() . PHP_EOL;
    echo '      ^ Note WHERE it blew up: inside number_format(), three call' . PHP_EOL;
    echo '        frames away from where the bad data entered. The array' . PHP_EOL;
    echo '        accepted the wrong types happily; only a formatting function' . PHP_EOL;
    echo '        deep inside eventually complained. Debugging this in a real' . PHP_EOL;
    echo '        app means tracing backwards through the call stack to find' . PHP_EOL;
    echo '        which caller supplied a string.' . PHP_EOL;
    echo '      ^ WORSE: remove declare(strict_types=1) from this file and the' . PHP_EOL;
    echo '        same code runs with NO error at all - PHP coerces "99.50" to' . PHP_EOL;
    echo '        99.5 silently. The array never validated anything; the only' . PHP_EOL;
    echo '        thing that objected was an internal function under strict' . PHP_EOL;
    echo '        types.' . PHP_EOL;
}

// --- BREAKAGE 3: invalid data can be constructed --------------------------
echo PHP_EOL . '  (c) INVALID DATA IS CONSTRUCTIBLE' . PHP_EOL;
$invalid = ['name' => '', 'email' => 'not-an-email', 'price' => -50.0, 'quantity' => -3];
echo '      ' . describeArray($invalid) . '   <- negative money, empty name' . PHP_EOL;

// --- BREAKAGE 4: anything can modify it anywhere ---------------------------
echo PHP_EOL . '  (d) MUTABLE FROM ANYWHERE' . PHP_EOL;
$arrayProduct['price'] = 0.0;               // no guard, no audit trail
echo '      price silently set to 0 by unrelated code' . PHP_EOL;

echo PHP_EOL . '=============== 2. THE TYPED OBJECT (DTO) VERSION ===============' . PHP_EOL;

/**
 * A Data Transfer Object.
 *
 * readonly promoted properties give you:
 *   - the shape enforced by the language, not by a comment
 *   - types checked at construction
 *   - validation in ONE place, impossible to bypass
 *   - immutability: no distant code can change it
 *   - IDE autocompletion and safe renaming
 */
final class Product
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly float $price,
        public readonly int $quantity,
    ) {
        // Validation lives WITH the data. An invalid Product cannot exist.
        if (trim($name) === '') {
            throw new InvalidArgumentException('name must not be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('email is not valid: ' . $email);
        }

        if ($price < 0) {
            throw new InvalidArgumentException('price must be 0 or greater');
        }

        if ($quantity < 0) {
            throw new InvalidArgumentException('quantity must be 0 or greater');
        }
    }

    /**
     * A named constructor for building from untrusted input (a form, an API).
     *
     * @param array<string, mixed> $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name:     is_string($input['name'] ?? null) ? trim($input['name']) : '',
            email:    (string) ($input['email'] ?? ''),
            price:    (float) ($input['price'] ?? 0),
            quantity: (int) ($input['quantity'] ?? 0),
        );
    }

    public function lineTotal(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    public function describe(): string
    {
        return sprintf(
            '%s - Rs. %s x %d = Rs. %s',
            $this->name,
            number_format($this->price, 2),
            $this->quantity,
            number_format($this->lineTotal(), 2)
        );
    }

    /**
     * Immutable update: returns a NEW object instead of mutating this one.
     */
    public function withQuantity(int $quantity): self
    {
        return new self($this->name, $this->email, $this->price, $quantity);
    }
}

$product = new Product('Blue Gel Pen', 'seller@example.com', 99.50, 25);
echo '  ' . $product->describe() . PHP_EOL;

echo PHP_EOL . '  The same four failures, now caught:' . PHP_EOL . PHP_EOL;

// --- (a) typo -> a real error, immediately --------------------------------
echo '  (a) TYPO: $product->quantiy' . PHP_EOL;
echo '      -> Warning: Undefined property, and the IDE underlines it as you' . PHP_EOL;
echo '         type. With an array you get no IDE help at all.' . PHP_EOL;

// --- (b) wrong types -> TypeError -----------------------------------------
echo PHP_EOL . '  (b) WRONG TYPES' . PHP_EOL;
try {
    /** @phpstan-ignore-next-line - deliberate type error for the demo */
    new Product('Pen', 'seller@example.com', 'not a number', 25);
} catch (TypeError $e) {
    echo '      TypeError caught: ' . substr($e->getMessage(), 0, 78) . '...' . PHP_EOL;
}

// --- (c) invalid data -> impossible to construct --------------------------
echo PHP_EOL . '  (c) INVALID DATA' . PHP_EOL;
foreach ([
    ['', 'seller@example.com', 99.5, 1],
    ['Pen', 'not-an-email', 99.5, 1],
    ['Pen', 'seller@example.com', -50.0, 1],
] as [$n, $e, $p, $q]) {
    try {
        new Product($n, $e, $p, $q);
        echo '      constructed (unexpected)' . PHP_EOL;
    } catch (InvalidArgumentException $ex) {
        echo '      rejected: ' . $ex->getMessage() . PHP_EOL;
    }
}

// --- (d) immutability ------------------------------------------------------
echo PHP_EOL . '  (d) IMMUTABILITY' . PHP_EOL;
try {
    /** @phpstan-ignore-next-line - deliberate readonly violation for the demo */
    $product->price = 0.0;
} catch (Error $e) {
    echo '      ' . $e->getMessage() . PHP_EOL;
}

$updated = $product->withQuantity(50);
printf('      original quantity: %d   (unchanged)%s', $product->quantity, PHP_EOL);
printf('      new object       : %d%s', $updated->quantity, PHP_EOL);

echo PHP_EOL . '=============== 3. BUILDING FROM UNTRUSTED INPUT ===============' . PHP_EOL;

$formInput = ['name' => '  Notebook  ', 'email' => 'seller@example.com', 'price' => '149.00', 'quantity' => '3'];

try {
    $fromForm = Product::fromArray($formInput);
    echo '  ' . $fromForm->describe() . PHP_EOL;
    printf('  price is a real %s, quantity is a real %s%s',
        get_debug_type($fromForm->price), get_debug_type($fromForm->quantity), PHP_EOL);
} catch (InvalidArgumentException $e) {
    echo '  rejected: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . '=============== 4. WHEN AN ARRAY IS STILL THE RIGHT CHOICE ===============' . PHP_EOL;

echo '  USE AN ARRAY when the shape is genuinely dynamic:' . PHP_EOL;
echo '    - a config map read from a file' . PHP_EOL;
echo '    - request query parameters, before validation' . PHP_EOL;
echo '    - a CSV row whose columns vary between files' . PHP_EOL;
echo '    - a lookup table: ["IN" => "India", "AE" => "UAE"]' . PHP_EOL;
echo '    - a list of items (a list OF DTOs is still an array)' . PHP_EOL . PHP_EOL;

echo '  USE A CLASS when the shape is known:' . PHP_EOL;
echo '    - a User, an Order, a Product, an ApiResponse' . PHP_EOL;
echo '    - anything crossing a layer boundary (controller -> service)' . PHP_EOL;
echo '    - anything with rules attached (a valid email, a non-negative price)' . PHP_EOL;
echo '    - anything you catch yourself documenting with @param array{...}' . PHP_EOL;

echo PHP_EOL . '=============== 5. SIDE-BY-SIDE COMPARISON ===============' . PHP_EOL;
echo '  +---------------------------+------------------+--------------------+' . PHP_EOL;
echo '  |                           | array            | typed DTO          |' . PHP_EOL;
echo '  +---------------------------+------------------+--------------------+' . PHP_EOL;
echo '  | Typo in a key/property    | silent, runtime  | IDE + runtime error|' . PHP_EOL;
echo '  | Wrong value type          | accepted         | TypeError          |' . PHP_EOL;
echo '  | Invalid data              | constructible    | impossible         |' . PHP_EOL;
echo '  | Where validation lives    | scattered        | one constructor    |' . PHP_EOL;
echo '  | IDE autocompletion        | none             | full               |' . PHP_EOL;
echo '  | Safe rename/refactor      | no               | yes                |' . PHP_EOL;
echo '  | Can add behaviour         | no               | yes (lineTotal())  |' . PHP_EOL;
echo '  | Immutability              | no               | readonly           |' . PHP_EOL;
echo '  | Flexible shape            | YES              | no                 |' . PHP_EOL;
echo '  +---------------------------+------------------+--------------------+' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 @param array{name: string, email: string, price: float, quantity: int} $product
     A PHPStan ARRAY SHAPE. It documents the structure so the IDE and static
     analysis can help - but PHP itself enforces NOTHING at runtime. Section 1
     proves this: a string price passes straight through. The moment you need
     this annotation, you are describing a class in a comment.

 $typo['quantiy'] = 25;
     One transposed letter. PHP emits a warning for the missing 'quantity' key
     and treats it as null, so the multiplication yields 0 and the invoice line
     shows Rs. 0.00. A wrong number, not a crash - the worst kind of bug,
     because nothing alerts anyone.

 public function __construct(public readonly string $name, ...)
     CONSTRUCTOR PROPERTY PROMOTION (8.0) + readonly (8.1). Each parameter
     declares a property, types it, assigns it and locks it. The type
     declarations are enforced at runtime, so a string price throws a TypeError
     at the boundary rather than producing a wrong total three layers later.

 Validation inside the constructor
     This is the key idea: an INVALID Product CANNOT EXIST. Anywhere in the
     codebase that you hold a Product, you know the name is non-empty, the
     email is valid and the price is not negative - without re-checking. With
     arrays, every function must defensively re-validate, and eventually one
     forgets.

 public static function fromArray(array $input): self
     A NAMED CONSTRUCTOR. The raw array from a form enters here, is converted,
     and never travels further into the application. This is the boundary
     pattern from example 03, expressed as a class.

 public function withQuantity(int $quantity): self
     The immutable-update pattern: return a NEW object rather than mutating.
     This is exactly how DateTimeImmutable works. It means no distant code can
     change an object you are holding.

 $product->price = 0.0;  throws Error
     "Cannot modify readonly property". readonly makes the guarantee structural
     rather than a convention people are asked to follow.

 catch (TypeError $e) and catch (Error $e)
     TypeError and Error are Throwable but NOT Exception - they are the "you
     have a bug" branch of the hierarchy (file 47). Catching them here is only
     to keep the demo running; in real code you let them crash loudly in
     development and log them in production.

 [$n, $e, $p, $q] in the foreach
     Array destructuring: each inner array is unpacked into four named
     variables, which reads far better than $case[0], $case[1], ...

 WHY THIS MATTERS FOR INTERVIEWS
     "Array or object?" is a design question interviewers use to gauge
     seniority. The junior answer is "arrays are easier". The senior answer is
     "arrays for dynamic shapes, typed objects for known shapes, because the
     type system then catches an entire class of bugs for free".

================================================================================
 EXPECTED OUTPUT  (abbreviated)
================================================================================

   =============== 1. THE ARRAY VERSION ===============
     Blue Gel Pen - Rs. 99.50 x 25 = Rs. 2,487.50

     (a) TYPO IN A KEY
         Blue Gel Pen - Rs. 99.50 x 0 = Rs. 0.00   <- quantity became 0, silently
     (b) WRONG TYPES FAIL LATE, IN THE WRONG PLACE
         TypeError: number_format(): Argument #1 ($num) must be of type
         int|float, string given
         (thrown deep inside a formatting call, not where the data entered)
     (c) INVALID DATA IS CONSTRUCTIBLE
          - Rs. -50.00 x -3 = Rs. 150.00   <- negative money, empty name

   =============== 2. THE TYPED OBJECT (DTO) VERSION ===============
     Blue Gel Pen - Rs. 99.50 x 25 = Rs. 2,487.50

     (b) WRONG TYPES
         TypeError caught: Product::__construct(): Argument #3 ($price) must be
         of type float, string given...
     (c) INVALID DATA
         rejected: name must not be empty
         rejected: email is not valid: not-an-email
         rejected: price must be 0 or greater
     (d) IMMUTABILITY
         Cannot modify readonly property Product::$price
         original quantity: 25   (unchanged)
         new object       : 50

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. An array's shape is a COMMENT. A class's shape is ENFORCED.
  2. Validation in a constructor means invalid objects cannot exist - so no
     other code has to re-check.
  3. readonly + promotion makes a DTO about six lines long. The cost of using a
     class instead of an array is now very small.
  4. Immutable updates (withX methods) prevent action at a distance.
  5. Keep arrays for genuinely dynamic data; convert to objects at the boundary.
  6. THE HEURISTIC: if you are writing @param array{...}, write a class instead.

 INTERVIEW LINK
   "When would you use an associative array and when a class?"
   "What is a DTO?"
   "What does readonly give you?"
   "Where should validation live?"          -> with the data, in the constructor
   "How do you make an object immutable?"   -> readonly + withX() methods

 TRY THIS
   1. Add a discountedPrice(float $percent): float method to Product. Notice
      you cannot add behaviour to an array at all - you would need a separate
      function that re-validates its input.
   2. Try to construct a Product with quantity '25' (a string) under
      declare(strict_types=1) and read the TypeError. Then delete the
      strict_types line and observe PHP silently converting it - which is the
      best possible argument for always declaring it.
================================================================================
*/
