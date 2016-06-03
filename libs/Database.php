<?php
class Database extends PDO {
  public function __construct() {
    try {
      parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    } catch(exception $e) {
      (new PlainResponse(500, 'No database connection.'))
        ->send();
    }
  }
}
