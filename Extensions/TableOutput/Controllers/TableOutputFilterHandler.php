<?php

namespace Onyx\Extensions\TableOutput\Controllers;

use Onyx\Extensions\TableOutput\Models\TableOutputTable;

class TableOutputFilterHandler
{
    private $rootTable;
    private $tableOutput;

    public function __construct(TableOutputTable $rootTable)
    {
        $this->rootTable = $rootTable;
    }

    /** Get all inserted filter values
     *
     */
    public function get_values()
    {
        $values = array();
        $fieldPaths = $this->rootTable->get_paths();

        foreach ($fieldPaths as $path) {
            $field = $this->rootTable->tableOutput->get_field_from_path($path, false, $this->rootTable);

            // this specific filter does not exist or is not applied
            if (!isset($field->filter) || !$field->filter->isApplied) continue;

            // apply filter values
            foreach ($field->filter->get_values() as $key => $filterField) {
                $values[':' . $key . '_' . $this->rootTable->tableOutput->path_to_pdo_parameter($path)] = $filterField;
            }
        }

        return $values;
    }

    public function get($field)
    {
        $arr = array();

        foreach ($field->filter->get() as $key => $value) {
            $args = array(
                'type' => $field->type,
                'header' => $value['header'],
                'value' => $value['value'],
                'name' => $key,
                'content' => $this->tableOutput->parser->get_autocomplete_value($field, $value['value']),
            );

            if (isset($field->link)) {
                $args['linkId'] = '../tableOutput/link?f=' . $field->name . '&id=' . $this->tableOutput->id;
            }

            array_push($arr, $args);
        }

        return $arr;
    }
}
