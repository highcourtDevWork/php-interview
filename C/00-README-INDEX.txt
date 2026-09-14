================================================================================
PHP CORE CONCEPTS - COMPLETE STUDY GUIDE (FOLDER: C)
================================================================================

WHAT THIS FOLDER IS
-------------------
One SUBFOLDER per topic. Each subfolder contains the written notes plus
runnable .php example files with full code explanations.
Pure PHP only (no framework code). Environment: PHP 8.5.4 (XAMPP, Windows).

Source list: ../PHP_CORE_CONCEPTS.txt (the 168 must-know items).
Full syllabus incl. intermediate/senior items: ../PHP_MASTER_SYLLABUS.md


FOLDER LAYOUT
-------------
    C/
    +-- 00-README-INDEX.txt              <- you are here
    +-- 01-how-php-works/
    |     +-- 00-NOTES.txt               <- the full written notes (11 sections)
    |     +-- 01-shared-nothing.php      <- runnable example + explanation
    |     +-- 02-environment.php
    |     +-- 03-lifecycle-shutdown.php
    |     +-- 04-request-profiler.php    <- project-style code
    +-- 02-php-syntax-basics/
    |     +-- 00-NOTES.txt
    |     +-- 01-case-sensitivity.php
    |     +-- 02-output-functions.php
    |     +-- 03-closing-tag-trap.php
    |     +-- 04-template-render.php
    |     +-- helpers.php                <- supporting files used by the examples
    |     +-- config-bad.php  config-good.php
    |     +-- views/user-list.php
    +-- 03-variables-and-scope/  ... and so on for every topic


HOW EACH .php EXAMPLE IS STRUCTURED
-----------------------------------
    /* HEADER  - what it demonstrates, how to run it                        */
    ... runnable code, commented line by line ...
    /* CODE EXPLANATION - every function, argument and concept explained
       EXPECTED OUTPUT  - what you should see (verified by actually running)
       KEY TAKEAWAYS    - the points to remember
       INTERVIEW LINK   - which questions this answers
       TRY THIS         - a small experiment to do yourself                 */

Every example has been executed on PHP 8.5.4 and the documented output is the
real output, not an estimate.


STRUCTURE OF EVERY 00-NOTES.txt FILE
------------------------------------
 1.  Topic heading + introduction (what it is, why it matters)
 2.  Complete explanation (basic -> advanced)
 3.  Real-time examples (how it is used in actual projects)
 4.  Use cases (when to use, when NOT to use)
 5.  Code examples (realistic, best-practice, with expected output)
 6.  Code explanation (line by line / section by section)
 7.  Interview questions (beginner / intermediate / advanced /
     scenario-based / real-time project) with answers and reasoning
 8.  Common mistakes (including security and performance problems)
 9.  Best practices
10.  Important notes / quick revision section
11.  Practical exercises (basic / intermediate / real-time project style)


COMPLETE TOPIC LIST (69 FILES)
------------------------------

GROUP A - FOUNDATIONS
  01-how-php-works.txt .................. Request lifecycle, SAPI, shared-nothing
  02-php-syntax-basics.txt .............. Tags, statements, comments, case rules
  03-variables-and-scope.txt ............ Variables, scope, static variables
  04-references-and-constants.txt ....... & references, copy-on-write, constants

GROUP B - TYPES AND OPERATORS
  05-data-types-overview.txt ............ 8 types, type checking functions
  06-integers-floats-precision.txt ...... Overflow, float precision, bcmath
  07-type-juggling-and-casting.txt ...... Implicit/explicit conversion rules
  08-loose-vs-strict-comparison.txt ..... == vs ===, PHP 8 changes
  09-truthy-isset-empty-null.txt ........ Falsy table, isset/empty/is_null/??
  10-arithmetic-assignment-ops.txt ...... Math, compound assignment, intdiv
  11-logical-precedence-nullsafe.txt .... &&/and traps, precedence, ?->

GROUP C - CONTROL FLOW
  12-if-else-alternative-syntax.txt ..... Conditions, template syntax
  13-switch-vs-match.txt ................ switch, match (8.0), differences
  14-loops-while-do-for.txt ............. Loop types and control
  15-foreach-and-reference-trap.txt ..... foreach modes, the &$value bug
  16-include-require-autoloading.txt .... include/require, exit, PSR-4 autoload

GROUP D - ARRAYS
  17-array-fundamentals.txt ............. Ordered hash map, keys, coercion
  18-array-add-remove-check.txt ......... push/pop/unset, isset vs key_exists
  19-array-map-filter-reduce.txt ........ The functional toolkit
  20-array-merge-slice-column.txt ....... merge vs +, slice, chunk, column
  21-array-sorting.txt .................. Full sort family, usort, key handling
  22-array-real-world-patterns.txt ...... group-by, index-by, flatten, totals

GROUP E - STRINGS AND REGEX
  23-string-basics-and-heredoc.txt ...... Quotes, interpolation, heredoc/nowdoc
  24-string-search-functions.txt ........ strpos, substr, str_contains (8.0)
  25-string-replace-split-join.txt ...... str_replace, explode, implode
  26-string-formatting-comparison.txt ... sprintf, number_format, strcmp
  27-regex-syntax.txt ................... PCRE patterns, quantifiers, groups
  28-regex-preg-functions.txt ........... preg_match, replace, split, callback

GROUP F - FUNCTIONS
  29-function-basics-variadics.txt ...... Params, defaults, ...$args
  30-value-vs-reference-params.txt ...... &$param semantics
  31-type-declarations.txt .............. Param/return types, nullable
  32-named-arguments.txt ................ Named args (8.0)
  33-closures-arrow-callables.txt ....... use(), fn(), callable forms

GROUP G - OBJECT ORIENTED PROGRAMMING
  34-classes-and-objects.txt ............ Class, object, $this, ->, ::
  35-constructors-and-promotion.txt ..... __construct, __destruct, promotion
  36-visibility.txt ..................... public, protected, private
  37-static-and-late-static-binding.txt . static members, self vs static
  38-constants-typed-readonly.txt ....... Class constants, typed props, readonly
  39-encapsulation-abstraction.txt ...... Two of the four pillars
  40-inheritance.txt .................... extends, overriding, parent::
  41-polymorphism.txt ................... Polymorphism, no method overloading
  42-abstract-vs-interface.txt .......... The most-asked OOP question
  43-traits.txt ......................... Horizontal reuse, conflict resolution
  44-enums.txt .......................... Pure/backed enums (8.1)
  45-magic-methods.txt .................. __get, __set, __call, __toString, etc
  46-object-semantics-cloning.txt ....... Handles, == vs ===, shallow vs deep

GROUP H - ERRORS AND EXCEPTIONS
  47-error-levels-throwable.txt ......... E_* levels, Error vs Exception
  48-try-catch-custom-handlers.txt ...... finally, custom exceptions, handlers

GROUP I - WEB AND HTTP
  49-http-lifecycle-superglobals.txt .... Request flow, $_GET/$_POST/$_SERVER
  50-headers-status-output-buffer.txt ... header(), redirects, ob_*
  51-cookies-and-sessions.txt ........... setcookie, session_start, security
  52-file-uploads.txt ................... $_FILES, validation, move_uploaded_file

GROUP J - DATABASE
  53-pdo-fundamentals.txt ............... DSN, options, PDO vs MySQLi
  54-prepared-statements-fetch.txt ...... prepare/execute, fetch modes
  55-transactions.txt ................... ACID, commit/rollback, savepoints

GROUP K - SECURITY
  56-sql-injection-and-xss.txt .......... Attack + defence, escaping contexts
  57-csrf-and-session-security.txt ...... Tokens, SameSite, fixation
  58-hashing-randomness-injection.txt ... password_hash, random_bytes, LFI/RFI

GROUP L - FILES AND TIME
  59-filesystem-operations.txt .......... fopen, file_get_contents, directories
  60-date-and-time.txt .................. DateTime, DateTimeImmutable, timezones

GROUP M - ARCHITECTURE
  61-design-patterns.txt ................ Singleton, Factory, Strategy, etc
  62-solid-principles.txt ............... SOLID with PHP examples
  63-mvc-architecture.txt ............... Layer responsibilities

GROUP N - TOOLING
  64-composer.txt ....................... composer.json/lock, semver, autoload
  65-psr-standards.txt .................. PSR-1/3/4/7/11/12/15
  66-phpunit-testing.txt ................ Test structure, mocks, data providers

GROUP O - PERFORMANCE AND PRACTICE
  67-performance-and-caching.txt ........ OPcache, cache layers, profiling
  68-algorithms-in-php.txt .............. Coding round problems
  69-practical-build-tasks.txt .......... CRUD, auth, pagination, REST API


HOW TO USE THIS FOLDER
----------------------
1. Open the topic folder. Read 00-NOTES.txt fully. Do not skim.
2. RUN each .php example from the command line:
       cd C/01-how-php-works
       php 01-shared-nothing.php
   Read the code, then the CODE EXPLANATION block underneath it.
3. Do the "TRY THIS" experiment at the bottom of each example. Breaking the
   code on purpose teaches more than reading it.
4. Answer the interview questions in 00-NOTES.txt out loud, without looking.
5. Do the 3 exercises at the end of 00-NOTES.txt.
6. Before an interview, re-read only section 10 (Important Notes) of each
   00-NOTES.txt.

Some examples are also worth opening in the BROWSER (they say so in their
header), because they behave differently there:
    http://localhost/php_learning_interview_prep/C/01-how-php-works/01-shared-nothing.php

PROGRESS: 5 of 69 topics complete (01-05). The rest follow the same structure.

SUGGESTED ORDER
---------------
Files are numbered in learning order. Follow 01 -> 69.
If you are short on time, prioritise these groups in this order:
    G (OOP)  ->  D (Arrays)  ->  K (Security)  ->  J (Database)
    ->  I (Web)  ->  H (Errors)  ->  B (Types)  ->  everything else

================================================================================
