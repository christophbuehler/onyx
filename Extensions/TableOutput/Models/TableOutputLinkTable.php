<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models;

use Onyx\Extensions\TableOutput\TableOutput;
use Onyx\Extensions\TableOutput\Models;


class TableOutputLinkTable extends TableOutputTable
{
    public $reference;
    public $autoCompleteLimit = 10;

    /**
     * TableOutputLinkTable constructor.
     * @param array $args
     * @param TableOutput $tableOutput
     */
    function __construct(array $args, TableOutput $tableOutput)
    {
        parent::__construct($args, $tableOutput);
        $this->reference = $args['reference'] ?? null;
    }

    /**
     * Get the field name with an SQL COALESCE expression.
     * @return string
     */
    private function get_coalesce_field_names()
    {
        $fieldNames = $this->get_field_names();
        return sprintf('COALESCE(%s)',
            implode($fieldNames, ", ''), ' ', COALESCE("));
    }

    /**
     * This query returns the link text of a foreign table by its foreign key.
     * @return string
     */
    public function compose_link_query(): string
    {
        $fieldNames = [];

        foreach ($this->fields as $field)
            array_push($fieldNames, $field->name);
        
        $linkQuery = sprintf('SELECT %s FROM %s WHERE %s = :id',
            implode($fieldNames, ','), $this->name, $this->id);

        return $linkQuery;
    }
}
