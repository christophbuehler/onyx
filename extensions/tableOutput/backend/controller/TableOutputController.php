<?php

class TableOutputController
{
  public $tableOutputs = array();
  private $sessionPrefix = 'tblOutput';
  private $db;

  public function __construct(Database $db)
  {
    $this->db = $db;
  }

  /**
  * Register a table-output from the session.
  * @param  String $id   the table-output identifier
  * @param  Array  $args the arguments
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
  * Get a table-output by its ID.
  * @param  string $id  the id
  * @return TableOutput the table-output
  */
  public function get_table_by_id(string $id): TableOutput
  {
    foreach ($this->tableOutputs as $tableOutput)
      if ($tableOutput->id == $id) return $tableOutput;
    return null;
  }
}
