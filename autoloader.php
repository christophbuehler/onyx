<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

function autoloader($class)
{
    require_once str_replace('\\', '/', $class) . '.php';
}

// error handling
error_reporting(-1);
ini_set('display_errors', 'On');

spl_autoload_register('autoloader');

require_once 'vendor/autoload.php';