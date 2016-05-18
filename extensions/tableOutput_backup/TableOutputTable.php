<?php

/**
 * 
 */
class TableOutputTable
{
  private $name;
  private $id;
  private $fields = [];

  /**
  * TableOutputTable constructor.
  * @param  string $name   the table name
  * @param  string $id     the table id
  * @param  array  $fields the fields
  */
  function __constructor(string $name, string $id, array $fields)
  {
    $this->table = $name;
    $this->id = str_replace(' ', '', $id);
    $this->fields = $fields;
  }
}
