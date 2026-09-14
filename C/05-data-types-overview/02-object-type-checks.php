<?php

/*
================================================================================
 TOPIC 05 - EXAMPLE 2 : CHECKING OBJECT TYPES (instanceof vs get_class)
================================================================================

 WHAT THIS DEMONSTRATES
   Why "instanceof" is almost always right and comparing class names is almost
   always wrong - shown with a subclass that silently breaks the class-name
   version. This is a real bug pattern in authorisation and dispatch code.

 HOW TO RUN
   php 02-object-type-checks.php

================================================================================
*/

declare(strict_types=1);

interface Payable
{
    public function amountInPaise(): int;
}

interface Refundable
{
    public function isRefundable(): bool;
}

abstract class Document
{
    public function __construct(protected readonly int $id)
    {
    }

    public function id(): int
    {
        return $this->id;
    }
}

class Invoice extends Document implements Payable
{
    public function __construct(int $id, private readonly int $paise)
    {
        parent::__construct($id);
    }

    public function amountInPaise(): int
    {
        return $this->paise;
    }
}

final class RecurringInvoice extends Invoice implements Refundable
{
    public function isRefundable(): bool
    {
        return true;
    }
}

final class Receipt extends Document
{
}

echo '=============== 1. WHAT EACH OBJECT IS ===============' . PHP_EOL . PHP_EOL;

$objects = [
    new Invoice(1, 250_000),
    new RecurringInvoice(2, 99_900),
    new Receipt(3),
    new stdClass(),
];

printf("  %-18s | %-9s | %-8s | %-9s | %s%s",
    'CLASS', 'Document', 'Invoice', 'Payable', 'Refundable', PHP_EOL);
echo '  ' . str_repeat('-', 70) . PHP_EOL;

foreach ($objects as $object) {
    printf("  %-18s | %-9s | %-8s | %-9s | %s%s",
        get_debug_type($object),
        $object instanceof Document   ? 'yes' : 'no',
        $object instanceof Invoice    ? 'yes' : 'no',
        $object instanceof Payable    ? 'yes' : 'no',
        $object instanceof Refundable ? 'yes' : 'no',
        PHP_EOL
    );
}

echo PHP_EOL . '  instanceof follows the WHOLE chain: parent classes, grandparents,' . PHP_EOL;
echo '  interfaces, and interfaces inherited from a parent.' . PHP_EOL;

echo PHP_EOL . '=============== 2. THE BUG: COMPARING CLASS NAMES ===============' . PHP_EOL;

/**
 * WRONG. Works today, breaks the moment anyone adds a subclass.
 */
function canBePaidWrong(object $doc): bool
{
    return $doc::class === Invoice::class;      // exact match only
}

/**
 * CORRECT. Works for every current and future implementation.
 */
function canBePaidRight(object $doc): bool
{
    return $doc instanceof Payable;             // contract, not class identity
}

$normal    = new Invoice(1, 250_000);
$recurring = new RecurringInvoice(2, 99_900);

printf('  Invoice          -> wrong:%-6s right:%s%s',
    var_export(canBePaidWrong($normal), true),
    var_export(canBePaidRight($normal), true), PHP_EOL);

printf('  RecurringInvoice -> wrong:%-6s right:%s%s',
    var_export(canBePaidWrong($recurring), true),
    var_export(canBePaidRight($recurring), true), PHP_EOL);

echo PHP_EOL . '  ^ RecurringInvoice IS an invoice and IS payable, but the class-name' . PHP_EOL;
echo '    check says false. In a payment handler that means a customer cannot' . PHP_EOL;
echo '    pay their recurring invoice - and no error is thrown anywhere.' . PHP_EOL;
echo '    Adding a subclass silently broke unrelated code. That violates the' . PHP_EOL;
echo '    Liskov Substitution Principle (the L in SOLID - see file 62).' . PHP_EOL;

echo PHP_EOL . '=============== 3. THE FULL TOOLKIT ===============' . PHP_EOL;

$obj = new RecurringInvoice(2, 99_900);

printf('  %-42s : %s%s', '$obj::class', $obj::class, PHP_EOL);
printf('  %-42s : %s%s', 'get_class($obj)', get_class($obj), PHP_EOL);
printf('  %-42s : %s%s', 'get_parent_class($obj)', get_parent_class($obj), PHP_EOL);
printf('  %-42s : %s%s', 'get_debug_type($obj)', get_debug_type($obj), PHP_EOL);

printf('  %-42s : %s%s', '$obj instanceof Invoice',
    var_export($obj instanceof Invoice, true), PHP_EOL);
printf('  %-42s : %s%s', 'is_a($obj, Invoice::class)',
    var_export(is_a($obj, Invoice::class), true), PHP_EOL);
printf('  %-42s : %s   <- self does NOT count%s', 'is_subclass_of($obj, RecurringInvoice::class)',
    var_export(is_subclass_of($obj, RecurringInvoice::class), true), PHP_EOL);
printf('  %-42s : %s%s', 'is_subclass_of($obj, Invoice::class)',
    var_export(is_subclass_of($obj, Invoice::class), true), PHP_EOL);

printf('  %-42s : %s%s', 'method_exists($obj, "amountInPaise")',
    var_export(method_exists($obj, 'amountInPaise'), true), PHP_EOL);
printf('  %-42s : %s%s', 'property_exists($obj, "paise")',
    var_export(property_exists($obj, 'paise'), true), PHP_EOL);

printf('  %-42s : %s%s', 'class_implements() count',
    (string) count(class_implements($obj) ?: []), PHP_EOL);
printf('  %-42s : %s%s', '  interfaces',
    implode(', ', array_keys(class_implements($obj) ?: [])), PHP_EOL);
printf('  %-42s : %s%s', 'class_parents()',
    implode(', ', array_keys(class_parents($obj) ?: [])), PHP_EOL);

echo PHP_EOL . '=============== 4. POLYMORPHIC DISPATCH (THE REAL PATTERN) ===============' . PHP_EOL;

/**
 * Process a mixed list of documents. Notice there is no switch on class names -
 * the type declarations and instanceof checks do the work, so adding a new
 * document class requires NO change here.
 *
 * @param array<int, object> $documents
 */
function processDocuments(array $documents): void
{
    foreach ($documents as $doc) {
        $line = '  ' . str_pad(get_debug_type($doc), 18);

        if ($doc instanceof Payable) {
            $line .= sprintf('payable: Rs. %s', number_format($doc->amountInPaise() / 100, 2));
        } else {
            $line .= 'not payable';
        }

        if ($doc instanceof Refundable && $doc->isRefundable()) {
            $line .= '  [refundable]';
        }

        echo $line . PHP_EOL;
    }
}

processDocuments([
    new Invoice(1, 250_000),
    new RecurringInvoice(2, 99_900),
    new Receipt(3),
]);

echo PHP_EOL . '=============== 5. WHEN EXACT CLASS MATCHING IS CORRECT ===============' . PHP_EOL;

echo '  Rare, but real:' . PHP_EOL;
echo '    - You deliberately want THIS class and not any subclass, for example' . PHP_EOL;
echo '      when a subclass changes behaviour you must not accept.' . PHP_EOL;
echo '    - Serialisation or a factory mapping a stored class name back to a' . PHP_EOL;
echo '      concrete class.' . PHP_EOL;
echo '    - Debug output and logging: get_debug_type() is exactly right there.' . PHP_EOL;
echo '  Otherwise: instanceof, and prefer an INTERFACE over a concrete class,' . PHP_EOL;
echo '  because that is the contract you actually depend on.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 interface Payable / interface Refundable
     An interface is a CONTRACT: any class implementing it promises those
     methods exist. Checking against an interface rather than a class is what
     makes code extensible - new classes can satisfy it without touching the
     checking code.

 abstract class Document
     Cannot be instantiated directly; it exists to be extended and to share the
     $id property and id() method. Covered fully in file 42.

 class RecurringInvoice extends Invoice implements Refundable
     It inherits everything from Invoice - INCLUDING the Payable interface that
     Invoice implements. That is why the table shows "yes" in the Payable
     column for RecurringInvoice even though its own declaration never mentions
     Payable.

 $doc::class === Invoice::class          <- THE BUG
     ::class returns the fully qualified class name as a string. Comparing them
     is an EXACT match, so a subclass fails the test. The failure is silent: no
     error, no exception, just a feature that quietly stops working for one type
     of record. This is why reviewers flag class-name comparisons.

 $doc instanceof Payable                 <- THE FIX
     instanceof walks the full inheritance chain and the interface list. It asks
     "can this object do the job?" rather than "is it exactly this class?".

 is_a($obj, Invoice::class)
     Same result as instanceof for objects. instanceof is preferred in code
     because it is an operator (no function call, and the IDE understands it).
     is_a() is useful when the class name is only known as a string at runtime.

 is_subclass_of($obj, RecurringInvoice::class) is FALSE
     "Subclass of" excludes the class itself. An object is not a subclass of its
     own class. If you want "is it this class or a descendant", use instanceof.
     This off-by-one is a classic interview trap.

 property_exists($obj, 'paise') is TRUE even though $paise is private
     property_exists() reports declared properties regardless of visibility. It
     answers "does the class declare this?", not "can I access it from here?".

 class_implements($obj) / class_parents($obj)
     Return arrays keyed by interface/class name, covering the whole chain.
     Useful in debugging, container wiring and framework internals.

 processDocuments(array $documents)
     The polymorphic pattern. There is no switch and no list of class names, so
     adding a CreditNote class that implements Payable requires zero changes
     here. Compare that with a switch statement, which must be edited every time
     a new type appears - the Open/Closed Principle in practice (file 62).

 $doc instanceof Refundable && $doc->isRefundable()
     Note the order. The instanceof check must come FIRST, because calling
     isRefundable() on an object that does not have it is a fatal error. && is
     short-circuiting: if the left side is false, the right side never runs.

================================================================================
 EXPECTED OUTPUT  (main sections)
================================================================================

   =============== 1. WHAT EACH OBJECT IS ===============
   CLASS              | Document  | Invoice  | Payable   | Refundable
   ----------------------------------------------------------------------
   Invoice            | yes       | yes      | yes       | no
   RecurringInvoice   | yes       | yes      | yes       | yes
   Receipt            | yes       | no       | no        | no
   stdClass           | no        | no       | no        | no

   =============== 2. THE BUG: COMPARING CLASS NAMES ===============
     Invoice          -> wrong:true  right:true
     RecurringInvoice -> wrong:false right:true

   =============== 3. THE FULL TOOLKIT ===============
     $obj::class                                : RecurringInvoice
     get_parent_class($obj)                     : Invoice
     is_subclass_of($obj, RecurringInvoice::class) : false   <- self does NOT count
     is_subclass_of($obj, Invoice::class)       : true
     interfaces                                 : Refundable, Payable

   =============== 4. POLYMORPHIC DISPATCH (THE REAL PATTERN) ===============
     Invoice           payable: Rs. 2,500.00
     RecurringInvoice  payable: Rs. 999.00  [refundable]
     Receipt           not payable

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. instanceof respects inheritance AND interfaces. Class-name comparison does
     not.
  2. Comparing $obj::class === Foo::class silently excludes subclasses and
     breaks polymorphism. Reviewers treat it as a code smell.
  3. Prefer checking against an INTERFACE - that is the contract you depend on.
  4. is_subclass_of() excludes the class itself; instanceof includes it.
  5. property_exists() ignores visibility; it reports declaration, not access.
  6. The goal is code where adding a new class requires no changes to existing
     checks. instanceof plus interfaces gets you there; switch on class names
     does not.

 INTERVIEW LINK
   "Difference between instanceof and get_class()?"
   "How do you check if an object implements an interface?"
   "What is polymorphism in PHP?"                     -> section 4
   "Why is checking class names an anti-pattern?"     -> section 2
   "Difference between is_a() and is_subclass_of()?"

 TRY THIS
   Add   final class CreditNote extends Document implements Payable, Refundable
   with the two required methods, then pass it to processDocuments(). It works
   immediately with NO change to processDocuments() - that is the whole benefit
   of programming against interfaces.
================================================================================
*/
