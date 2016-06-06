<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 04.06.2016
 * Time: 23:46
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;


interface iTableOutputFilter
{
    public function get_sql(string $fieldPath): string;

    public function get_values(): array;

    public function get(): array;
}