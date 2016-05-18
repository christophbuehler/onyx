<?php

class TableOutputField
{

  /* user properties */
  public $name;
  public $header;
  public $predefined;
  public $link;
  public $independent = false;
  public $suggestion;

  /* system properties */
  public $filter;
  // public $autocomplete;
  public $type;
  public $autoIncrement = false;
  public $notNull = false;

  function __construct($args)
  {
    $this->independent = $args['independent'] ?? false;

    $this->name = $args['name'] ?? null;

    if (isset($args['header']))
      $this->header = trim($args['header']);

    if (isset($args['predefined']))
      $this->predefined = $args['predefined'];

    if (isset($args['link']))
      $this->link = new TableOutputLinkTable($args['link'], $this->name);

    if (isset($args['href']))
      $this->href = $args['href'];

    if (isset($args['suggestion']))
      $this->suggestion = $args['suggestion'];

    if (isset($args['type']))
      $this->type = $args['type'];

    return new SuccessHandler();
  }
}
