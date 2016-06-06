<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;


class TableOutputBoolFilter extends TableOutputFilter
{
    public $on = [
        'header' => TABLE_OUTPUT_FILTER_BOOL_IS,
        'value' => 0,
    ];

    /**
     * Get this filter.
     * @return array
     */
    public function get(): array
    {
        return array(
            'on' => $this->on,
        );
    }

    /**
     * Get this filter values.
     * @return array
     */
    public function get_values(): array
    {
        return array(
            'on' => $this->on['value'],
        );
    }

    /**
     * Get this filter SQL.
     * @param string $fieldPath
     * @return string
     */
    public function get_sql(string $fieldPath): string
    {
        return sprintf('%s = :%s', $this->field->name, 'on_' . $fieldPath);
    }
}