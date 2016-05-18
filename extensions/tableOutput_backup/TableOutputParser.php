<?php

class TableOutputParser
{
  private $tableOutput;

  function __construct($tableOutput) {
    $this->tableOutput = $tableOutput;
  }

  /**
  * Get the link value of a field.
  * @param  Object $field  the field
  * @param  int $id        the link id
  * @param  Array $values  the linked values
  * @return String         the value
  */
  public function get_link_value($field, $id, $values = [])
  {
    if (!isset($field->link)) {
      return $id;
    }

    return $this->get_link($field->link, $id, $values);
  }

  public function validate_content($type, $content)
  {
    $content = $content;
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

  private function get_link($link, $id, $values)
  {
    $db = $this->tableOutput->db;

    $containedValues = array();

    $sth = $db->prepare($link->compose_link_query());

    if ($id != null) {
      $usedId = $id;
    } elseif (isset($link->value)) {
      $usedId = $values[$link->value];
    } else {
      return $id;
    }

    if (!$sth->execute(array(
      ':id' => $usedId,
    ))) {
      throw new SQLException(sprintf('Could not get link value for link id: %s', $id), $sth);
    }

    $result = $sth->fetch()[0];

    if (isset($link->link) && $result != null) {
      return $this->get_link($link->link, $result, $values);
    }

    return $result;
  }

  /**
  * Get the content of a database value.
  * @param  Object $field the field
  * @param  String $val   the field value
  * @return String        the autocomplete value
  */
  // public function get_autocomplete_value($field, $val)
  // {
  //     $db = $this->tableOutput->db;
  //
  //     if (isset($field->link)) {
  //         return $this->get_link_value($field, $val);
  //     }
  //
  //     if (!isset($field->autocomplete)) {
  //         return $val;
  //     }
  //
  //     // this is an autocomplete field
  //     $sth = $db->prepare($this->autocomplete[$field]);
  //     $sth->execute(array(
  //         ':value' => $val,
  //     ));
  //
  //     return $sth->fetch()[0];
  // }

  // public function get_href_value($field, $val, $allValues, $linkVal)
  // {
  //
  //     // no href found. Maybe, try to treat it as a web link or email address
  //     if (!isset($field->href)) {
  //         return $this->get_email_and_website_links($val, $linkVal);
  //     }
  //
  //     $str = $field->href;
  //
  //     foreach ($allValues as $key => $value) {
  //         $str = str_replace(':'.$key, $value, $str);
  //     }
  //
  //     return '<a href="'.str_replace(':value', $val, $str).'">'.$linkVal.'</a>';
  // }

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
  * @return Array             a list of entries
  */
  public function link($fieldName, $val)
  {
    $links = [];

    $field = $this->tableOutput->config->get_field_by_name($fieldName);

    if (!isset($field->link)) {
      throw new Exception(sprintf('No link could be found for the field: %s', $field->name));
    }

    $sth = $this->tableOutput->db->prepare($field->link->compose_autocomplete_query());

    if (!$sth->execute(array(
      ':value' => $val . '%',
    ))) {
      throw new SQLException('Could not get link autocomplete.', $sth);
    }

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      array_push($links, [

        // used to identitfy the entry
        'value' => $row['id'],

        // visible by the user
        'content' => $row['text']
      ]);
    }
    
    return $links;
  }

  /** Get reverse autocomplete value for a given identifier.
  */
  public function link_text($fieldName, $id)
  {
    $str = '';

    $field = $this->tableOutput->config->get_field_by_name($fieldName);

    if (!isset($field->link)) {
      throw new Exception(sprintf('No link could be found for the field: %s', $field->name));
    }

    $sth = $this->tableOutput->db->prepare($field->link->compose_text_query());

    if (!$sth->execute(array(
      ':value' => $id,
    ))) {
      throw new SQLException('Could not get link autocomplete.', $sth);
    }

    return array(
      'code' => 0,
      'text' => $sth->fetch()['text'],
    );
  }
}
?>
