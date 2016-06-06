<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;

class TableOutputDateFilter extends TableOutputFilter
{
    public $from = array(
        'header' => TABLE_OUTPUT_FILTER_DATE_FROM,
        'value' => '',
    );

    public $to = array(
        'header' => TABLE_OUTPUT_FILTER_DATE_TILL,
        'value' => '',
    );

    /**
     * Get this filter.
     * @return array
     */
    public function get(): array
    {
        return array(
            'from' => $this->from,
            'to' => $this->to,
        );
    }

    /**
     * Get this filter values.
     * @return array
     */
    public function get_values(): array
    {
        $arr = array();

        if (trim($this->from['value']) != '') {
            $arr['from'] = $this->from['value'];
        }

        if (trim($this->to['value']) != '') {
            $arr['to'] = $this->to['value'];
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

        if (trim($this->from['value']) != '') {
            array_push($arr,
                sprintf('%s >= :%s', $this->field->name, 'from_' . $fieldPath)
            );
        }

        if (trim($this->to['value']) != '') {
            array_push($arr,
                sprintf('%s <= :%s', $this->field->name, 'to_' . $fieldPath)
            );
        }

        return implode(' AND ', $arr);
    }
}