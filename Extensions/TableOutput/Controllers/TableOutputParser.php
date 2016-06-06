<?php

namespace Onyx\Extensions\TableOutput\Controllers;

use Exception;
use Onyx\Extensions\TableOutput\Exceptions\SQLException;
use Onyx\Extensions\TableOutput\Models\TableOutputField;
use Onyx\Extensions\TableOutput\Models\TableOutputLinkTable;
use Onyx\Extensions\TableOutput\Views\TableOutputRow;
use Onyx\Extensions\TableOutput\TableOutput;
use PDO;

class TableOutputParser
{
  private $tableOutput;

  function __construct(TableOutput $tableOutput) {
    $this->tableOutput = $tableOutput;
  }

  /**
  * Get the link value of a field.
  * @param  Object $field  the field
  * @param  int $id        the link id
  * @param  array $values  the linked values
  * @return String         the value
  */
  public function get_reverse_link_value(TableOutputField $field, $id)
  {
    if (!isset($field->link)) {
      return $id;
    }

    return $this->reverse_link($field, $id);
  }

  public function validate_content($type, $content)
  {
    switch ($type) {
      case 'TINY':
        return $content == '1' ? 'Ja' : 'Nein';
      case 'DATE':
        if (trim($content) == '') {
          return '';
        }

        return date('d.m.Y', strtotime($content));
      default:
        return $content;
    }
  }

  public function get_link_values(TableOutputLinkTable $link, string $id)
  {
    $db = $this->tableOutput->db;
    $sth = $db->prepare($link->compose_link_query());

    if (!$sth->execute(array(
      ':id' => $id,
    ))) {
      throw new SQLException(sprintf('Could not get link value for link id: %s', $id), $sth);
    }

    $result = $sth->fetch();

    return $result;
  }

  public function get_email_and_website_links($val, $linkVal)
  {
    // it's a valid email address
    if (filter_var($linkVal, FILTER_VALIDATE_EMAIL)) {
      return '<a href="mailto: '.$linkVal.'">'.$linkVal.'</a>';
    }

    // it's a valid url
    if (preg_match('/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/', $linkVal)) {
      return '<a href="'.$linkVal.'">'.$linkVal.'</a>';
    }

    return $linkVal;
  }

  /**
  * Get link link proposals for autocompletion.
  * @param  String $fieldName the field name
  * @param  String $val       the needle
  * @return array             a list of entries
  */
  public function link($fieldName, $val)
  {
    $tableOutput = $this->tableOutput;
    $links = [];

    $field = $this->tableOutput->rootTable->get_field_by_name($fieldName);

    if (!isset($field->link)) {
      throw new Exception(sprintf('No link could be found for the field: %s', $field->name));
    }

    $sth = $this->tableOutput->db->prepare($this->tableOutput->queryHandler->select_autocomplete($field->link, $val));
    if (!$sth->execute((new TableOutputFilterHandler($field->link))->get_values())) {
      throw new SQLException('Could not get link autocomplete.', $sth);
    }

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      array_push($links, (new TableOutputRow($row[$field->link->idAlias], $row, $field->link->fields, $tableOutput->parser))
          ->parse_autocomplete());
    }

    return $links;
  }

  /** Get reverse autocomplete value for a given identifier.
  */
  public function reverse_link(TableOutputField $field, $id)
  {
    $str = '';

    if (!isset($field->link)) {
      throw new Exception(sprintf('No link could be found for the field: %s', $field->name));
    }

    return $this->tableOutput->queryHandler->get_reverse_autocomplete($field->link, $id);
  }
}
?>
