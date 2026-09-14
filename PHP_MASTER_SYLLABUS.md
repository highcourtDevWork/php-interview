# PHP Interview Prep — Master Concept List

Single source of truth. Every concept you'd be asked about, from fundamentals to senior-level.
Checkboxes so you can segregate into daily/weekly batches later.

**Legend:** `[C]` core must-know · `[I]` intermediate · `[S]` senior/architecture · `8.x` = version-specific feature
**Environment:** PHP 8.5.4 (XAMPP, Windows)

---

## TIER 0 — Language Setup & Runtime Model

- [ ] `[C]` How PHP executes: request → SAPI (CLI / Apache mod_php / PHP-FPM) → Zend Engine → opcodes → execute → teardown
- [ ] `[C]` **Shared-nothing architecture** — every request starts with a clean memory state (the #1 conceptual difference from Node/Java)
- [ ] `[C]` `php.ini` — locating it (`php --ini`), key directives: `display_errors`, `error_reporting`, `memory_limit`, `max_execution_time`, `upload_max_filesize`, `post_max_size`, `date.timezone`
- [ ] `[I]` OPcache — what it caches, why it matters, `opcache.validate_timestamps`, preloading (7.4+), JIT (8.0+) and when JIT actually helps (rarely for typical web work)
- [ ] `[I]` CLI vs web SAPI differences (no timeout in CLI, `$argv`, STDIN/STDOUT/STDERR, exit codes)
- [ ] `[C]` PHP tags `<?php ?>`, short echo `<?= ?>`, why you omit the closing tag in pure-PHP files
- [ ] `[C]` Comments, statement termination, case sensitivity rules (variables = sensitive, functions/classes = insensitive)
- [ ] `[I]` PHP version history and what landed when (5.4 traits/short array, 5.6 variadics, 7.0 return types/spaceship, 7.4 arrow fns/typed props, 8.0 union types/attributes/named args, 8.1 enums/readonly/fibers, 8.2 readonly classes/DNF, 8.3 typed constants, 8.4 property hooks/asymmetric visibility)

---

## TIER 1 — Variables, Types & Operators

### Variables
- [ ] `[C]` Declaration, `$` sigil, naming rules, dynamic/variable variables `$$name`
- [ ] `[C]` Scope: local, global, static, function parameters — and why PHP has **no block scope**
- [ ] `[C]` `global` keyword vs `$GLOBALS` superglobal (and why both are code smells)
- [ ] `[C]` `static` variables inside functions (persist across calls in same request)
- [ ] `[C]` **Assignment by value vs by reference** (`&`) — the single most common gotcha
- [ ] `[I]` Reference semantics with arrays: the classic `foreach ($arr as &$v)` dangling-reference bug
- [ ] `[I]` Copy-on-write (COW) and refcounting — why passing PHP arrays is cheap
- [ ] `[C]` Constants: `define()` vs `const` — differences (scope, arrays, conditional definition, case sensitivity)
- [ ] `[I]` Magic constants: `__LINE__`, `__FILE__`, `__DIR__`, `__FUNCTION__`, `__CLASS__`, `__METHOD__`, `__NAMESPACE__`, `ClassName::class`

### Data Types
- [ ] `[C]` Scalars: `int`, `float`, `string`, `bool`
- [ ] `[C]` Compound: `array`, `object`, `callable`, `iterable`
- [ ] `[C]` Special: `null`, `resource` (legacy — progressively replaced by objects)
- [ ] `[C]` `gettype()`, `get_debug_type()` (8.0+, prefer it), `var_dump()`, `is_*()` family
- [ ] `[C]` Integer overflow → float, `PHP_INT_MAX`, `PHP_INT_SIZE`
- [ ] `[C]` **Float precision** — `0.1 + 0.2 !== 0.3`, `PHP_FLOAT_EPSILON`, when to use `bcmath`/`gmp` (money!)
- [ ] `[C]` Type juggling rules and **PHP 8's saner string↔number comparison** (`0 == "foo"` was `true` pre-8, now `false`)
- [ ] `[C]` Loose `==` vs strict `===` — the comparison table you must memorize
- [ ] `[C]` Truthy/falsy table: `0`, `0.0`, `""`, `"0"`, `[]`, `null`, `false` are falsy — `"0.0"`, `" "`, `"false"`, `[0]` are truthy
- [ ] `[C]` Explicit casting `(int)`, `(float)`, `(string)`, `(bool)`, `(array)`, `(object)`
- [ ] `[I]` `settype()` vs casting, `intval()` with base, `floatval()`, `strval()`
- [ ] `[I]` `declare(strict_types=1)` — what it changes, why it's per-file, why you should always use it

### Operators
- [ ] `[C]` Arithmetic, `**` exponent, `intdiv()`, modulo behavior with negatives, `fdiv()`
- [ ] `[C]` Assignment + compound (`+=`, `.=`, `??=`)
- [ ] `[C]` Comparison, `<=>` spaceship (7.0+) and its use in `usort`
- [ ] `[C]` Logical `&&`/`||` vs `and`/`or` — **precedence trap** with `=`
- [ ] `[C]` Null coalescing `??` vs ternary `?:` vs `isset()` vs `empty()` — know the exact differences
- [ ] `[C]` Nullsafe operator `?->` (8.0+) and its limits (doesn't apply to array access)
- [ ] `[I]` String concatenation `.` and why `+` doesn't concatenate
- [ ] `[I]` Bitwise operators `& | ^ ~ << >>` — flags/permissions use case
- [ ] `[I]` Error control `@` — why it's harmful, what it doesn't suppress
- [ ] `[I]` Operator precedence & associativity table; the `.` vs `+` precedence change in PHP 8
- [ ] `[I]` `instanceof`, `clone`, `new`, `print` as expressions

---

## TIER 2 — Control Structures

- [ ] `[C]` `if` / `elseif` / `else`, alternative syntax (`if: endif;`) for templates
- [ ] `[C]` `switch` — loose comparison, fallthrough, `default`
- [ ] `[C]` **`match` expression (8.0+)** — strict comparison, returns a value, exhaustive (throws `UnhandledMatchError`), no fallthrough. Classic "match vs switch" question
- [ ] `[C]` `while`, `do-while`, `for`, `foreach` (by value, by reference, with key, with destructuring)
- [ ] `[C]` `break N` / `continue N`
- [ ] `[C]` `goto` (know it exists, never use it)
- [ ] `[C]` `include` vs `require` vs `*_once` — failure behavior, return values, performance
- [ ] `[I]` `return` from an included file
- [ ] `[C]` `exit` / `die`, exit codes, `register_shutdown_function()`

---

## TIER 3 — Arrays (Huge Interview Topic)

- [ ] `[C]` PHP arrays are **ordered hash maps** — not real arrays. Understand the implication
- [ ] `[C]` Indexed, associative, multidimensional; short syntax `[]`
- [ ] `[C]` Key coercion rules: `"1"` → `1`, `1.7` → `1`, `true` → `1`, `null` → `""`
- [ ] `[C]` Adding/removing: `[]=`, `array_push/pop/shift/unshift`, `unset()` (and why it doesn't reindex)
- [ ] `[C]` `array_values()`, `array_keys()`, `array_key_exists()` vs `isset()` (the null-value difference), `in_array()` strict flag
- [ ] `[C]` **Sorting family**: `sort`, `rsort`, `asort`, `arsort`, `ksort`, `krsort`, `usort`, `uasort`, `uksort`, `natsort`, `array_multisort` — know which preserve keys
- [ ] `[C]` **Functional trio**: `array_map`, `array_filter` (with `ARRAY_FILTER_USE_KEY` / `USE_BOTH`), `array_reduce` — and their key-preservation quirks
- [ ] `[C]` `array_merge` vs the `+` operator — different behavior for numeric keys
- [ ] `[C]` `array_combine`, `array_flip`, `array_fill`, `array_fill_keys`, `range()`
- [ ] `[C]` `array_slice` (preserve_keys), `array_splice`, `array_chunk`, `array_column`, `array_pad`
- [ ] `[C]` Set ops: `array_unique`, `array_diff`, `array_diff_key`, `array_diff_assoc`, `array_intersect` + key/assoc variants
- [ ] `[C]` `array_search`, `count()` recursive mode, `array_sum`, `array_product`, `min`, `max`
- [ ] `[I]` `list()` / `[]` destructuring, nested destructuring, with keys, inside `foreach`
- [ ] `[I]` Spread `...` in arrays (7.4+), **string-keyed spread (8.1+)**
- [ ] `[I]` `array_walk` vs `array_map` (by-ref, return value)
- [ ] `[I]` `compact()` / `extract()` (and why `extract()` is dangerous)
- [ ] `[I]` Internal pointer: `current`, `next`, `reset`, `end`, `key` (`each` was removed in 8.0)
- [ ] `[I]` `array_is_list()` (8.1+)
- [ ] `[S]` Memory cost of large arrays; when to switch to `SplFixedArray`, generators, or streaming
- [ ] `[C]` **Practice**: group-by, index-by-id, flatten nested, sum by category, find duplicates, sort objects by property — write these without docs

---

## TIER 4 — Strings

- [ ] `[C]` Single vs double quotes — interpolation, escape sequences, the performance myth
- [ ] `[C]` Heredoc `<<<EOT` and Nowdoc `<<<'EOT'`; 7.3 flexible indentation
- [ ] `[C]` Interpolation forms: `"$var"`, `"{$obj->prop}"`, `"{$arr['key']}"`, and the deprecated `"${var}"` (8.2)
- [ ] `[C]` Core functions: `strlen`, `strtolower/upper`, `ucfirst`, `ucwords`, `trim/ltrim/rtrim`, `str_pad`, `str_repeat`, `strrev`
- [ ] `[C]` Search: `strpos`, `strrpos`, `stripos`, `strstr`, `substr`, `substr_count`
- [ ] `[C]` **8.0+ `str_contains`, `str_starts_with`, `str_ends_with`** — very common interview mention
- [ ] `[C]` Replace: `str_replace`, `str_ireplace`, `substr_replace`, `strtr`
- [ ] `[C]` Split/join: `explode` (with limit, negative limit), `implode`, `str_split`, `preg_split`, `wordwrap`, `nl2br`
- [ ] `[C]` `sprintf` / `printf` / `vsprintf` format specifiers, `number_format`
- [ ] `[C]` Comparison: `strcmp`, `strcasecmp`, `strncmp`, `strnatcmp`, `hash_equals()` for secrets (timing-safe)
- [ ] `[I]` **Multibyte**: why `strlen("é") === 2`, the whole `mb_*` family, `mb_internal_encoding`, UTF-8 handling
- [ ] `[I]` `htmlspecialchars` vs `htmlentities` vs `strip_tags` — XSS context
- [ ] `[I]` `json_encode` / `json_decode` — assoc flag, depth, `JSON_THROW_ON_ERROR`, `JSON_UNESCAPED_UNICODE`, `json_last_error`
- [ ] `[I]` Serialization: `serialize`/`unserialize` (and the RCE risk), `var_export`, `__serialize`/`__unserialize`
- [ ] `[I]` Encoding: `base64_encode/decode`, `urlencode` vs `rawurlencode`, `http_build_query`, `parse_str`, `parse_url`

### Regular Expressions
- [ ] `[C]` PCRE syntax: delimiters, anchors, character classes, quantifiers (greedy vs lazy), groups, alternation
- [ ] `[C]` `preg_match`, `preg_match_all` (with `PREG_SET_ORDER` / `PREG_PATTERN_ORDER`), `preg_replace`, `preg_replace_callback`, `preg_split`, `preg_quote`
- [ ] `[I]` Named capture groups, non-capturing groups, lookahead/lookbehind
- [ ] `[I]` Modifiers: `i`, `m`, `s`, `x`, `u` (UTF-8 — always needed for unicode)
- [ ] `[S]` Catastrophic backtracking / ReDoS, `pcre.backtrack_limit`

---

## TIER 5 — Functions

- [ ] `[C]` Declaration, parameters, return values, default args (must be trailing)
- [ ] `[C]` Pass by value vs **pass by reference** `&$param`
- [ ] `[C]` Variadic `...$args`, `func_get_args()`, argument unpacking at the call site
- [ ] `[C]` **Type declarations**: scalar, class, `array`, `callable`, `iterable`, `self`, `static`, nullable `?T`
- [ ] `[C]` Return types incl. `void`, `never` (8.1), `static` (8.0), `mixed` (8.0)
- [ ] `[C]` **Union types `int|string`** (8.0), **intersection types `A&B`** (8.1), **DNF types `(A&B)|null`** (8.2)
- [ ] `[C]` **Named arguments** (8.0) — skipping optionals, combining with positional
- [ ] `[C]` Anonymous functions (closures), `use` by value vs by reference
- [ ] `[C]` **Arrow functions `fn()` (7.4)** — implicit by-value capture, single expression only. Closure vs arrow fn = frequent question
- [ ] `[I]` `Closure::bind`, `bindTo`, `call`, `Closure::fromCallable`, first-class callable syntax `strlen(...)` (8.1)
- [ ] `[I]` Callables: string name, `[$obj,'method']`, `[Class::class,'staticMethod']`, `__invoke`
- [ ] `[I]` Recursion, absence of tail-call optimization, stack depth limits
- [ ] `[I]` **Generators**: `yield`, `yield from`, `yield $k => $v`, `send()`, `getReturn()` — memory-efficient iteration
- [ ] `[S]` Fibers (8.1) — what problem they solve, relation to async (Amp/ReactPHP/Swoole)
- [ ] `[I]` Nullable vs optional vs default — three different concepts
- [ ] `[I]` Function composition, currying, higher-order functions, partial application

---

## TIER 6 — OOP (The Interview Core)

### Fundamentals
- [ ] `[C]` Class, object, `new`, `$this`, `->`, `::`
- [ ] `[C]` Properties, methods, constructor `__construct`, destructor `__destruct`
- [ ] `[C]` **Visibility**: `public`, `protected`, `private` — and what each means for inheritance
- [ ] `[C]` `static` properties/methods, `self::` vs `static::` (**late static binding**) vs `parent::`
- [ ] `[C]` Class constants, `final` constants, **typed constants (8.3)**
- [ ] `[C]` **Typed properties (7.4)**, the uninitialized state, `readonly` properties (8.1), **readonly classes (8.2)**
- [ ] `[C]` **Constructor property promotion (8.0)** — big boilerplate reduction
- [ ] `[C]` **Asymmetric visibility (8.4)** — `public private(set) string $name`
- [ ] `[I]` **Property hooks (8.4)** — `get`/`set` hooks, computed properties
- [ ] `[C]` `$this` binding; static context has no `$this`

### The Four Pillars (expect to be asked verbatim)
- [ ] `[C]` **Encapsulation** — visibility, getters/setters, invariants
- [ ] `[C]` **Inheritance** — `extends`, single inheritance, method overriding, `parent::__construct()`
- [ ] `[C]` **Polymorphism** — interface-based, method overriding; PHP has **no method overloading** (explain why + the `__call` workaround)
- [ ] `[C]` **Abstraction** — `abstract` classes and methods

### Structures
- [ ] `[C]` **Abstract class vs Interface** — when to use each (the most-asked OOP question)
- [ ] `[C]` Interfaces: multiple implementation, constants, `extends` on multiple interfaces
- [ ] `[C]` **Traits** — horizontal reuse, conflict resolution (`insteadof`, `as`), abstract/static members, properties, **constants in traits (8.2)**
- [ ] `[C]` Trait vs Interface vs Abstract class — the comparison table
- [ ] `[C]` `final` classes and methods — why "prefer composition over inheritance"
- [ ] `[C]` **Enums (8.1)** — pure vs backed, `cases()`, `from()`, `tryFrom()`, methods, interfaces, constants
- [ ] `[I]` Anonymous classes (7.0)
- [ ] `[I]` Nested/inner classes — PHP doesn't have them (know the answer)
- [ ] `[I]` Static factory methods, named constructors

### Magic Methods
- [ ] `[C]` `__construct`, `__destruct`
- [ ] `[C]` `__get`, `__set`, `__isset`, `__unset` — property overloading
- [ ] `[C]` `__call`, `__callStatic` — method overloading simulation
- [ ] `[C]` `__toString`, `__invoke`
- [ ] `[I]` `__clone` and **shallow vs deep copy** (very common question)
- [ ] `[I]` `__sleep`/`__wakeup`, `__serialize`/`__unserialize` (7.4+)
- [ ] `[I]` `__set_state`, `__debugInfo`
- [ ] `[S]` Performance cost of magic methods, and why they break static analysis

### Object Semantics
- [ ] `[C]` **Objects are assigned by handle, not by reference** — explain the difference precisely
- [ ] `[C]` `==` vs `===` for objects (same class + equal props vs same instance)
- [ ] `[I]` `clone`, deep-clone implementation, `SplObjectStorage`
- [ ] `[I]` Object iteration (`foreach` over public props), `Iterator`, `IteratorAggregate`
- [ ] `[I]` `instanceof`, `get_class`, `get_parent_class`, `class_exists`, `method_exists`, `property_exists`, `is_a`, `is_subclass_of`

### SPL Interfaces & Structures (know these by name)
- [ ] `[I]` `Countable`, `ArrayAccess`, `Iterator`, `IteratorAggregate`, `Traversable`, `Stringable` (8.0), `JsonSerializable`, `Serializable` (deprecated)
- [ ] `[I]` SPL data structures: `SplStack`, `SplQueue`, `SplObjectStorage`, `SplFixedArray`, `SplHeap`, `SplPriorityQueue`, `ArrayObject`, `ArrayIterator`
- [ ] `[I]` SPL iterators: `RecursiveIteratorIterator`, `RecursiveDirectoryIterator`, `LimitIterator`, `CallbackFilterIterator`, `Generator` as an iterator

### Advanced OOP
- [ ] `[I]` **Namespaces** — declaration, `use`, aliasing, group use, fully-qualified `\`, function/const import, global fallback
- [ ] `[C]` **Autoloading** — `spl_autoload_register`, **PSR-4**, Composer's autoloader, classmap vs PSR-4, `composer dump-autoload -o`
- [ ] `[I]` **Attributes (8.0)** `#[Attr]` — declaration, targets, `ReflectionAttribute`, real use (routing, validation, ORM mapping)
- [ ] `[I]` **Reflection API** — `ReflectionClass`, `ReflectionMethod`, `ReflectionProperty`, `ReflectionNamedType`, use in DI containers, runtime cost
- [ ] `[S]` `WeakReference` (7.4), `WeakMap` (8.0) — caches/observers without leaks
- [ ] `[S]` Object lifecycle, garbage collection, cyclic reference collector, `gc_collect_cycles()`

---

## TIER 7 — Errors & Exceptions

- [ ] `[C]` Error levels: `E_NOTICE`, `E_WARNING`, `E_DEPRECATED`, `E_ERROR`, `E_USER_*`, `E_ALL`
- [ ] `[C]` **PHP 7+ `Throwable` hierarchy**: `Throwable` → `Error` (`TypeError`, `ValueError`, `ArgumentCountError`, `ArithmeticError`, `DivisionByZeroError`, `AssertionError`) and `Exception` (`RuntimeException`, `LogicException`, `InvalidArgumentException`, `OutOfBoundsException`, `JsonException`, `PDOException`, …)
- [ ] `[C]` `try` / `catch` / `finally` — execution order, `finally` overriding a return
- [ ] `[C]` Multi-catch `catch (A|B $e)` (7.1), **non-capturing catch `catch (Exception)`** (8.0)
- [ ] `[C]` Custom exception classes, exception chaining (`$previous`), `getMessage/getCode/getFile/getLine/getTrace/getTraceAsString/getPrevious`
- [ ] `[C]` `set_error_handler`, `set_exception_handler`, `register_shutdown_function`, converting errors → `ErrorException`
- [ ] `[C]` PHP 8 changes: many warnings promoted to `Error`; undefined array key / undefined variable severity changes
- [ ] `[I]` `error_log()`, logging strategy, PSR-3 `LoggerInterface`, Monolog
- [ ] `[I]` Exceptions vs error codes vs null returns vs Result objects — design discussion
- [ ] `[S]` When NOT to catch; fail-fast; don't use exceptions for flow control
- [ ] `[I]` `assert()` and `zend.assertions`

---

## TIER 8 — Web / HTTP Layer

- [ ] `[C]` Request lifecycle end-to-end: browser → web server → PHP-FPM → script → response
- [ ] `[C]` **Superglobals**: `$_GET`, `$_POST`, `$_REQUEST` (avoid), `$_SERVER`, `$_FILES`, `$_COOKIE`, `$_SESSION`, `$_ENV`, `$GLOBALS`
- [ ] `[C]` Important `$_SERVER` keys: `REQUEST_METHOD`, `REQUEST_URI`, `HTTP_HOST`, `REMOTE_ADDR`, `HTTP_X_FORWARDED_FOR` (and why it's untrusted), `CONTENT_TYPE`
- [ ] `[C]` GET vs POST vs PUT/PATCH/DELETE; idempotency; when PHP does NOT populate `$_POST` (JSON body → `php://input`)
- [ ] `[C]` `header()`, status codes, `http_response_code()`, redirects (301 vs 302 vs 307), the "headers already sent" error
- [ ] `[C]` **Cookies**: `setcookie()`, the options array, `HttpOnly`, `Secure`, `SameSite`, `Domain`, `Path`, expiry
- [ ] `[C]` **Sessions**: `session_start()`, `$_SESSION`, session ID, storage handlers (files/redis/db), `session_regenerate_id()` (fixation defense), `session_destroy()`, GC, session locking & the blocking-request problem
- [ ] `[C]` **File uploads**: `enctype`, `$_FILES` structure, error codes, `is_uploaded_file`, `move_uploaded_file`, MIME validation (never trust `type`), size limits, multi-file uploads
- [ ] `[C]` Output buffering: `ob_start`, `ob_get_clean`, `ob_end_flush` — why it matters for headers and templating
- [ ] `[I]` Input filtering: `filter_var`, `filter_input`, validation filters (`FILTER_VALIDATE_EMAIL/INT/URL/IP`), sanitize filters
- [ ] `[I]` `php://input`, `php://memory`, `php://temp`, stream wrappers, `stream_context_create`
- [ ] `[I]` cURL: GET/POST, headers, timeouts, SSL verification (never disable), `curl_multi_*`
- [ ] `[I]` PSR-7 (HTTP messages), PSR-15 (middleware), PSR-17 (factories), PSR-18 (client) — the modern stack
- [ ] `[I]` CORS handling, preflight requests
- [ ] `[I]` Content negotiation, `Accept` headers, JSON APIs, correct `Content-Type`
- [ ] `[S]` Long-running processes, queue workers, why "shared nothing" assumptions break for daemons (memory leaks, stale state)

---

## TIER 9 — Databases

- [ ] `[C]` **PDO vs MySQLi** — differences, why PDO (many drivers, named params, consistent API)
- [ ] `[C]` PDO connection: DSN, options array, **`ERRMODE_EXCEPTION`**, `ATTR_EMULATE_PREPARES => false`, `ATTR_DEFAULT_FETCH_MODE`, charset in the DSN
- [ ] `[C]` **Prepared statements** — how they prevent SQL injection at the protocol level; emulated vs native prepares
- [ ] `[C]` `prepare/execute/bindParam/bindValue` (by-ref vs by-value difference), positional `?` vs named `:name`
- [ ] `[C]` Fetch modes: `FETCH_ASSOC`, `FETCH_OBJ`, `FETCH_CLASS`, `FETCH_COLUMN`, `FETCH_KEY_PAIR`, `FETCH_GROUP`; `fetchAll` vs iterating
- [ ] `[C]` `lastInsertId()`, `rowCount()` caveats
- [ ] `[C]` **Transactions**: `beginTransaction/commit/rollBack`, ACID, the nested-transaction problem + savepoints
- [ ] `[C]` **SQL injection** — vectors, why escaping isn't enough, dynamic identifiers (table/column names) needing whitelists, `LIMIT` with emulated prepares
- [ ] `[I]` SQL you must know: JOINs (INNER/LEFT/RIGHT/FULL/SELF/CROSS), `GROUP BY` + `HAVING`, subqueries, `UNION`, window functions, CTEs
- [ ] `[I]` Indexes: B-tree, composite index column order, covering index, reading `EXPLAIN`, when indexes hurt
- [ ] `[I]` **N+1 query problem** — how to detect and fix (eager loading, `IN` batching)
- [ ] `[I]` Normalization (1NF–3NF) vs denormalization tradeoffs
- [ ] `[I]` MySQL data types, `utf8` vs `utf8mb4`, collations, storage engines (InnoDB vs MyISAM)
- [ ] `[I]` No connection pooling in PHP; persistent connections `ATTR_PERSISTENT` and their dangers
- [ ] `[I]` ORMs: Active Record (Eloquent) vs Data Mapper (Doctrine) — tradeoffs, identity map, unit of work, lazy loading
- [ ] `[I]` Migrations, seeding, schema versioning
- [ ] `[S]` Optimistic vs pessimistic locking, `SELECT ... FOR UPDATE`, isolation levels, deadlocks
- [ ] `[S]` Read replicas, sharding, query caching, the slow query log
- [ ] `[I]` NoSQL touchpoints: Redis (caching, sessions, queues, rate limiting), MongoDB basics

---

## TIER 10 — Security (Expect Deep Questions)

- [ ] `[C]` **SQL Injection** — prepared statements, whitelisting identifiers
- [ ] `[C]` **XSS** — stored/reflected/DOM; `htmlspecialchars($s, ENT_QUOTES, 'UTF-8')`; context-aware escaping (HTML body vs attribute vs JS vs URL vs CSS); CSP headers
- [ ] `[C]` **CSRF** — synchronizer token pattern, `SameSite` cookies, double-submit, why GET must be safe
- [ ] `[C]` **Password hashing** — `password_hash()` (bcrypt/argon2id), `password_verify()`, `password_needs_rehash()`, cost factor, **never MD5/SHA1**, salting is automatic
- [ ] `[C]` Session security: regeneration on privilege change, `session.cookie_httponly`, `cookie_secure`, `use_strict_mode`, fixation vs hijacking
- [ ] `[C]` **File upload security** — extension whitelist, MIME re-check, store outside webroot, randomize names, never `include` an upload
- [ ] `[C]` **File inclusion** — LFI/RFI, `allow_url_include`, path traversal, `realpath()` + basedir checks
- [ ] `[C]` **Command injection** — `escapeshellarg`, `escapeshellcmd`, prefer `proc_open` with array args
- [ ] `[C]` Insecure deserialization — `unserialize()` gadget chains; use JSON instead
- [ ] `[C]` Randomness: `random_bytes()`, `random_int()`, `bin2hex()` — **never `rand()`/`mt_rand()`/`uniqid()` for security**
- [ ] `[I]` Timing attacks — `hash_equals()`
- [ ] `[I]` Security headers: CSP, HSTS, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`
- [ ] `[I]` Authentication vs authorization; RBAC/ABAC; JWT (signing, the `alg:none` attack, storage, refresh tokens) vs session cookies
- [ ] `[I]` OAuth2 / OpenID Connect flows (authorization code + PKCE)
- [ ] `[I]` Rate limiting, brute-force protection, account enumeration
- [ ] `[I]` HTTPS/TLS, mixed content, certificate verification in cURL
- [ ] `[I]` Secrets management — `.env`, never commit, `getenv()` vs `$_ENV`
- [ ] `[I]` **OWASP Top 10** — be able to name and mitigate each
- [ ] `[S]` Mass assignment, IDOR, SSRF, XXE, open redirects

---

## TIER 11 — Filesystem, Dates, Misc Builtins

### Files
- [ ] `[C]` `fopen` modes, `fread`, `fwrite`, `fgets`, `fclose`, `feof`, `fseek`
- [ ] `[C]` `file_get_contents`, `file_put_contents` (with `FILE_APPEND`, `LOCK_EX`), `file()`, `readfile()`
- [ ] `[C]` `file_exists`, `is_file`, `is_dir`, `is_readable/writable`, `filesize`, `filemtime`, `unlink`, `rename`, `copy`
- [ ] `[C]` Directories: `mkdir` (recursive), `rmdir`, `scandir`, `glob`, `opendir/readdir`, `DirectoryIterator`, `RecursiveDirectoryIterator`
- [ ] `[C]` Paths: `basename`, `dirname`, `pathinfo`, `realpath`, `__DIR__`, `DIRECTORY_SEPARATOR`
- [ ] `[I]` CSV: `fgetcsv`, `fputcsv`, streaming large files
- [ ] `[I]` `flock()` for concurrency, atomic writes via temp file + rename
- [ ] `[I]` `tmpfile`, `sys_get_temp_dir`, `tempnam`
- [ ] `[I]` Streaming large files without exhausting memory (generators + `fgets`)

### Date & Time
- [ ] `[C]` `date()`, `time()`, `mktime()`, `strtotime()` and its parsing quirks
- [ ] `[C]` **`DateTime` / `DateTimeImmutable`** — prefer Immutable; `modify`, `add`, `sub`, `diff`, `format`
- [ ] `[C]` `DateInterval`, `DatePeriod`, `DateTimeZone`
- [ ] `[C]` Timezones, the UTC-storage convention, DST pitfalls
- [ ] `[I]` Unix timestamps, Y2038, `microtime(true)`, `hrtime()` for benchmarking
- [ ] `[I]` Format-character cheat sheet, `DateTime::createFromFormat`, ISO 8601 / `DATE_ATOM`

### Math & Misc
- [ ] `[I]` `round` (modes), `floor`, `ceil`, `abs`, `pow`, `sqrt`, `number_format`, `bcmath` for money
- [ ] `[I]` `hash()`, `md5`/`sha1` (non-security only), `crc32`, `hash_hmac`
- [ ] `[I]` `uniqid` vs UUID (ramsey/uuid), ULID
- [ ] `[I]` `intdiv`, `fmod`, `is_nan`, `is_finite`, `INF`, `NAN`

---

## TIER 12 — Design Patterns & Architecture

### Creational
- [ ] `[C]` Singleton (and why it's an anti-pattern for testing)
- [ ] `[C]` Factory Method, Abstract Factory, Static Factory
- [ ] `[I]` Builder, Prototype (`clone`), Object Pool

### Structural
- [ ] `[C]` Adapter, Decorator, Facade, Proxy (lazy loading)
- [ ] `[I]` Composite, Bridge, Flyweight

### Behavioral
- [ ] `[C]` Strategy, Observer (event dispatcher), Template Method
- [ ] `[I]` Command, Chain of Responsibility (middleware!), State, Iterator, Mediator, Visitor, Memento, Null Object

### Architectural
- [ ] `[C]` **MVC** — precise responsibilities of each layer, fat model / thin controller
- [ ] `[I]` **Dependency Injection** vs Service Locator; constructor vs setter injection; **IoC container**, autowiring, PSR-11
- [ ] `[C]` **SOLID** — be able to give a PHP example of each violation and its fix
- [ ] `[C]` DRY, KISS, YAGNI, Law of Demeter, composition over inheritance
- [ ] `[I]` Repository pattern, Service layer, DTOs, Value Objects, Entities
- [ ] `[I]` **Front Controller**, routing, middleware pipeline
- [ ] `[S]` Domain-Driven Design basics: aggregates, bounded contexts, ubiquitous language
- [ ] `[S]` Hexagonal / Ports & Adapters, Clean Architecture, CQRS, Event Sourcing
- [ ] `[S]` Monolith vs microservices in PHP; message queues (RabbitMQ, Redis, SQS)
- [ ] `[S]` Event-driven design, domain events, outbox pattern

---

## TIER 13 — Tooling & Ecosystem

- [ ] `[C]` **Composer** — `composer.json` vs `composer.lock`, `require` vs `require-dev`, semver constraints (`^`, `~`, `>=`), `install` vs `update`, autoload sections (`psr-4`, `files`, `classmap`), scripts, platform requirements
- [ ] `[C]` **PSR standards**: PSR-1/PSR-12 (style), PSR-4 (autoload), PSR-3 (logging), PSR-7/15/17/18 (HTTP), PSR-11 (container), PSR-6/16 (caching), PSR-14 (events)
- [ ] `[C]` **PHPUnit** — test structure, assertions, `setUp`/`tearDown`, data providers, mocks/stubs/spies, `expectException`, coverage
- [ ] `[I]` TDD cycle, AAA pattern, unit vs integration vs functional vs E2E, test doubles, testing against a DB (transactions / in-memory SQLite)
- [ ] `[I]` Static analysis: **PHPStan** / **Psalm** (levels, baselines), **PHP CS Fixer** / PHP_CodeSniffer, Rector for upgrades
- [ ] `[I]` Xdebug — breakpoints, step debugging, profiling; and why it must be off in production
- [ ] `[I]` Pest (modern testing), Faker, Mockery
- [ ] `[I]` Git workflow, branching, PR review basics (they will ask)
- [ ] `[I]` Docker for PHP: FPM image, multi-stage builds, `docker-compose` with MySQL/Redis
- [ ] `[I]` CI/CD pipeline shape: lint → static analysis → test → build → deploy
- [ ] `[I]` Deployment: atomic/symlink deploys, zero-downtime, opcache reset, migrations during deploy

---

## TIER 14 — Frameworks (Know At Least One Deeply)

### Laravel (most commonly asked)
- [ ] `[C]` Request lifecycle, service container, service providers, facades (and how they work under the hood)
- [ ] `[C]` Routing, route model binding, middleware, controllers, form requests, validation rules
- [ ] `[C]` **Eloquent**: models, relationships (hasOne/hasMany/belongsTo/belongsToMany/morphTo/hasManyThrough), eager loading, scopes, accessors/mutators/casts, observers, soft deletes
- [ ] `[C]` Migrations, seeders, factories
- [ ] `[C]` Blade templating, components, layouts
- [ ] `[I]` Queues & jobs, events & listeners, notifications, mail, task scheduling
- [ ] `[I]` Auth: Sanctum vs Passport vs Breeze/Jetstream; gates & policies
- [ ] `[I]` API resources, pagination, rate limiting
- [ ] `[I]` Caching, config caching, `artisan` commands, `.env` handling
- [ ] `[I]` Testing in Laravel (feature vs unit, `RefreshDatabase`)
- [ ] `[S]` Octane, Horizon, Telescope; performance tuning

### Symfony (if targeting enterprise)
- [ ] `[I]` Kernel & HttpFoundation, bundles, DI container + YAML/PHP config, autowiring
- [ ] `[I]` Doctrine ORM (Data Mapper), entities, repositories, DQL
- [ ] `[I]` Twig, EventDispatcher, Console component, Forms, Security component
- [ ] `[I]` Symfony components used standalone (many other frameworks depend on them)

### Others (awareness level)
- [ ] `[I]` CodeIgniter, Yii, CakePHP, Slim, Laminas — what niche each fills
- [ ] `[I]` WordPress (hooks/filters, custom post types) if applying to agencies

---

## TIER 15 — Performance & Scaling

- [ ] `[C]` OPcache configuration & measuring hit rate
- [ ] `[C]` Caching layers: opcode, object cache (Redis/Memcached), HTTP cache, CDN, query cache
- [ ] `[C]` Identifying bottlenecks: profiling (Xdebug/Blackfire/Tideways), slow query log, APM
- [ ] `[C]` N+1 queries, unnecessary queries, `SELECT *`, pagination instead of full loads
- [ ] `[I]` Memory: `memory_get_usage`, `memory_get_peak_usage`, streaming vs loading, generators
- [ ] `[I]` PHP-FPM tuning: `pm` modes (static/dynamic/ondemand), `max_children`, `max_requests`, process math vs RAM
- [ ] `[I]` Async work: queues, workers, supervisord, cron
- [ ] `[I]` Lazy loading, deferred initialization, `WeakMap` caching
- [ ] `[S]` Horizontal scaling: stateless app servers, shared session store, sticky sessions, load balancers
- [ ] `[S]` Swoole / RoadRunner / FrankenPHP — persistent-process PHP and what breaks under it
- [ ] `[S]` Benchmarking honestly (`hrtime`, warm caches, realistic data volumes)

---

## TIER 16 — Modern PHP 8.x Feature Sweep (Rapid-Fire Interview Fodder)

- [ ] `8.0` Union types · named arguments · attributes · constructor promotion · `match` · nullsafe `?->` · `str_contains/starts_with/ends_with` · JIT · saner string↔number comparison · `static` return type · `mixed` · non-capturing catch · `throw` as an expression · `::class` on objects
- [ ] `8.1` **Enums** · `readonly` properties · **Fibers** · pure intersection types · `never` return · first-class callable syntax · `new` in initializers · `array_is_list` · final class constants
- [ ] `8.2` `readonly` classes · DNF types · `null`/`false`/`true` standalone types · deprecated dynamic properties (`#[AllowDynamicProperties]`) · constants in traits · `${}` interpolation deprecated
- [ ] `8.3` Typed class constants · `json_validate()` · dynamic class constant fetch · `#[\Override]` attribute · `Randomizer` additions · readonly reinitialization on clone
- [ ] `8.4` **Property hooks** · **asymmetric visibility** · lazy objects · `array_find` / `array_any` / `array_all` · `new` without parentheses for chaining · deprecated implicit nullable params
- [ ] `8.5` Pipe operator `|>` · `#[\NoDiscard]` · persistent cURL share handles · new array/string helpers · fatal error backtraces
- [ ] `[I]` Deprecations & the 7.x → 8.x migration path (Rector, PHPStan)

> Verify version-specific claims against the changelog for anything you'll state in an interview — release details shift late in a cycle.

---

## TIER 17 — Coding Round Preparation

### Algorithms in PHP
- [ ] `[C]` String manipulation: reverse (multibyte-safe), palindrome, anagram, word/char frequency, first non-repeating char, longest substring without repeats
- [ ] `[C]` Arrays: two-sum, max subarray, remove duplicates, rotate, merge sorted, missing number, majority element, group anagrams
- [ ] `[C]` Sorting: bubble/selection/insertion (explain), merge sort, quick sort; what PHP's `sort()` uses internally
- [ ] `[C]` Searching: linear, binary search (iterative + recursive)
- [ ] `[C]` Recursion: factorial, fibonacci (+ memoization), directory traversal, flatten nested array
- [ ] `[I]` Data structures implemented manually: linked list, stack, queue, hash map, binary tree traversals
- [ ] `[I]` Big-O of common PHP operations (array append O(1), `in_array` O(n), `isset($arr[$k])` O(1))
- [ ] `[C]` FizzBuzz, prime check/sieve, GCD/LCM, Armstrong number, leap year, pattern printing

### Practical Build Tasks
- [ ] `[C]` CRUD with PDO + prepared statements
- [ ] `[C]` Login/registration with `password_hash`, sessions, CSRF token
- [ ] `[C]` File upload with validation
- [ ] `[C]` Pagination
- [ ] `[C]` REST API endpoint returning JSON with proper status codes
- [ ] `[I]` Simple router (regex-based) + front controller
- [ ] `[I]` Simple DI container
- [ ] `[I]` Middleware pipeline
- [ ] `[I]` Consume a third-party API with cURL + error handling
- [ ] `[I]` Export/import CSV with large-file streaming

---

## TIER 18 — Classic Interview Questions (Be Able to Answer Cold)

- [ ] `==` vs `===` and the PHP 8 comparison changes
- [ ] `isset()` vs `empty()` vs `is_null()` vs `??`
- [ ] `include` vs `require` vs `*_once`
- [ ] Abstract class vs interface — with a real scenario
- [ ] Trait vs interface vs abstract class
- [ ] `self` vs `static` vs `$this` (late static binding)
- [ ] Value vs reference vs object handle
- [ ] Session vs cookie vs local storage vs JWT
- [ ] GET vs POST (and why "POST is more secure" is wrong)
- [ ] How do you prevent SQL injection / XSS / CSRF?
- [ ] `array_map` vs `array_filter` vs `array_reduce` vs `foreach`
- [ ] Closure vs arrow function
- [ ] `match` vs `switch`
- [ ] What are magic methods? Name and use each
- [ ] Shallow vs deep copy
- [ ] Why is PHP called "shared nothing"? What follows from that?
- [ ] What happens between typing a URL and PHP echoing output?
- [ ] Static vs instance methods — when static is the wrong choice
- [ ] How does autoloading work? What is PSR-4?
- [ ] What's new in PHP 8? (have 6–8 features ready)
- [ ] How do you debug a slow page?
- [ ] How do you handle errors in production vs dev?
- [ ] Explain SOLID with PHP examples
- [ ] MVC — what belongs in each layer?
- [ ] How would you scale a PHP app to 10x traffic?
- [ ] Generators — why and when?
- [ ] `final` — why use it?
- [ ] How do you test code that hits the database or network?

---

## Suggested Study Order (when you segregate)

1. **Week 1** — Tiers 1–3 (types, control flow, arrays). Arrays alone deserve two days.
2. **Week 2** — Tiers 4–5 (strings, regex, functions, generators, closures).
3. **Week 3** — Tier 6 (OOP), then Tier 7. Highest-yield block in the whole list.
4. **Week 4** — Tiers 8–10 (web, database, security). Second highest-yield.
5. **Week 5** — Tiers 12–13 (patterns, SOLID, Composer, PHPUnit) + Tier 16 (PHP 8 sweep).
6. **Week 6** — Tier 14 (your framework) + Tier 17 (coding practice daily) + Tier 18 (rapid recall drills).

**Rule:** for every concept, write a runnable snippet in this folder. Reading PHP ≠ knowing PHP.
