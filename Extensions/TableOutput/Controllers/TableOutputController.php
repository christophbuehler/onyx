<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Controllers;

use Exception;
use Onyx\DataProviders\PDODatabase;
use Onyx\Extensions\TableOutput\Models\TableOutputConfig;
use Onyx\Extensions\TableOutput\TableOutput;

class TableOutputController
{
  public $tableOutputs = array();
  private $db;

  /**
   * TableOutputController constructor.
   * @param PDODatabase $db
   */
  public function __construct(PDODatabase $db)
  {
    $this->db = $db;
  }

  /**
  * Register a table-output from the session.
  * @param  String $id   the table-output identifier
  * @param  array  $args the arguments
  * @return TableOutput  the registered table-output
  */
  public function register(string $id, array $args): TableOutput
  {
    $tableOutput = new TableOutput(
      $this->db,
      $id,
      $this,
      $args[0],
      new TableOutputConfig($args[1] ?? []));

    array_push($this->tableOutputs, $tableOutput);
    return $tableOutput;
  }

  /**
   * Get a TableOutput by its ID.
   * @param string $id
   * @return TableOutput
   * @throws Exception
   */
  public function get_table_by_id(string $id): TableOutput
  {
    foreach ($this->tableOutputs as $tableOutput)
      if ($tableOutput->id == $id) return $tableOutput;

    throw new Exception('Could not find table.');
  }
}
