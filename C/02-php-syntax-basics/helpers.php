<?php

/*
 Shared view helpers - used by views/user-list.php and 04-template-render.php.
 A pure PHP file, so there is NO closing tag at the end.
*/

declare(strict_types=1);

/**
 * Escape a value for safe output inside HTML.
 *
 * Every dynamic value printed into a page MUST go through this. It converts
 * < > " ' & into HTML entities so that user data is displayed as TEXT and
 * never executed as markup or JavaScript. This is the primary defence against
 * XSS (cross-site scripting).
 *
 * ENT_QUOTES  escapes BOTH single and double quotes, which is required when
 *             the value is placed inside an HTML attribute.
 * 'UTF-8'     states the charset explicitly rather than relying on a default.
 *
 * The short name e() exists so templates stay readable: <?= e($name) ?>
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Render a template file and RETURN the resulting HTML as a string.
 *
 * Using output buffering here means the caller decides what to do with the
 * HTML - echo it, wrap it in a layout, cache it, or send it as part of a JSON
 * payload. This is exactly how a minimal MVC view layer works.
 *
 * @param array<string, mixed> $data Variables made available to the template.
 */
function render(string $templatePath, array $data = []): string
{
    // extract() turns $data['users'] into $users inside this function's scope.
    // EXTR_SKIP refuses to overwrite variables that already exist here, which
    // stops a malicious or careless data key from clobbering $templatePath.
    extract($data, EXTR_SKIP);

    ob_start();                       // start capturing output
    include $templatePath;            // the template prints into the buffer
    return (string) ob_get_clean();   // return the captured HTML
}
