<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 04.06.2016
 * Time: 23:45
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

    public function get(): array
    {
        return array(
            'includes' => $this->includes,
            'excludes' => $this->excludes,
        );
    }

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