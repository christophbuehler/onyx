<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 04.06.2016
 * Time: 23:44
 */

namespace Onyx\Extensions\TableOutput\Models\Filters;


class TableOutputNumberFilter extends TableOutputFilter
{
    public $from = array(
        'header' => TABLE_OUTPUT_FILTER_NUMBER_FROM,
        'value' => ''
    );

    public $to = array(
        'header' => TABLE_OUTPUT_FILTER_NUMBER_TO,
        'value' => ''
    );

    public function get(): array
    {
        return array(
            'from' => $this->from,
            'to' => $this->to,
        );
    }

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

    public function get(): array
    {
        return array(
            'from' => $this->from,
            'to' => $this->to,
        );
    }

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

        // var_dump($this->from);

        return implode(' AND ', $arr);
    }
}