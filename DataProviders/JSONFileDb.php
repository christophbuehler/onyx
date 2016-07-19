<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\DataProviders;

use Exception;
use Onyx\Http\PlainResponse;
use PDO;

class JSONFileDb implements iDb
{
  private $fileContents;
  private $fileName;
  private $obj;

  /**
   * JSONFileDb constructor.
   * @param string $file
   */
  public function __construct(string $file)
  {
    $this->fileName = $file;
	  $this->fileContents = file_exists($file) ? file_get_contents($file) : '[]';
	  $this->obj = json_decode($this->fileContents, true);
  }

  public function set($key, $value) {
    $this->obj[$key] = $value;
    $this->save();
  }

  public function get($key) {
    return $this->obj[$key];
  }

  public function save() {
    file_put_contents($this->fileName, json_encode($this->obj));
  }
}
