<?php

class TableOutputLinkTable extends TableOutputTable
{
  private $reference;

  function __construct($args)
  {
    parent::__construct($args);
    $this->reference = $args['reference'] ?? null;
  }

  /**
   * Get the autocomplete query.
   * @return string the query for autocompletion
   */
  public function compose_autocomplete_query(): string
  {
    $linkQuery = sprintf('SELECT %s AS id, %s AS text FROM %s WHERE %s LIKE :value LIMIT %s',
    $this->id, $this->text, $this->table, $this->text, $this->autocompleteLimit);

    return $linkQuery;
  }

  /**
  * Returns the query for link metas.
  * @return String the query
  */
  function compose_meta_query()
  {
    return sprintf('SELECT %s AS text FROM %s LIMIT 1',
    $this->text, $this->table);
  }

  /** Returns the query for text.
  */
  public function compose_text_query()
  {
    $textQuery = sprintf('SELECT %s AS text FROM %s WHERE %s = :value LIMIT 1',
    $this->text, $this->table, $this->id);

    return $textQuery;
  }

  /** This query returns the link text of a foreign table by its foreign key.
  */
  public function compose_link_query()
  {
    $linkQuery = sprintf('SELECT %s AS text FROM %s WHERE %s = :id',
    $this->text, $this->table, $this->id);

    return $linkQuery;
  }
}
