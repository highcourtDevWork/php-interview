<?php
/*
 DELIBERATELY BROKEN FILE - used by 03-closing-tag-trap.php

 Notice the ?> below, followed by a blank line. Everything after ?> is treated
 as OUTPUT and sent to the browser the moment this file is included.
 Never write a closing tag in a pure PHP file.
*/

$configBad = [
    'db_name' => 'shop',
    'debug'   => false,
];

?>

