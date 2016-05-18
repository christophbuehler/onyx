<?php

class TableOutputRow
{
  private $id;
  private $fields = [];
  private $fieldDefinitions;
  private $parser;

  public function __construct($id, $row, $fieldDefinitions, $parser)
  {
    $this->id = $id;
    $this->fieldDefinitions = $fieldDefinitions;
    $this->parser = $parser;

    $this->add_row($row);
  }

  /**
   * Parse a field and add it to this row.
   * @param string $field the field value, directly from the database
   */
  private function add_row($row)
  {

    // loop structure fields
    foreach ($this->fieldDefinitions as $sField) {

      // independent field
      if ($sField->independent) {

        // add field
        array_push($this->fields, [

          // the original value
          'value' => 1,

          // the validated field content
          'content' => $this->parser->validate_content(
            $sField->type,
            $this->parser->get_reverse_link_value($sField, $row[$sField->name]))
        ]);
        continue;
      }

      // add field
      array_push($this->fields, $this->get_field_values($sField, $row));
    }
  }

  private function get_field_values($field, $row): array
  {
    $arr = [

      // the original value
      'value' => $row[$field->name],

      // the validated field content
      'content' => $this->parser->validate_content(
        $field->type,
        $row[$field->name]),

      'fields' => [],
    ];

    if (!isset($field->link)) return $arr;

    $fields = [];

    $arr['content'] = $this->parser->get_reverse_link_value($field, $row[$field->name]);

    $linkValues = $this->parser->get_link_values($field->link, $row[$field->name] ?? '');
    foreach ($field->link->fields as $lField) {
      array_push($fields, $this->get_field_values($lField, $linkValues));
    }

    $arr['fields'] = $fields;
    return $arr;
  }

  public function parse()
  {
    return [
      'id' => $this->id,
      'fields' => $this->fields
    ];
  }

  public function parse_autocomplete()
  {
    $fieldValues = [];
    foreach ($this->fields as $field) {
        array_push($fieldValues, $field['content'] ?? '');
    }

    return [
      'value' => $this->id,
      'content' => $fieldValues
    ];
  }
}

?>
