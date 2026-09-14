<?php

/*
================================================================================
 TOPIC 02 - EXAMPLE 4 : TEMPLATES, SHORT ECHO TAGS AND OUTPUT ESCAPING
================================================================================

 WHAT THIS DEMONSTRATES
   How PHP is correctly mixed with HTML in a view file, and why every single
   dynamic value must be escaped. One of the users in this demo has a malicious
   name containing <script>. You will see it rendered harmlessly as text, and
   you will see what would have happened without escaping.

   This is a minimal version of the view layer every PHP framework provides.

 FILES USED
   helpers.php           e() and render()
   views/user-list.php   the template (alternative syntax + <?= ?>)

 HOW TO RUN
   Command line :  php 04-template-render.php
   Browser      :  http://localhost/php_learning_interview_prep/C/02-php-syntax-basics/04-template-render.php
                   <- open this one in the browser too; it renders a real table

================================================================================
*/

declare(strict_types=1);

require __DIR__ . '/helpers.php';     // __DIR__ so the path never depends on CWD

// =============================================================================
// 1. THE "CONTROLLER" PART - prepare the data
// =============================================================================

$users = [
    ['name' => 'Ravi Kumar',  'email' => 'ravi@example.com',  'role' => 'admin',  'active' => true],
    ['name' => 'Amit Sharma', 'email' => 'amit@example.com',  'role' => 'editor', 'active' => false],

    // A hostile value, exactly as it would arrive from a signup form.
    ['name' => '<script>alert("XSS")</script>', 'email' => 'evil@example.com', 'role' => 'user', 'active' => true],

    // A value with quotes - this is why ENT_QUOTES matters. Without it, the
    // quote would break out of the title="..." attribute in the template.
    ['name' => 'O\'Brien "The Boss"', 'email' => 'obrien@example.com', 'role' => 'user', 'active' => true],
];

// =============================================================================
// 2. RENDER THE VIEW
// =============================================================================

$html = render(__DIR__ . '/views/user-list.php', [
    'title' => 'Registered Users',
    'users' => $users,
]);

// =============================================================================
// 3. SHOW WHAT ESCAPING ACTUALLY DID
// =============================================================================

if (PHP_SAPI === 'cli') {

    echo '=============== ESCAPING, VALUE BY VALUE ===============' . PHP_EOL;

    foreach ($users as $user) {
        echo 'raw    : ' . $user['name'] . PHP_EOL;
        echo 'escaped: ' . e($user['name']) . PHP_EOL . PHP_EOL;
    }

    echo '=============== PROOF THE SCRIPT TAG IS NEUTRALISED ===============' . PHP_EOL;
    echo 'Does the rendered HTML contain a live <script> tag? ';
    echo (str_contains($html, '<script>') ? 'YES - VULNERABLE' : 'No - safe') . PHP_EOL;

    echo 'Does it contain the escaped form &lt;script&gt;?     ';
    echo (str_contains($html, '&lt;script&gt;') ? 'Yes - escaped correctly' : 'No') . PHP_EOL;

    echo PHP_EOL . '=============== WHAT UNESCAPED OUTPUT WOULD LOOK LIKE ===============' . PHP_EOL;
    echo 'UNSAFE: <td>' . $users[2]['name'] . '</td>' . PHP_EOL;
    echo '  -> the browser would EXECUTE that JavaScript.' . PHP_EOL;
    echo 'SAFE  : <td>' . e($users[2]['name']) . '</td>' . PHP_EOL;
    echo '  -> the browser DISPLAYS it as text.' . PHP_EOL;

    echo PHP_EOL . '=============== FIRST 20 LINES OF RENDERED HTML ===============' . PHP_EOL;
    echo implode(PHP_EOL, array_slice(explode(PHP_EOL, $html), 0, 20)) . PHP_EOL;

} else {
    // In the browser, just send the page.
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
}

/*
================================================================================
 CODE EXPLANATION
================================================================================

 require __DIR__ . '/helpers.php';
     __DIR__ is the directory of THIS file, so the path works no matter what
     the current working directory is. A relative require './helpers.php'
     breaks when the script is run from another folder or by cron.

 render(__DIR__ . '/views/user-list.php', ['title' => ..., 'users' => ...])
     The controller passes data to the view explicitly. The view never fetches
     anything itself - that separation is the whole point of MVC (file 63).

 INSIDE render() (see helpers.php):

   extract($data, EXTR_SKIP)
       Turns array keys into local variables, so $data['users'] becomes $users
       inside the template. EXTR_SKIP refuses to overwrite existing variables,
       which prevents a data key named 'templatePath' from hijacking the
       function's own variable.
       WARNING: extract() on raw user input ($_POST) is dangerous for exactly
       that reason. Here the keys are controlled by us, and EXTR_SKIP adds a
       second layer of safety. Real frameworks use it the same way.

   ob_start() ... ob_get_clean()
       The template PRINTS its HTML. Output buffering captures that printing
       and returns it as a string, so the caller can decide what to do with it.
       This is how a view returns HTML instead of sending it immediately.

 INSIDE THE TEMPLATE (views/user-list.php):

   <?php if ($users === []): ?> ... <?php endif; ?>
       ALTERNATIVE SYNTAX. In a file full of HTML this is far easier to follow
       than matching { and } across 30 lines, because each block is closed by a
       named keyword: endif, endforeach, endwhile.

   <?= e($user['name']) ?>
       The short echo tag. Always available since PHP 5.4 - unlike the short
       OPEN tag <? which depends on an ini setting and must be avoided.
       Note there is no semicolon before ?> - it is optional there.

   e() / htmlspecialchars($v, ENT_QUOTES, 'UTF-8')
       Converts:
           <  ->  &lt;        >  ->  &gt;
           "  ->  &quot;      '  ->  &#039;      &  ->  &amp;
       So <script>alert("XSS")</script> becomes text the browser displays
       rather than code the browser runs.

       ENT_QUOTES matters in this template because of:
           <td title="<?= e($user['name']) ?>">
       The name O'Brien "The Boss" contains a double quote. Without ENT_QUOTES
       that quote would close the title attribute early and let an attacker
       inject onmouseover="..." into the tag. Attribute context is the reason
       ENT_QUOTES is not optional.

 str_contains($html, '<script>')     (PHP 8.0+)
     Readable replacement for strpos(...) !== false. Used here to PROVE that
     no live script tag survived into the output.

================================================================================
 EXPECTED OUTPUT  (CLI, abbreviated)
================================================================================

   =============== ESCAPING, VALUE BY VALUE ===============
   raw    : <script>alert("XSS")</script>
   escaped: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;

   raw    : O'Brien "The Boss"
   escaped: O&#039;Brien &quot;The Boss&quot;

   =============== PROOF THE SCRIPT TAG IS NEUTRALISED ===============
   Does the rendered HTML contain a live <script> tag? No - safe
   Does it contain the escaped form &lt;script&gt;?     Yes - escaped correctly

 IN THE BROWSER you get a real HTML table. The third user's name is displayed
 as the literal text <script>alert("XSS")</script> and no alert box appears.
 Remove the e() calls from the template and reload to see the alert fire -
 that is a live XSS vulnerability in four keystrokes.

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Template files are the only PHP files that should contain closing tags.
  2. Use alternative syntax (if: / endif;) in templates - it keeps HTML readable.
  3. <?= ?> is always safe to use; <? ?> is not (ini-dependent).
  4. ESCAPE EVERY DYNAMIC VALUE with htmlspecialchars(..., ENT_QUOTES, 'UTF-8').
     A short helper e() makes this painless, so there is no excuse to skip it.
  5. ENT_QUOTES is required whenever a value can land inside an HTML attribute.
  6. Keep logic out of views. The controller prepares; the view displays.
  7. In a larger project, a template engine like Twig escapes automatically,
     which removes the risk of forgetting.

 INTERVIEW LINK
   "How do you prevent XSS in PHP?"
   "What is the difference between <?= and <? ?"
   "Why use alternative syntax in templates?"
   "What does ENT_QUOTES do and why does it matter?"

 TRY THIS (do it - it makes XSS concrete)
   1. Open 04-template-render.php in the browser. Note there is no alert.
   2. Edit views/user-list.php and change  <?= e($user['name']) ?>
      to  <?= $user['name'] ?>  in the name column.
   3. Reload. The alert box fires - you just created a stored XSS bug.
   4. Put the e() back.
================================================================================
*/
