<?php

/*
================================================================================
 TOPIC 04 - EXAMPLE 4 : CONSTANTS - const vs define(), CLASS CONSTANTS, MAGIC
================================================================================

 WHAT THIS DEMONSTRATES
   Every form of constant in PHP, the exact differences between const and
   define() (a guaranteed written-test question), typed class constants (8.3),
   the magic constants, and - importantly - which values should NOT be
   constants at all.

 HOW TO RUN
   php 04-constants.php

================================================================================
*/

declare(strict_types=1);

echo '=============== 1. const : COMPILE TIME ===============' . PHP_EOL;

const APP_NAME    = 'InvoiceApp';
const TAX_RATE    = 0.18;
const MAX_RETRIES = 3;

// Arrays are allowed in constants (const since 5.6, define since 7.0).
const ALLOWED_UPLOAD_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

// A constant may be built from other constants and simple expressions,
// because all of it is resolvable at compile time.
const APP_TITLE = APP_NAME . ' v2';

printf('  APP_NAME              : %s%s', APP_NAME, PHP_EOL);
printf('  TAX_RATE              : %s%s', TAX_RATE, PHP_EOL);
printf('  APP_TITLE             : %s%s', APP_TITLE, PHP_EOL);
printf('  ALLOWED_UPLOAD_TYPES  : %s%s', implode(', ', ALLOWED_UPLOAD_TYPES), PHP_EOL);

echo PHP_EOL . '=============== 2. define() : RUNTIME ===============' . PHP_EOL;

// dirname(__DIR__) is COMPUTED when the line runs, so const cannot be used.
define('APP_ROOT', dirname(__DIR__));

// Conditional definition - impossible with const, which cannot sit inside an if.
if (!defined('APP_ENV')) {
    // Default to the SAFE value. A misconfigured server should behave as
    // production (errors hidden) rather than accidentally exposing debug output.
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
}

// The constant NAME itself can be dynamic with define() - rare, but possible.
$featureName = 'FEATURE_DARK_MODE';
define($featureName, true);

printf('  APP_ROOT              : %s%s', APP_ROOT, PHP_EOL);
printf('  APP_ENV               : %s%s', APP_ENV, PHP_EOL);
printf('  FEATURE_DARK_MODE     : %s%s', var_export(FEATURE_DARK_MODE, true), PHP_EOL);

echo PHP_EOL . '=============== 3. WHAT const CANNOT DO ===============' . PHP_EOL;

echo '  These are FATAL ERRORS - shown as comments, not executed:' . PHP_EOL;
echo '      if ($x) { const A = 1; }        // const cannot be conditional' . PHP_EOL;
echo '      const B = dirname(__DIR__);     // not resolvable at compile time' . PHP_EOL;
echo '      const C = time();               // ditto' . PHP_EOL;
echo '  Use define() for all three.' . PHP_EOL;

echo PHP_EOL . '=============== 4. CONSTANTS ARE VISIBLE IN EVERY SCOPE ===============' . PHP_EOL;

function taxOn(float $amount): float
{
    // No "global" needed. Unlike variables, constants are visible everywhere.
    // That is convenient - and it is also why constants ARE global state and
    // should be used only for values that genuinely never change.
    return round($amount * TAX_RATE, 2);
}

printf('  taxOn(1000)           : %.2f%s', taxOn(1000.00), PHP_EOL);

echo PHP_EOL . '=============== 5. CLASS CONSTANTS (incl. typed, PHP 8.3) ===============' . PHP_EOL;

final class Order
{
    // TYPED class constants (8.3+). The type stops a subclass redefining the
    // constant with a different type.
    public const string STATUS_PENDING   = 'pending';
    public const string STATUS_PAID      = 'paid';
    public const string STATUS_CANCELLED = 'cancelled';

    public const int MAX_ITEMS = 50;

    // A class constant may reference a global constant.
    public const string LABEL = APP_NAME . ' order';

    // private / protected class constants are allowed too.
    private const string INTERNAL_PREFIX = 'ORD-';

    /** @return array<int, string> */
    public static function validStatuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_CANCELLED];
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::validStatuses(), true);
    }

    public static function reference(int $id): string
    {
        return self::INTERNAL_PREFIX . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }
}

printf('  Order::STATUS_PAID    : %s%s', Order::STATUS_PAID, PHP_EOL);
printf('  Order::MAX_ITEMS      : %d%s', Order::MAX_ITEMS, PHP_EOL);
printf('  Order::LABEL          : %s%s', Order::LABEL, PHP_EOL);
printf('  valid statuses        : %s%s', implode(', ', Order::validStatuses()), PHP_EOL);
printf('  isValidStatus("paid") : %s%s', var_export(Order::isValidStatus('paid'), true), PHP_EOL);
printf('  isValidStatus("PAID") : %s   <- constants are case sensitive%s',
    var_export(Order::isValidStatus('PAID'), true), PHP_EOL);
printf('  Order::reference(42)  : %s%s', Order::reference(42), PHP_EOL);

echo PHP_EOL . '=============== 6. CHECKING AND DYNAMIC ACCESS ===============' . PHP_EOL;

printf('  defined("TAX_RATE")            : %s%s', var_export(defined('TAX_RATE'), true), PHP_EOL);
printf('  defined("NOT_A_CONSTANT")      : %s%s', var_export(defined('NOT_A_CONSTANT'), true), PHP_EOL);
printf('  constant("TAX_RATE")           : %s%s', constant('TAX_RATE'), PHP_EOL);
printf('  constant("Order::STATUS_PAID") : %s%s', constant('Order::STATUS_PAID'), PHP_EOL);

// Reading a constant by a name computed at runtime - useful when mapping a
// database value onto a class constant.
$statusKey = 'STATUS_' . strtoupper('cancelled');
printf('  dynamic: Order::%s = %s%s', $statusKey, constant(Order::class . '::' . $statusKey), PHP_EOL);

echo PHP_EOL . '=============== 7. MAGIC CONSTANTS ===============' . PHP_EOL;

printf('  __LINE__       : %d%s', __LINE__, PHP_EOL);
printf('  __FILE__       : %s%s', basename(__FILE__), PHP_EOL);
printf('  __DIR__        : %s%s', basename(__DIR__), PHP_EOL);
printf('  __FUNCTION__   : "%s"  (empty at file level)%s', __FUNCTION__, PHP_EOL);
printf('  Order::class   : %s%s', Order::class, PHP_EOL);

function showFunctionContext(): void
{
    printf('  inside a function -> __FUNCTION__ = %s%s', __FUNCTION__, PHP_EOL);
}
showFunctionContext();

echo PHP_EOL . '  __DIR__ IS THE IMPORTANT ONE:' . PHP_EOL;
echo '      require __DIR__ . "/../config/database.php";   CORRECT' . PHP_EOL;
echo '      require "../config/database.php";              BREAKS under cron' . PHP_EOL;
echo '  because a relative path depends on the current working directory,' . PHP_EOL;
echo '  which is different when the script is launched by cron or a different' . PHP_EOL;
echo '  entry point. __DIR__ is always the folder of THIS file.' . PHP_EOL;

echo PHP_EOL . '=============== 8. WHAT SHOULD *NOT* BE A CONSTANT ===============' . PHP_EOL;

echo '  BAD  const DB_PASSWORD = "s3cret";' . PHP_EOL;
echo '       -> credentials in source control is a security incident, and the' . PHP_EOL;
echo '          value cannot differ between dev / staging / production.' . PHP_EOL;
echo '       -> put it in .env and read it through a config layer.' . PHP_EOL . PHP_EOL;

echo '  BAD  const FEATURE_X_ENABLED = true;' . PHP_EOL;
echo '       -> a feature flag must be switchable without a code deploy, and a' . PHP_EOL;
echo '          test cannot override a constant.' . PHP_EOL . PHP_EOL;

echo '  BETTER IN MODERN PHP: a set of related values should be an ENUM (8.1+)' . PHP_EOL;
echo '       enum OrderStatus: string {' . PHP_EOL;
echo '           case Pending = "pending";' . PHP_EOL;
echo '           case Paid    = "paid";' . PHP_EOL;
echo '       }' . PHP_EOL;
echo '       -> you then get TYPE SAFETY: a function can declare' . PHP_EOL;
echo '          OrderStatus $status and no invalid string can ever reach it.' . PHP_EOL;
echo '          With constants, any string can be passed. See file 44.' . PHP_EOL;

echo PHP_EOL . '=============== 9. THE COMPARISON TABLE ===============' . PHP_EOL;
echo '  +---------------------------+---------------+------------------+' . PHP_EOL;
echo '  | Feature                   | define()      | const            |' . PHP_EOL;
echo '  +---------------------------+---------------+------------------+' . PHP_EOL;
echo '  | Evaluated at              | runtime       | compile time     |' . PHP_EOL;
echo '  | Conditional (inside if)   | YES           | no (fatal)       |' . PHP_EOL;
echo '  | Runtime value (time())    | YES           | no               |' . PHP_EOL;
echo '  | Inside a class            | no (global!)  | YES              |' . PHP_EOL;
echo '  | Dynamic name              | YES           | no               |' . PHP_EOL;
echo '  | Respects namespace        | no            | YES              |' . PHP_EOL;
echo '  | Arrays                    | YES (7.0+)    | YES (5.6+)       |' . PHP_EOL;
echo '  +---------------------------+---------------+------------------+' . PHP_EOL;
echo '  RULE: use const by default; use define() only for runtime or' . PHP_EOL;
echo '        conditional definitions.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 const APP_NAME = 'InvoiceApp';
     A language construct resolved at COMPILE time. Because of that it cannot
     appear inside an if block, a loop or a function body that runs
     conditionally, and its value cannot come from a function call.

 const APP_TITLE = APP_NAME . ' v2';
     Constant expressions are allowed: concatenation, arithmetic and references
     to other constants are all resolvable at compile time.

 define('APP_ROOT', dirname(__DIR__));
     define() is a FUNCTION, executed at runtime, so it can take a computed
     value. dirname(__DIR__) goes one level above this file's folder - the
     standard way to compute a project root.

 if (!defined('APP_ENV')) { define('APP_ENV', getenv('APP_ENV') ?: 'production'); }
     Two things at once:
       - conditional definition (const cannot do this)
       - defined() prevents a "Constant already defined" warning if the file is
         included twice
     getenv() reads an environment variable; ?: supplies a default when it is
     empty or false. Defaulting to 'production' is a deliberate SAFE default.

 Constants are visible inside functions with no import
     Unlike variables, constants live in a global constant table that every
     scope can read. Convenient - but remember that this makes them global
     state, so restrict them to values that genuinely never change.

 public const string STATUS_PAID = 'paid';
     A TYPED class constant (PHP 8.3+). Before 8.3 you wrote
     "public const STATUS_PAID = 'paid';" with no type. The type prevents a
     subclass from redefining it as, say, an int.

 private const string INTERNAL_PREFIX = 'ORD-';
     Class constants support visibility. Private ones are implementation
     details - here the order-reference prefix, which no caller needs to know.

 self::STATUS_PENDING
     Inside the class, self:: refers to the current class. From outside you
     write Order::STATUS_PENDING. (self vs static matters for inheritance -
     see file 37.)

 str_pad((string) $id, 5, '0', STR_PAD_LEFT)
     Pads to 5 characters with leading zeros, giving ORD-00042. STR_PAD_LEFT is
     what puts the padding on the left.

 in_array($status, self::validStatuses(), true)
     Strict comparison (the third argument). Note that isValidStatus('PAID')
     returns false: constants and string comparison are both case sensitive.

 constant('Order::STATUS_PAID')  and  constant(Order::class . '::' . $key)
     Reads a constant by a NAME built at runtime. The realistic use is mapping a
     value from a database or an API onto a class constant. Note this is exactly
     the kind of dynamic string lookup that an ENUM makes unnecessary.

 Order::class
     Returns the fully qualified class name as a string, resolved at compile
     time. Prefer it to writing 'Order' as a literal, because renaming the class
     in an IDE updates it and static analysis can verify it.

 MAGIC CONSTANTS
     __LINE__      current line number
     __FILE__      full path of this file
     __DIR__       directory of this file        <- use this in every include
     __FUNCTION__  current function name (empty at file level)
     __CLASS__     current class name
     __METHOD__    Class::method
     __NAMESPACE__ current namespace
     They are resolved at COMPILE time based on WHERE they are written, which is
     why __DIR__ is reliable no matter how the script was launched.

================================================================================
 EXPECTED OUTPUT  (abbreviated)
================================================================================

   =============== 1. const : COMPILE TIME ===============
     APP_NAME              : InvoiceApp
     TAX_RATE              : 0.18
     APP_TITLE             : InvoiceApp v2
     ALLOWED_UPLOAD_TYPES  : image/jpeg, image/png, application/pdf

   =============== 5. CLASS CONSTANTS (incl. typed, PHP 8.3) ===============
     Order::STATUS_PAID    : paid
     Order::MAX_ITEMS      : 50
     Order::LABEL          : InvoiceApp order
     isValidStatus("paid") : true
     isValidStatus("PAID") : false   <- constants are case sensitive
     Order::reference(42)  : ORD-00042

   =============== 6. CHECKING AND DYNAMIC ACCESS ===============
     defined("TAX_RATE")            : true
     constant("Order::STATUS_PAID") : paid
     dynamic: Order::STATUS_CANCELLED = cancelled

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. const  = compile time, works in classes, respects namespaces, cannot be
              conditional.
     define = runtime, can be conditional, can take a dynamic name, always
              global.
  2. Use const by default. Use define() for APP_ROOT-style computed values and
     conditional definitions.
  3. Class constants belong to the class that owns the concept:
     Order::STATUS_PAID, not a loose global STATUS_PAID.
  4. Typed class constants exist since PHP 8.3.
  5. Constants are visible in every scope and can never change - so keep them
     for values that genuinely never change.
  6. Credentials and per-environment values must NOT be constants. Use .env and
     a config layer.
  7. A set of related values should be an ENUM in modern PHP, for type safety.
  8. Always build include paths with __DIR__.

 INTERVIEW LINK
   "What is the difference between define() and const?"     -> the table
   "Can you define a constant inside an if block?"          -> only with define()
   "What is __DIR__ used for?"
   "Where would you store a database password?"             -> not a constant
   "When would you use an enum instead of class constants?"

 TRY THIS
   1. Uncomment a "const X = time();" line and read the fatal error text.
   2. Try define('TAX_RATE', 0.2) after the const and observe the warning that
      the constant is already defined - constants are immutable.
   3. Rewrite Order's three status constants as a backed enum and note how the
      isValidStatus() method becomes unnecessary (tryFrom() replaces it).
================================================================================
*/
