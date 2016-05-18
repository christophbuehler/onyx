<?php
require_once('TableOutputTable.php');

class TableOutputLinkTable extends TableOutputTable
{
  public $reference;
  public $autocompleteLimit = 10;

  function __construct(array $args, TableOutput $tableOutput)
  {
    parent::__construct($args, $tableOutput);
    $this->reference = $args['reference'] ?? null;
  }

  /**
   * Get the autocomplete query.
   * @return string the query for autocompletion
   */
  /* public function compose_autocomplete_query(): string
  {
    $fieldNames = $this->get_field_names();
    $linkQuery = sprintf('SELECT %s AS id, CONCAT(%s) AS text FROM %s WHERE CONCAT(%s) LIKE :value LIMIT %s',
      $this->id, "'\"', " . implode($fieldNames, ", '\", \"', ") . ", '\"'", $this->name, implode($fieldNames, ", ' ', "), $this->autocompleteLimit);

    return $linkQuery;
  } */

  /** Returns the query for text.
  */
  /*public function compose_reverse_autocomplete_query(): string
  {
    $fieldNames = $this->get_field_names();
    $linkQuery = sprintf('SELECT %s AS id, CONCAT(%s) AS text FROM %s WHERE CONCAT(%s) = :value',
      $this->id, $this->get_coalesce_field_names(), $this->name, $this->id);

    return $linkQuery;
  }*/

  private function get_coalesce_field_names() {
    $fieldNames = $this->get_field_names();
    return sprintf('COALESCE(%s)',
      implode($fieldNames, ", ''), ' ', COALESCE("));
  }

  /** This query returns the link text of a foreign table by its foreign key.
  */
  public function compose_link_query(): string
  {
    $fieldNames = [];
    foreach ($this->fields as $field) {
      array_push($fieldNames, $field->name);
    }

    $linkQuery = sprintf('SELECT %s FROM %s WHERE %s = :id',
      implode($fieldNames, ','), $this->name, $this->id);

    return $linkQuery;
  }
}
