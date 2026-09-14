<?php

/*
================================================================================
 TOPIC 02 - EXAMPLE 3 : WHY YOU NEVER WRITE ?> IN A PURE PHP FILE
================================================================================

 WHAT THIS DEMONSTRATES
   The most common "invisible" bug in PHP. A single newline after ?> in an
   included file becomes OUTPUT, which:
       - corrupts JSON responses (the mobile app cannot parse them)
       - causes "Cannot modify header information - headers already sent"
       - breaks redirects, cookies, session_start() and file downloads

   This script proves it at BYTE level, so you can see the stray character
   rather than take it on trust.

 FILES USED
   config-bad.php   - has ?> and a blank line at the end   (broken)
   config-good.php  - no closing tag                       (correct)

 HOW TO RUN
   php 03-closing-tag-trap.php

================================================================================
*/

declare(strict_types=1);

echo '=============== 1. WHAT EACH FILE EMITS ===============' . PHP_EOL;

// Output buffering lets us CAPTURE whatever a file prints instead of sending
// it out, so we can inspect it. See file 50 for output buffering in depth.
ob_start();
require __DIR__ . '/config-bad.php';
$emittedByBad = ob_get_clean();          // grabs the buffer AND turns it off

ob_start();
require __DIR__ . '/config-good.php';
$emittedByGood = ob_get_clean();

printf("config-bad.php  emitted %d byte(s): %s%s",
    strlen($emittedByBad),
    $emittedByBad === '' ? '(nothing)' : bin2hex($emittedByBad) . '  <- stray bytes!',
    PHP_EOL
);

printf("config-good.php emitted %d byte(s): %s%s",
    strlen($emittedByGood),
    $emittedByGood === '' ? '(nothing)' : bin2hex($emittedByGood),
    PHP_EOL
);

echo PHP_EOL . '  0a = newline (\n), 0d = carriage return (\r), 20 = space' . PHP_EOL;

echo PHP_EOL . '=============== 2. HOW IT DESTROYS A JSON API ===============' . PHP_EOL;

// Build the response exactly as a real API endpoint would.
$badResponse  = $emittedByBad  . json_encode(['status' => 'ok', 'id' => 42]);
$goodResponse = $emittedByGood . json_encode(['status' => 'ok', 'id' => 42]);

foreach (['BROKEN (with ?>)' => $badResponse, 'CORRECT (no ?>)' => $goodResponse] as $label => $response) {
    echo PHP_EOL . $label . PHP_EOL;
    echo '  raw bytes : ' . bin2hex(substr($response, 0, 12)) . '...' . PHP_EOL;
    echo '  as text   : ' . str_replace(["\r", "\n"], ['\r', '\n'], $response) . PHP_EOL;

    // A strict JSON parser (like the one in a mobile app) rejects leading junk.
    $decoded = json_decode($response, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    echo '  json_decode: ' . (json_last_error() === JSON_ERROR_NONE ? 'OK' : 'FAILED')
       . ' (' . json_last_error_msg() . ')' . PHP_EOL;

    // The stricter, modern check every API should use:
    echo '  json_validate(): '
       . (function_exists('json_validate') && json_validate($response) ? 'valid' : 'INVALID')
       . PHP_EOL;
}

echo PHP_EOL . '=============== 3. HOW IT BREAKS HEADERS ===============' . PHP_EOL;

// headers_sent() reports whether output has already started. When it has, every
// later header() / setcookie() / session_start() call fails with a warning.
$file = '';
$line = 0;

if (headers_sent($file, $line)) {
    echo '  Output already started at ' . basename($file) . ' line ' . $line . PHP_EOL;
    echo '  -> in a WEB request, header(), setcookie() and session_start()' . PHP_EOL;
    echo '     would now fail with "headers already sent".' . PHP_EOL;
    echo '  -> in CLI there are no HTTP headers, so nothing breaks here - but' . PHP_EOL;
    echo '     notice that headers_sent() still tracks output correctly, and it' . PHP_EOL;
    echo '     names the exact file and line where output began. That pair of' . PHP_EOL;
    echo '     values is what the real warning message reports.' . PHP_EOL;
} else {
    echo '  No output sent yet, so headers could still be set.' . PHP_EOL;
}

echo PHP_EOL . '=============== 4. HOW TO FIND IT IN A REAL PROJECT ===============' . PHP_EOL;

/**
 * A tiny linter you could actually run in CI over your src/ folder.
 *
 * IMPORTANT: it uses the TOKENIZER, not a text search. A naive
 * str_contains($code, '?>') would also match the characters "?>" written
 * inside a comment or a string - including the explanation blocks in these
 * very files - and report dozens of false positives.
 *
 * token_get_all() runs PHP's real lexer, so T_CLOSE_TAG means an actual
 * closing tag that PHP would act on. This is how PHP CS Fixer does it.
 *
 * @return array<string, string> filename => description of the problem
 */
function findRealClosingTags(string $directory): array
{
    $suspects = [];

    foreach (glob($directory . '/*.php') ?: [] as $path) {
        $code   = (string) file_get_contents($path);
        $tokens = token_get_all($code);

        // Find the LAST real closing tag, if there is one.
        $lastCloseOffset = null;

        foreach ($tokens as $token) {
            // Simple tokens are strings; complex ones are [id, text, line].
            if (is_array($token) && $token[0] === T_CLOSE_TAG) {
                $lastCloseOffset = $token[2];      // line number
            }
        }

        if ($lastCloseOffset === null) {
            continue;                              // clean file - no closing tag
        }

        // How many bytes follow the final closing tag?
        $afterFinalTag = substr($code, (int) strrpos($code, '?>') + 2);

        $suspects[basename($path)] = sprintf(
            'closing tag on line %d, %d byte(s) after it%s',
            $lastCloseOffset,
            strlen($afterFinalTag),
            $afterFinalTag === '' ? '' : ' (' . bin2hex(substr($afterFinalTag, 0, 8)) . ')'
        );
    }

    return $suspects;
}

$suspects = findRealClosingTags(__DIR__);

if ($suspects === []) {
    echo '  No files with a real closing tag found.' . PHP_EOL;
} else {
    foreach ($suspects as $name => $problem) {
        echo '  ' . str_pad($name, 26) . $problem . PHP_EOL;
    }
}

echo PHP_EOL . '  Note: views/user-list.php is a TEMPLATE, so its closing tags are' . PHP_EOL;
echo '  correct. A real linter excludes the views directory for that reason.' . PHP_EOL;

echo PHP_EOL . '  In a real project use PHP CS Fixer (no_closing_tag) or' . PHP_EOL;
echo '  PHP_CodeSniffer with the PSR-12 ruleset in CI instead of this script.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 ob_start() / ob_get_clean()
     ob_start() starts an output buffer: everything printed after it is captured
     in memory instead of being sent. ob_get_clean() returns the captured
     contents AND discards the buffer. Together they let us measure exactly what
     an included file emitted. (Output buffering is covered fully in file 50.)

 bin2hex($string)
     Converts each byte to two hex characters. This is how you SEE invisible
     characters:
         0a = \n newline          0d = \r carriage return
         20 = space               efbbbf = a UTF-8 BOM
     If you ever face "headers already sent" with no visible output, bin2hex on
     the first bytes of the response is how you find the culprit.

 $emittedByBad . json_encode([...])
     Simulates the real failure: the stray newline is written BEFORE the JSON
     body, so the response is "\n{...}" rather than "{...}".

 json_decode($response, true, 512, JSON_INVALID_UTF8_SUBSTITUTE)
     Arguments: the JSON string, true for an associative array (not stdClass),
     512 = maximum nesting depth, then flags. Note: PHP's own json_decode is
     lenient about LEADING whitespace, so it may still succeed - but many
     strict parsers in other languages, and any code doing an exact byte
     comparison or a checksum, will fail. That difference is precisely why the
     bug is so confusing to debug: it works in PHP and fails in the mobile app.

 json_validate($json)   (PHP 8.3+)
     Checks whether a string is valid JSON without building the whole structure
     in memory. Cheaper than json_decode when you only need a yes/no answer.

 headers_sent($file, $line)
     Returns true once output has started, and fills the two by-reference
     arguments with the FILE and LINE where output began. That is the single
     most useful diagnostic for this bug - the "headers already sent" warning
     prints the same information, and it points straight at the offending file.
     Note: passing variables that receive values is a by-reference parameter -
     see file 30.

 glob(__DIR__ . '/*.php')
     Returns an array of matching file paths. The ?: [] guards against glob()
     returning false on error.

 strrpos($contents, '?>')
     Finds the LAST occurrence of ?> (strpos would find the first). Everything
     after it is what would be emitted as output.

 str_contains($contents, '?>')   (PHP 8.0+)
     Modern, readable replacement for strpos($h, $n) !== false.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== 1. WHAT EACH FILE EMITS ===============
   config-bad.php  emitted 1 byte(s): 0a  <- stray byte!
   config-good.php emitted 0 byte(s): (nothing)

   (You may see 2 bytes 0d0a instead, depending on whether the file was saved
    with Windows CRLF or Unix LF line endings. Either way it is output that
    should not exist.)

     0a = newline (\n), 0d = carriage return (\r), 20 = space

   =============== 2. HOW IT DESTROYS A JSON API ===============

   BROKEN (with ?>)
     raw bytes : 0d0a7b227374617475...
     as text   : \r\n{"status":"ok","id":42}
     json_decode: OK (No error)
     json_validate(): valid

   CORRECT (no ?>)
     raw bytes : 7b22737461747573...
     as text   : {"status":"ok","id":42}
     json_decode: OK (No error)
     json_validate(): valid

   NOTE: PHP's parser tolerates the leading whitespace. The response is still
   WRONG - the Content-Length is off by two, the headers were already flushed,
   and stricter clients reject it. Never rely on the tolerance.

   =============== 3. HOW IT BREAKS HEADERS ===============
     Output already started at 03-closing-tag-trap.php line 30
     -> in a WEB request, header(), setcookie() and session_start()
        would now fail with "headers already sent".
     ...

   =============== 4. HOW TO FIND IT IN A REAL PROJECT ===============
     config-bad.php            closing tag on line 15, 2 byte(s) after it (0a0a)

   WHY 2 BYTES IN THE FILE BUT ONLY 1 BYTE EMITTED?
     PHP swallows ONE newline immediately after ?>. config-bad.php has two
     newlines after the tag, so one is eaten and one is emitted. That single
     rule is why the bug is so easy to create by accident and so hard to see:
     a file with exactly one trailing newline emits nothing, and a file with
     two emits a byte. You cannot tell them apart by looking.

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. NEVER write ?> in a file that contains only PHP. Template files that mix
     HTML and PHP are the only exception.
  2. Any byte after ?> is output. Output before headers = broken headers,
     broken redirects, broken cookies, broken sessions, corrupted JSON.
  3. The same problem is caused by: a space BEFORE <?php on line 1, and by
     saving a file as "UTF-8 with BOM".
  4. To find it: read the file and line in the warning, or bin2hex the first
     bytes of the response.
  5. To prevent it: PHP CS Fixer / PHP_CodeSniffer PSR-12 in CI, plus an editor
     configured to trim trailing whitespace and save without BOM.

 INTERVIEW LINK
   "Why do we omit the closing PHP tag?"
   "What causes 'headers already sent' and how do you fix it?"
   "Your JSON API returns data the app cannot parse, but it looks fine in the
    browser. How do you debug it?"   -> curl -i, then bin2hex the raw bytes.

 TRY THIS IN THE BROWSER
   Create a file that requires config-bad.php and then calls
   header('Content-Type: application/json'). Open it in the browser and read the
   warning - it names the exact file and line where output started.
================================================================================
*/
