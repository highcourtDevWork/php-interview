<?php

/*
================================================================================
 TOPIC 02 - EXAMPLE 1 : WHAT IS CASE SENSITIVE IN PHP (AND WHAT IS NOT)
================================================================================

 WHAT THIS DEMONSTRATES
   The exact case-sensitivity rules, proved by running them. This is a
   guaranteed written-test question, and getting it wrong causes the classic
   "works on Windows, breaks on the Linux server" bug.

 THE RULE IN ONE LINE
   Anything with a $ sign is CASE SENSITIVE.
   Anything you declare with a keyword (function / class) is NOT.

 HOW TO RUN
   php 01-case-sensitivity.php

================================================================================
*/

declare(strict_types=1);

class UserAccount
{
    public string $userName = 'ravi';

    public function getUserName(): string
    {
        return $this->userName;
    }
}

const MAX_LOGIN_ATTEMPTS = 3;

$account = new UserAccount();

echo '================ CASE INSENSITIVE (all of these WORK) ================' . PHP_EOL;

// --- CLASS NAMES ---
$a = new UserAccount();
$b = new useraccount();          // works - class names ignore case
$c = new USERACCOUNT();          // works
echo 'new UserAccount / useraccount / USERACCOUNT : all created' . PHP_EOL;

// --- FUNCTION NAMES ---
echo 'STRTOUPPER("ok")        : ' . STRTOUPPER('ok') . PHP_EOL;
echo 'StrToUpper("ok")        : ' . StrToUpper('ok') . PHP_EOL;

// --- METHOD NAMES ---
echo 'GETUSERNAME()           : ' . $account->GETUSERNAME() . PHP_EOL;

// --- KEYWORDS AND true/false/null ---
IF (TRUE) {
    echo 'IF / TRUE in capitals   : keywords ignore case' . PHP_EOL;
}
$nothing = NULL;
echo 'NULL in capitals        : ' . var_export($nothing, true) . PHP_EOL;

echo PHP_EOL . '================ CASE SENSITIVE (these FAIL) ================' . PHP_EOL;

// --- VARIABLES ---
$name = 'Ravi';
echo '$name                   : ' . $name . PHP_EOL;
// Reading $Name would emit "Warning: Undefined variable $Name".
// We capture it instead of crashing, so the script keeps running:
echo '$Name                   : ' . (isset($Name) ? $Name : '*** UNDEFINED ***') . PHP_EOL;

// --- OBJECT PROPERTIES ---
echo '$account->userName      : ' . $account->userName . PHP_EOL;
echo '$account->UserName      : '
   . (property_exists($account, 'UserName') ? 'exists' : '*** DOES NOT EXIST ***')
   . PHP_EOL;

// --- ARRAY KEYS ---
$row = ['status' => 'active', 'Email' => 'ravi@example.com'];
echo "isset(\$row['status'])   : " . var_export(isset($row['status']), true) . PHP_EOL;
echo "isset(\$row['Status'])   : " . var_export(isset($row['Status']), true) . PHP_EOL;
echo "isset(\$row['email'])    : " . var_export(isset($row['email']),  true) . PHP_EOL;

// --- CONSTANTS ---
echo 'MAX_LOGIN_ATTEMPTS      : ' . MAX_LOGIN_ATTEMPTS . PHP_EOL;
echo 'max_login_attempts      : '
   . (defined('max_login_attempts') ? 'defined' : '*** NOT DEFINED ***')
   . PHP_EOL;

echo PHP_EOL . '================ THE REAL-WORLD FIX ================' . PHP_EOL;

/**
 * Third-party data (CSV headers, API responses, legacy databases) often has
 * inconsistent key casing. Normalise it ONCE at the boundary instead of
 * guessing the casing at every use site.
 *
 * @param  array<string, mixed> $row
 * @return array<string, mixed>
 */
function normaliseKeys(array $row): array
{
    return array_change_key_case($row, CASE_LOWER);
}

$messy  = ['Name' => 'Ravi', 'EMAIL' => 'ravi@example.com', 'phone_Number' => '99999'];
$clean  = normaliseKeys($messy);

echo 'before : ' . implode(', ', array_keys($messy)) . PHP_EOL;
echo 'after  : ' . implode(', ', array_keys($clean)) . PHP_EOL;
echo 'access : ' . $clean['email'] . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 new useraccount() works
     PHP stores class names in an internal table case-insensitively, so any
     casing resolves to the same class. It WORKS - but never rely on it: it
     hurts readability and it breaks PSR-4 autoloading on Linux, where the FILE
     name must match exactly.

 $account->GETUSERNAME()
     Method names are case insensitive for the same reason. Again: works, never
     do it.

 IF (TRUE) { ... }
     Keywords and the literals true/false/null are case insensitive. Standard
     style is lowercase, and PSR-12 requires lowercase.

 isset($Name) ? $Name : '*** UNDEFINED ***'
     Variables ARE case sensitive, so $Name is a completely different variable
     from $name and does not exist. isset() lets us prove it without the script
     emitting a warning. (?? would work identically here.)

 property_exists($account, 'UserName')
     Properties are case sensitive too. property_exists() checks by exact name,
     which is why it returns false.

 $row['Status'] vs $row['status']
     STRING ARRAY KEYS are compared byte for byte, so these are two different
     keys. This is the case-sensitivity rule that causes the most real bugs -
     typically when reading a CSV whose header row is "Email" but whose code
     expects "email".

 defined('max_login_attempts')
     Constants are case sensitive. define() once accepted a third argument to
     create case-insensitive constants; it was deprecated in 7.3 and REMOVED in
     8.0, so there is no such thing any more.

 array_change_key_case($row, CASE_LOWER)
     Converts all top-level string keys to lower case in one call. CASE_UPPER
     also exists. Note it is NOT recursive - nested arrays keep their original
     keys, so for nested data you need a recursive helper.

================================================================================
 EXPECTED OUTPUT
================================================================================

   ================ CASE INSENSITIVE (all of these WORK) ================
   new UserAccount / useraccount / USERACCOUNT : all created
   STRTOUPPER("ok")        : OK
   StrToUpper("ok")        : OK
   GETUSERNAME()           : ravi
   IF / TRUE in capitals   : keywords ignore case
   NULL in capitals        : NULL

   ================ CASE SENSITIVE (these FAIL) ================
   $name                   : Ravi
   $Name                   : *** UNDEFINED ***
   $account->userName      : ravi
   $account->UserName      : *** DOES NOT EXIST ***
   isset($row['status'])   : true
   isset($row['Status'])   : false
   isset($row['email'])    : false
   MAX_LOGIN_ATTEMPTS      : 3
   max_login_attempts      : *** NOT DEFINED ***

   ================ THE REAL-WORLD FIX ================
   before : Name, EMAIL, phone_Number
   after  : name, email, phone_number
   access : ravi@example.com

================================================================================
 KEY TAKEAWAYS
================================================================================

  CASE SENSITIVE      : variables, object properties, string array keys, constants
  CASE INSENSITIVE    : function names, method names, class names, keywords,
                        true / false / null

  THE PRODUCTION TRAP : class names are case insensitive in PHP, but FILE names
                        are case sensitive on Linux. class User in user.php
                        works on XAMPP and throws "Class not found" in
                        production. Always match the file name to the class name.

 INTERVIEW LINK
   "Which parts of PHP are case sensitive?"
   "Why does my code work locally but fail on the server?"
   "Is $_POST['Email'] the same as $_POST['email']?"   -> No.

 TRY THIS
   Add  $row['STATUS'] ?? 'missing'  and confirm it prints "missing".
   Then write a recursive version of normaliseKeys() that also lowercases the
   keys of nested arrays.
================================================================================
*/
