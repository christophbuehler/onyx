<?php

namespace Onyx\Extensions\TableOutput\Models;

use Onyx\Extensions\TableOutput\TableOutput;
use Onyx\Extensions\TableOutput\Models;


class TableOutputLinkTable extends TableOutputTable
{
    public $reference;
    public $autocompleteLimit = 10;

    function __construct(array $args, TableOutput $tableOutput)
    {
        parent::__construct($args, $tableOutput);
        $this->reference = $args['reference'] ?? null;
    }

    private function get_coalesce_field_names()
    {
        $fieldNames = $this->get_field_names();
        return sprintf('COALESCE(%s)',
            implode($fieldNames, ", ''), ' ', COALESCE("));
    }

    /** This query returns the link text of a foreign table by its foreign key.
     */
    public function compose_link_query(): string
    {
        $fieldNames = [];
        foreach ($this->fields as $field) {
            array_push($fieldNames, $field->name);
        }

        $linkQuery = sprintf('SELECT %s FROM %s WHERE %s = :id',
            implode($fieldNames, ','), $this->name, $this->id);

        return $linkQuery;
    }
}
