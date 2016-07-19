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
  public $obj;

  private $fileContents;
  private $fileName;

  /**
   * JSONFileDb constructor.
   * @param string $file
   */
  public function __construct(string $file)
  {
    $this->fileName = $file;
  }

  private function save() {
    file_put_contents($this->fileName, json_encode($this->obj));
  }
}
