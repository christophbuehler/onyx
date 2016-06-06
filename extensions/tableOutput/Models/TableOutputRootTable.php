<?php

namespace Onyx\Extensions\TableOutput\Models;

use Exception;
use Onyx\Extensions\TableOutput\Controllers\TableOutputFilterHandler;
use Onyx\Extensions\TableOutput\TableOutput;

class TableOutputRootTable extends TableOutputTable
{
    public $filterHandler;

    function __construct(array $args, TableOutput $tableOutput)
    {
        parent::__construct($args, $tableOutput);
        $this->filterHandler = new TableOutputFilterHandler($this);
        $this->check_ids();
    }

    private function check_ids()
    {

        // all the ids have to be valid fields
        foreach (explode(',', $this->id) as $id) {
            if (!$this->field_name_exists($id)) {
                throw new Exception(sprintf('Id not set as field: %s', $id));
            }
        }
    }
}
