<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models;

use Exception;
use Onyx\Extensions\TableOutput\Controllers\TableOutputFilterHandler;
use Onyx\Extensions\TableOutput\TableOutput;

class TableOutputRootTable extends TableOutputTable
{
    public $filterHandler;

    /**
     * TableOutputRootTable constructor.
     * @param array $args
     * @param TableOutput $tableOutput
     */
    function __construct(array $args, TableOutput $tableOutput)
    {
        parent::__construct($args, $tableOutput);
        $this->filterHandler = new TableOutputFilterHandler($this);
        $this->check_ids();
    }

    /**
     * Check if all the IDs are valid fields.
     * @throws Exception
     */
    private function check_ids(): bool
    {
        foreach (explode(',', $this->id) as $id)
            if (!$this->field_name_exists($id))
                throw new Exception(sprintf('Id not set as field: %s', $id));

        return true;
    }
}
