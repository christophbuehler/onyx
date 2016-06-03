<?php

/**
 * Utility functions.
 */

/**
 * Call an XHR mathod.
 * @param  string $className  the class name
 * @param  string $methodName the XHR method name
 * @param  array $args       the arguments
 */
function call_xhr_method(string $className, string $methodName, array $args = [])
{
  $sortedArgs = [];

  try {

    // get the required arguments for this method
    $reqArgs = (new ReflectionMethod('HTTPResponse', 'with_status'))
      ->getParameters();
  } catch(Exception $e) {
    return false;
  }

  // not the required amount of arguments
  if (count($reqArgs) != count($args)) {

    (new PlainResponse(400, 'Argument count error.'))
      ->send();
  }

  // bring the arguments in the right order
  foreach ($reqArgs as $reqArg) {

    // all the sent arguments with the required key
    $match = array_filter($args, function($a) {
      return $a == $reqArg;
    }, ARRAY_FILTER_USE_KEY);

    // exactly one argument with that name has to be passed
    if (count($match) != 1) {
      (new PlainResponse(400, 'Argument match error.'))
        ->send();
    }

    array_push($sortedArgs, $match[0]);
  }

  // call the method
  call_user_func_array([ $className, $methodName ], $sortedArgs)
    ->send();
}

/**
 * Create an ajax method name.
 * @param  string $reqMethod the request HTTP method (GET, POST, PUT..)
 * @param  array $url        the URL array
 * @return string            the method name
 */
function compose_ajax_method_name(string $reqMethod, array $url): string
{
  return REMOTE_FUNCTION_START . '_' .  $reqMethod . '_' . $url[ count($url) - 1 ];
}

/**
* Converts a string to a valid url.
* @param  string $str the string
* @return string      the formatted url
*/
function utf8_urldecode($str): string
{
  $str = preg_replace('/%u([0-9a-f]{3,4})/i', '&#x\\1;', urldecode($str));
  return html_entity_decode($str, null, 'UTF-8');
}

function
