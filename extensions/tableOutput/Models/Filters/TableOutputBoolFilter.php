<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 04.06.2016
 * Time: 23:45
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;


class TableOutputBoolFilter extends TableOutputFilter
{
    public $on = [
        'header' => TABLE_OUTPUT_FILTER_BOOL_IS,
        'value' => 0,
    ];

    public function get(): array
    {
        return array(
            'on' => $this->on,
        );
    }

    public function get_values(): array
    {
        return array(
            'on' => $this->on['value'],
        );
    }

    public function get_sql(string $fieldPath): string
    {
        return sprintf('%s = :%s', $this->field->name, 'on_' . $fieldPath);
    }
}