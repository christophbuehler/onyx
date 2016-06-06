<?php

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

    public function __construct($args)
    {
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

    public function can_select()
    {
        return $this->allowDelete || $this->allowEdit;
    }
}
