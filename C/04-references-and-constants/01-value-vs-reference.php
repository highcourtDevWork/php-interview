<?php

/*
================================================================================
 TOPIC 04 - EXAMPLE 1 : VALUE vs REFERENCE vs OBJECT HANDLE
================================================================================

 WHAT THIS DEMONSTRATES
   The three different assignment behaviours in PHP, and the test that proves
   objects are NOT passed by reference - which is one of the most common
   interview questions and one of the most commonly wrong answers.

       $b = $a       scalars/arrays -> independent COPY
       $b = &$a      any type       -> same storage, two names
       $b = $a       objects        -> copy of the HANDLE (looks like a
                                       reference, but is not one)

 HOW TO RUN
   php 01-value-vs-reference.php

================================================================================
*/

declare(strict_types=1);

final class Cart
{
    /** @param array<int, string> $items */
    public function __construct(public array $items = [])
    {
    }
}

echo '=============== 1. SCALARS BY VALUE (the default) ===============' . PHP_EOL;

$a = 10;
$b = $a;          // COPY
$b = 20;

printf('  $a = %d   $b = %d   -> independent%s', $a, $b, PHP_EOL);

echo PHP_EOL . '=============== 2. SCALARS BY REFERENCE (&) ===============' . PHP_EOL;

$c = 10;
$d = &$c;         // SAME storage, two names
$d = 20;

printf('  $c = %d   $d = %d   -> changing $d changed $c%s', $c, $d, PHP_EOL);

unset($d);        // removes the NAME $d, not the value
printf('  after unset($d): $c = %d   (the value survives)%s', $c, PHP_EOL);

echo PHP_EOL . '=============== 3. ARRAYS ARE VALUES TOO ===============' . PHP_EOL;

$list1 = ['a', 'b'];
$list2 = $list1;      // no physical copy yet (copy-on-write)
$list2[] = 'c';       // the WRITE forces the real copy

printf('  $list1 = [%s]%s', implode(', ', $list1), PHP_EOL);
printf('  $list2 = [%s]   -> independent, exactly like a scalar%s', implode(', ', $list2), PHP_EOL);

$list3 = &$list1;     // now a real reference
$list3[] = 'z';

printf('  after $list3 = &$list1 and appending: $list1 = [%s]%s', implode(', ', $list1), PHP_EOL);

echo PHP_EOL . '=============== 4. OBJECTS: MODIFY vs REPLACE ===============' . PHP_EOL;

$cart1 = new Cart(['pen']);
$cart2 = $cart1;                  // copies the HANDLE, not the object

echo '  step 1: $cart2 = $cart1' . PHP_EOL;
printf('          same object? %s   (spl_object_id: %d vs %d)%s',
    $cart1 === $cart2 ? 'yes' : 'no',
    spl_object_id($cart1),
    spl_object_id($cart2),
    PHP_EOL
);

// ---- MODIFYING through one name IS visible through the other ----
$cart2->items[] = 'book';
echo '  step 2: $cart2->items[] = "book"' . PHP_EOL;
printf('          $cart1->items = [%s]   <- changed too%s', implode(', ', $cart1->items), PHP_EOL);

// ---- REPLACING one name is NOT visible through the other ----
$cart2 = new Cart(['eraser']);
echo '  step 3: $cart2 = new Cart(["eraser"])' . PHP_EOL;
printf('          $cart1->items = [%s]   <- UNCHANGED%s', implode(', ', $cart1->items), PHP_EOL);
printf('          $cart2->items = [%s]%s', implode(', ', $cart2->items), PHP_EOL);

echo PHP_EOL . '  ^ STEP 3 IS THE PROOF. If objects were true references, $cart1' . PHP_EOL;
echo '    would now show "eraser". It does not. The handle was copied.' . PHP_EOL;

echo PHP_EOL . '=============== 5. A TRUE OBJECT REFERENCE (&) ===============' . PHP_EOL;

$cart3 = new Cart(['notebook']);
$cart4 = &$cart3;                 // a REAL reference this time

$cart4 = new Cart(['replaced']);  // replacing now affects both

printf('  $cart3->items = [%s]   <- with &, replacing DOES propagate%s',
    implode(', ', $cart3->items), PHP_EOL);

echo PHP_EOL . '=============== 6. GETTING A REAL COPY: clone ===============' . PHP_EOL;

$original = new Cart(['pen', 'book']);
$shared   = $original;            // same object
$copy     = clone $original;      // NEW object

$copy->items[] = 'added to the clone only';

printf('  $original->items : [%s]%s', implode(', ', $original->items), PHP_EOL);
printf('  $shared->items   : [%s]%s', implode(', ', $shared->items), PHP_EOL);
printf('  $copy->items     : [%s]%s', implode(', ', $copy->items), PHP_EOL);

printf('  $original === $shared : %s%s', var_export($original === $shared, true), PHP_EOL);
printf('  $original === $copy   : %s   (different object)%s', var_export($original === $copy, true), PHP_EOL);
printf('  $original ==  $copy   : %s   (same class, but now different contents)%s',
    var_export($original == $copy, true), PHP_EOL);

echo PHP_EOL . '=============== 7. THE SUMMARY TABLE ===============' . PHP_EOL;
echo '  +----------------+---------------------+----------------------------+' . PHP_EOL;
echo '  | Assignment     | What is copied      | Other name sees changes?   |' . PHP_EOL;
echo '  +----------------+---------------------+----------------------------+' . PHP_EOL;
echo '  | $b = $a  (int) | the value           | no                         |' . PHP_EOL;
echo '  | $b = $a  (arr) | the value (via COW) | no                         |' . PHP_EOL;
echo '  | $b = $a  (obj) | the HANDLE          | MODIFY yes / REPLACE no    |' . PHP_EOL;
echo '  | $b = &$a (any) | nothing - one slot  | yes, both ways             |' . PHP_EOL;
echo '  | clone $a (obj) | a new object        | no                         |' . PHP_EOL;
echo '  +----------------+---------------------+----------------------------+' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 $b = $a;   (scalars)
     Copies the value into a separate storage slot. Two independent boxes.

 $d = &$c;
     The & binds both names to ONE storage slot. Think of two labels on one box.
     Assigning through either name changes what both names see.

 unset($d);
     Removes the NAME only. The value and the other name are untouched. This is
     the standard way to break a reference - and the fix for the famous foreach
     reference bug (file 15).

 $list2 = $list1;  then  $list2[] = 'c';
     Arrays are VALUES in PHP, so this behaves exactly like the scalar case.
     Internally PHP does not physically copy until the append (copy-on-write) -
     but the observable behaviour is a plain copy. See example 03.

 spl_object_id($object)
     Returns the internal identifier of an object. Two variables pointing to the
     SAME object return the SAME id. This is the cleanest way to prove that
     $cart2 = $cart1 did not create a second object.

 $cart1 === $cart2   (objects)
     For objects, === means "the very same instance". == means "same class and
     equal property values". This distinction appears constantly in interviews
     and is covered in depth in file 46.

 STEP 2 vs STEP 3 - THE HEART OF THIS FILE

     step 2  $cart2->items[] = 'book';
             MODIFIES the object both handles point at, so $cart1 sees it.

     step 3  $cart2 = new Cart(['eraser']);
             REPLACES what the local variable $cart2 points at. $cart1 still
             points at the original object, so it is unaffected.

     If PHP passed objects "by reference", step 3 would change $cart1 too. It
     does not. Therefore: assignment copies the HANDLE, not the object, and a
     handle is not a reference. Saying "objects are passed by reference" in an
     interview is the wrong answer; "the handle is copied" is the right one.

 $cart4 = &$cart3;
     With an explicit &, you DO get a true reference, and step-3-style
     replacement propagates. This contrast is what makes the distinction
     concrete rather than pedantic.

 clone $original
     Creates a genuinely new object with the same property values. Note it is a
     SHALLOW copy: any property that itself holds an object still points at the
     same nested object. Deep copying requires a __clone() method. See file 46.

 $original == $copy  is false here
     They were equal immediately after cloning, but the clone then had an item
     appended, so their property values now differ. Had we compared before the
     append, == would have been true and === still false.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== 1. SCALARS BY VALUE (the default) ===============
     $a = 10   $b = 20   -> independent

   =============== 2. SCALARS BY REFERENCE (&) ===============
     $c = 20   $d = 20   -> changing $d changed $c
     after unset($d): $c = 20   (the value survives)

   =============== 3. ARRAYS ARE VALUES TOO ===============
     $list1 = [a, b]
     $list2 = [a, b, c]   -> independent, exactly like a scalar
     after $list3 = &$list1 and appending: $list1 = [a, b, z]

   =============== 4. OBJECTS: MODIFY vs REPLACE ===============
     step 1: $cart2 = $cart1
             same object? yes   (spl_object_id: 1 vs 1)
     step 2: $cart2->items[] = "book"
             $cart1->items = [pen, book]   <- changed too
     step 3: $cart2 = new Cart(["eraser"])
             $cart1->items = [pen, book]   <- UNCHANGED
             $cart2->items = [eraser]

   =============== 5. A TRUE OBJECT REFERENCE (&) ===============
     $cart3->items = [replaced]   <- with &, replacing DOES propagate

   =============== 6. GETTING A REAL COPY: clone ===============
     $original->items : [pen, book]
     $shared->items   : [pen, book]
     $copy->items     : [pen, book, added to the clone only]
     $original === $shared : true
     $original === $copy   : false   (different object)
     $original ==  $copy   : false   (same class, but now different contents)

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Default assignment is BY VALUE - including arrays.
  2. & creates a true reference: one storage slot, two names.
  3. unset() breaks a reference without destroying the value.
  4. Objects: assignment copies the HANDLE.
       MODIFY through either name  -> both see it
       REPLACE one name            -> the other is unaffected
     That is why "objects are passed by reference" is WRONG.
  5. clone gives a real (shallow) copy of an object.
  6. For objects, === means "same instance", == means "same class and values".

 INTERVIEW LINK
   "Difference between assigning by value and by reference?"
   "Are objects passed by reference in PHP?"   -> No. Show step 2 vs step 3.
   "How do you get an independent copy of an object?"   -> clone
   "Difference between == and === for objects?"

 TRY THIS
   Add a nested object to Cart (for example a Customer property), clone the
   cart, then change the customer through the clone. The original changes too -
   because clone is SHALLOW. That is the setup for file 46.
================================================================================
*/
