<?php

function autoloader($class)
{
  require_once str_replace('\\', '/', $class) . '.php';
}

// error handling
error_reporting(-1);
ini_set('display_errors', 'On');

spl_autoload_register('autoloader');

require_once 'vendor/autoload.php';