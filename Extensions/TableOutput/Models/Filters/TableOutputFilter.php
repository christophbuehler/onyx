<?php

namespace Onyx\Extensions\TableOutput\Models\Filters;

abstract class TableOutputFilter implements iTableOutputFilter
{
    public $isApplied = false;
    public $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * Get filter fields.
     * @param object $parser the parser object
     * @return array the filter fields
     */
    public function parse($parser, $field)
    {
        $structure = [];

        // loop filter fields
        foreach ($this->get() as $key => $value) {

            // append filter field to array
            array_push($structure, array_merge(
                $value, [
                    'type' => $field->type,
                    'name' => $key,
                    'content' => $parser->get_reverse_link_value($field, $value['value']),
                ]
            ));
        }

        return [
            'isApplied' => $this->isApplied,
            'structure' => $structure,
        ];
    }
}