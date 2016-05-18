<?php

interface iTableOutputFilter
{
    public function get_sql(string $fieldPath): string;
    public function get_values(): array;
    public function get(): array;
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
