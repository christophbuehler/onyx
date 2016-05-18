<?php

interface iTableOutputFilter
{
    public function get_sql($idAlias);
    public function get_values();
    public function get();
}

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
     *
     * @param object $parser the parser object
     *
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
            'content' => $parser->get_link_value($field->name, $value['value']),
          ]
        ));
      }

      return [
        'isApplied' => $this->isApplied,
        'structure' => $structure,
      ];
    }
}

class TableOutputBoolFilter extends TableOutputFilter
{
    public $on = [
      'header' => TABLE_OUTPUT_FILTER_BOOL_IS,
      'value' => 0,
    ];

    public function get()
    {
        return array(
          'on' => $this->on,
      );
    }

    public function get_values()
    {
      return array(
        'on' => $this->on['value'],
      );
    }

    public function get_sql($idAlias)
    {
      return sprintf('%s = :%s', $this->field->name, 'on_' . $this->field->name);
    }
}

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
    public function get()
    {
        return array(
          'includes' => $this->includes,
          'excludes' => $this->excludes,
      );
    }
    public function get_values()
    {
        $arr = array();

        if (trim($this->includes['value']) != '') {
            $arr['includes'] = '%'.$this->includes['value'].'%';
        }

        if (trim($this->excludes['value']) != '') {
            $arr['excludes'] = '%'.$this->excludes['value'].'%';
        }

        return $arr;
    }
    public function get_sql($idAlias)
    {
        $arr = [];

        if (trim($this->includes['value']) != '') {
          array_push($arr,
            sprintf('%s LIKE :%s', $idAlias, 'includes_' . $this->field->name)
          );
        }

        if (trim($this->excludes['value']) != '') {
          array_push($arr,
            sprintf('%s NOT LIKE :%s', $idAlias, 'excludes_' . $this->field->name)
          );
        }

        return implode(' AND ', $arr);
    }
}

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

    public function get()
    {
        return array(
            'from' => $this->from,
            'to' => $this->to,
        );
    }
    public function get_values()
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
    public function get_sql($idAlias)
    {
        $arr = [];

        if (trim($this->from['value']) != '') {
          array_push($arr,
            sprintf('%s >= :%s', $idAlias, 'from_' . $this->field->name)
          );
        }

        if (trim($this->to['value']) != '') {
          array_push($arr,
            sprintf('%s <= :%s', $idAlias, 'to_' . $this->field->name)
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

    public function get()
    {
        return array(
            'from' => $this->from,
            'to' => $this->to,
        );
    }
    public function get_values()
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
    public function get_sql($idAlias)
    {
      $arr = [];

      if (trim($this->from) != '') {
        array_push($arr,
          sprintf('%s >= :%s', $this->field->name, 'from_'.$this->field->name)
        );
      }

      if (trim($this->to) != '') {
        array_push($arr,
          sprintf('%s <= :%s', $this->field->name, 'to_'.$this->field->name)
        );
      }

      return implode(' AND ', $arr);
    }
}
