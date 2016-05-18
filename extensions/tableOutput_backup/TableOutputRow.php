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
            $this->parser->get_link_value($sField, null, $row))
        ]);
        continue;
      }

      // add field
      array_push($this->fields, [

        // the original value
        'value' => $row[$sField->name],

        // the validated field content
        'content' => $this->parser->validate_content(
          $sField->type,
          $this->parser->get_link_value($sField, $row[$sField->name], $row))
      ]);
    }
  }

  public function parse()
  {
    return [
      'id' => $this->id,
      'fields' => $this->fields
    ];
  }
}

?>
