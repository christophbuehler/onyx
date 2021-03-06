<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;

class TableOutputStringFilter extends TableOutputFilter
{
    public $includes = [
        'header' => TABLE_OUTPUT_FILTER_STRING_CONTAINS,
        'value' => '',
    ];

    public $excludes = array(
        'header' => TABLE_OUTPUT_FILTER_STRING_CONTAINS_NOT,
        'value' => '',
    );

    /**
     * Get this filter.
     * @return array
     */
    public function get(): array
    {
        return array(
            'includes' => $this->includes,
            'excludes' => $this->excludes,
        );
    }
    
    /**
     * Get this filter values.
     * @return array
     */
    public function get_values(): array
    {
        $arr = array();

        if (trim($this->includes['value']) != '') {
            $arr['includes'] = '%' . $this->includes['value'] . '%';
        }

        if (trim($this->excludes['value']) != '') {
            $arr['excludes'] = '%' . $this->excludes['value'] . '%';
        }

        return $arr;
    }

    /**
     * Get this filter SQL.
     * @param string $fieldPath
     * @return string
     */
    public function get_sql(string $fieldPath): string
    {
        $arr = [];

        if (trim($this->includes['value']) != '') {
            array_push($arr,
                sprintf('%s LIKE :%s', $this->field->name, 'includes_' . $fieldPath)
            );
        }

        if (trim($this->excludes['value']) != '') {
            array_push($arr,
                sprintf('%s NOT LIKE :%s', $this->field->name, 'excludes_' . $fieldPath)
            );
        }

        return implode(' AND ', $arr);
    }
}