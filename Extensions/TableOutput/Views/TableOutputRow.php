<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Views;

use Onyx\Extensions\TableOutput\Controllers\TableOutputParser;
use Onyx\Extensions\TableOutput\Models\TableOutputField;

class TableOutputRow
{
    private $id;
    private $fields = [];
    private $fieldDefinitions;
    private $parser;

    /**
     * TableOutputRow constructor.
     * @param string $id
     * @param $row
     * @param $fieldDefinitions
     * @param TableOutputParser $parser
     */
    public function __construct(string $id, $row, $fieldDefinitions, TableOutputParser $parser)
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

    /**
     * Get the TableOutput field values as an array.
     * @param TableOutputField $field
     * @param array $row
     * @return array
     * @throws \Onyx\Extensions\TableOutput\Exceptions\SQLException
     */
    private function get_field_values(TableOutputField $field, array $row): array
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

        foreach ($field->link->fields as $lField)
            array_push($fields, $this->get_field_values($lField, $linkValues));

        $arr['fields'] = $fields;
        return $arr;
    }

    /**
     * Parse this row.
     * @return array
     */
    public function parse(): array
    {
        return [
            'id' => $this->id,
            'fields' => $this->fields
        ];
    }

    /**
     * Parse for auto completion.
     * @return array
     */
    public function parse_auto_complete(): array
    {
        $fieldValues = [];
        foreach ($this->fields as $field)
            array_push($fieldValues, $field['content'] ?? '');

        return [
            'value' => $this->id,
            'content' => $fieldValues
        ];
    }
}

?>
