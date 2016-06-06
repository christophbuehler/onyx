<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Libs;

class Utils
{
  /**
   * Converts a string to a valid url.
   * @param $str
   * @return string
   */
  public static function utf8_urldecode($str): string
  {
    $str = preg_replace('/%u([0-9a-f]{3,4})/i', '&#x\\1;', urldecode($str));
    return html_entity_decode($str, null, 'UTF-8');
  }
}