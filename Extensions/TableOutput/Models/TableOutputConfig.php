<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models;

use Exception;

class TableOutputConfig
{
    public $orientation = 'vertical';
    public $pageRecords = TABLE_OUTPUT_DEFAULT_PAGE_RECORDS;

    public $allowDelete = true;
    public $allowEdit = true;
    public $allowAppend = true;
    public $allowFilter = true;

    public $orderBy;
    public $orderByReversed = false;
    public $singlePage = false;
    public $readOnly = false;

    /**
     * TableOutputConfig constructor.
     * @param $args
     * @throws Exception
     */
    public function __construct($args)
    {
        $this->allowDelete = $args['allowDelete'] ?? null;
        $this->allowEdit = $args['allowEdit'] ?? null;
        $this->allowAppend = $args['allowAppend'] ?? null;
        $this->pageRecords = $args['pageRecords'] ?? null;
        $this->allowFilter = $args['allowFilter'] ?? null;
        $this->orderBy = $args['orderBy'] ?? null;
        $this->orderByReversed = $args['orderByReversed'] ?? null;
        $this->singlePage = $args['singlePage'] ?? null;
        $this->pageRecords = $args['pageRecords'] ?? null;
        $this->allowSinglePage = $args['allowSinglePage'] ?? null;

        if (isset($args['readOnly']) && $args['readOnly']) {
            $this->readOnly = true;

            if ($args['allowDelete'] !== null)
                throw new Exception('"allowDelete" has no effect, if "readOnly" is set.');

            if ($args['allowEdit'] !== null)
                throw new Exception('"allowEdit" has no effect, if "readOnly" is set.');

            if ($args['allowAppend'] !== null)
                throw new Exception('"allowAppend" has no effect, if "readOnly" is set.');

            $this->allowDelete = false;
            $this->allowEdit = false;
            $this->allowAppend = false;
        }

        if ($args['orientation'] === null) return;

        switch ($args['orientation']) {
            case 'vertical':
            case 'horizontal':
                $this->orientation = $args['orientation'];
                break;
            default:
                throw new Exception(sprintf('Invalid orientation provided: "%s". Valid values are "vertical" and "horizontal".', $args['orientation']));
        }
    }

    /**
     * Check if records can be selected.
     * @return bool
     */
    public function can_select()
    {
        return $this->allowDelete || $this->allowEdit;
    }
}
