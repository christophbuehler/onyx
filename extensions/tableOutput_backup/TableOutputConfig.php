<?php

class TableOutputConfig
{
  // public $table;
  // public $id;
  // public $idValue;
  // public $query;
  // public $fields = array();
  //
  public $orientation = 'vertical';
  public $pageRecords = TABLE_OUTPUT_DEFAULT_PAGE_RECORDS;

  public $allowDelete = true;
  public $allowEdit = true;
  public $allowAppend = true;
  public $allowFilter = true;

  public $orderBy;
  public $orderByReversed = false;
  public $singlePage = false;
  public $idAlias = 'tblOutputId';
  public $readOnly = false;
  // public $allowSinglePage = false;

  public $metas;

  public function __construct($args)
  {

    // check required parameters
    // if (!isset($args['table'])) {
    //     throw new MissingParameterException('table');
    // }
    //
    // if (!isset($args['id'])) {
    //     throw new MissingParameterException('id');
    // }
    //
    // if (!isset($args['fields'])) {
    //     throw new MissingParameterException('fields');
    // }

    // assign main table name
    // $this->table = $args['table'];

    // assign table id
    // $this->id = str_replace(' ', '', $args['id']);

    $this->query = sprintf('SELECT concat(%s) FROM %s',
    $this->get_concat_id(),
    $this->table);

    if (isset($args['idValue'])) {
      $this->query .= sprintf(' WHERE %s = %s', $this->get_concat_id(), $args['idValue']);
      $this->idValue = $args['idValue'];
    }

    // assign fields
    // foreach ($args['fields'] as $field) {
    //     if (isset($field['name']) && $this->field_name_exists(trim($field['name']))) {
    //         throw new Exception(
    //             sprintf('This field name is already in use: %s', trim($field['name'])));
    //     }
    //
    //     array_push($this->fields, new TableOutputField($field));
    // }

    // all the ids have to be valid fields
    foreach (explode(',', $this->id) as $id) {
      if (!$this->field_name_exists($id)) {
        throw new Exception(sprintf('Id not set as field: %s', $id));
      }
    }

    // conditional parameters
    if (isset($args['allowDelete'])) {
      $this->allowDelete = $args['allowDelete'];
    }

    if (isset($args['allowEdit'])) {
      $this->allowEdit = $args['allowEdit'];
    }

    if (isset($args['allowAppend'])) {
      $this->allowAppend = $args['allowAppend'];
    }

    if (isset($args['pageRecords'])) {
      $this->pageRecords = $args['pageRecords'];
    }

    if (isset($args['allowFilter'])) {
      $this->allowFilter = $args['allowFilter'];
    }

    if (isset($args['orderBy'])) {
      $this->orderBy = $args['orderBy'];
    }

    if (isset($args['orderByReversed'])) {
      $this->orderByReversed = $args['orderByReversed'];
    }

    if (isset($args['singlePage'])) {
      $this->singlePage = $args['singlePage'];
    }

    if (isset($args['pageRecords'])) {
      $this->pageRecords = $args['pageRecords'];
    }

    if (isset($args['allowSinglePage'])) {
      $this->allowSinglePage = $args['allowSinglePage'];
    }

    if (isset($args['readOnly']) && $args['readOnly']) {
      $this->readOnly = true;

      if (isset($args['allowDelete'])) {
        throw new Exception('"allowDelete" has no effect, if "readOnly" is set.');
      }

      if (isset($args['allowEdit'])) {
        throw new Exception('"allowEdit" has no effect, if "readOnly" is set.');
      }

      if (isset($args['allowAppend'])) {
        throw new Exception('"allowAppend" has no effect, if "readOnly" is set.');
      }

      $this->allowDelete = false;
      $this->allowEdit = false;
      $this->allowAppend = false;
    }

    if (isset($args['orientation'])) {
      switch ($args['orientation']) {
        case 'vertical':
        case 'horizontal':
        $this->orientation = $args['orientation'];
        break;
        default:
        throw new Exception(sprintf('Invalid orientation provided: "%s". Valid values are "vertical" and "horizontal".', $args['orientation']));
      }
    }
  }

  public function get_concat_id()
  {
    return str_replace(',', ',\',\',', $this->id);
  }

  public function can_select()
  {
    return $this->allowDelete || $this->allowEdit;
  }
  
  public function get_field_names()
  {
    $arr = array();

    foreach ($this->fields as $field) {
      array_push($arr, $field->name);
    }

    return $arr;
  }

  public function assign_metas_to_fields()
  {

    // loop metas
    foreach ($this->metas as $meta) {

      // this meta is not for a user-defined field
      if ($meta['name'] == $this->idAlias) {
        continue;
      }

      $field = $this->get_field_by_name($meta['name']);

      $field->autoIncrement = in_array('auto_increment', $meta['flags']);
      $field->notNull = in_array('not_null', $meta['flags']);

      if (!$field->notNull) {
        continue;
      }
      if (!$this->allowAppend) {
        continue;
      }
      if ($field->predefined) {
        continue;
      }
      if (isset($field->header)) {
        continue;
      }

      $error = sprintf('The field "%s" cannot be null. Set "allowAppend" to false or use a "predefined" value or a "header" for this field.', $field->name);

      if ($field->autoIncrement) {
        $error = sprintf('%s This is an "auto_increment" field. It is recommended to use a "predefined" value of "DEFAULT".', $error);
      }

      throw new Exception($error);
    }

    return true;
  }

  /**
  * Get the table-output field type from native SQL.
  * @param  String $type the SQL type
  * @return String       the table-output type
  */
  function get_table_output_type($type)
  {
    foreach (TABLE_OUTPUT_TYPES as $key => $tableOutputType) {
      if (in_array($type, $tableOutputType)) return $key;
    }
    throw new Exception(sprintf('The native field type %s was not defined as a table-output-type in the configuration file.', $type));
  }

  public function assign_filters_to_fields($renderer)
  {
    foreach ($this->fields as $field) {
      if ($field->independent) continue;

      if (!isset($field->type)) {
        throw new Exception(sprintf('No field type assigned to %s', $field->name));
      }

      // The field already has a filter.
      // This is the case, when the table-output
      // object was created from a session.
      if (isset($field->filter)) continue;

      $field->filter = $renderer->create_filter($field);
    }

    return true;
  }

  public function get_field_by_name($name)
  {
    foreach ($this->fields as $field) {
      if ($field->name == $name) {
        return $field;
      }
    }

    throw new Exception(
    sprintf('Field could not be found: %s', $name));

    return;
  }
}
