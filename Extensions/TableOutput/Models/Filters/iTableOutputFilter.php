<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;


interface iTableOutputFilter
{
    /**
     * Get the filter SQL.
     * @param string $fieldPath
     * @return string
     */
    public function get_sql(string $fieldPath): string;

    /**
     * Get the filter value.
     * @return array
     */
    public function get_values(): array;

    /**
     *
     * @return array
     */
    public function get(): array;
}