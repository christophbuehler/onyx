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

class PDODb extends PDO implements iDb
{

  /**
   * PDODatabase constructor.
   * @param string $dbType
   * @param string $dbHost
   * @param string $dbName
   * @param string $dbCharset
   * @param string $dbUser
   * @param string $dbPass
   */
  public function __construct(string $dbType, string $dbHost, string $dbName, string $dbCharset, string $dbUser, string $dbPass)
  {
    try {
      parent::__construct($dbType . ':host=' . $dbHost . ';dbname=' . $dbName . ';charset=' . $dbCharset, $dbUser, $dbPass);
    } catch(Exception $e) {
      (new PlainResponse(500, 'No database connection.'))
        ->send();
    }
  }
}
