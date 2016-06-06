<?php

namespace Onyx\Libs;

use PDO;

class Database extends PDO
{

  /**
   * Database constructor.
   */
  public function __construct(string $dbType, string $dbHost, string $dbName, string $dbCharset, string $dbUser, string $dbPass)
  {
    try {
      parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    } catch(exception $e) {
      (new PlainResponse(500, 'No database connection.'))
        ->send();
    }
  }
}
