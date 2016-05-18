<?php

class TableOutputFilterHandler
{
	private $tableOutput;
	public function __construct($tableOutput)
	{
		$this->tableOutput = $tableOutput;
	}

/** Get all inserted filter values
*
*/
	public function get_values()
	{
		$metas = $this->tableOutput->get_metas();
    $values = array();

		foreach ($this->tableOutput->config->fields as $field)
		{
			if ($field->independent) continue;
			
			// filter is not activated
			if (!$field->filter->isApplied) continue;

			foreach ($field->filter->get_values() as $key => $filterField) {
				$values[':' . $key . '_' . $field->name] = $filterField;
			}
		}

    return $values;
	}

	public function get($field)
	{
		$arr = array();

    foreach ($field->filter->get() as $key => $value) {
			$args = array(
				'type' => $field->type,
				'header' => $value['header'],
				'value' => $value['value'],
				'name' => $key,
				'content' => $this->tableOutput->parser->get_autocomplete_value($field, $value['value']),
			);

			if (isset($field->link)) {
				$args['linkId'] = '../tableOutput/link?f='.$field->name.'&id='.$this->tableOutput->id;
			}

			array_push($arr, $args);
		}

		return $arr;

        /* if (isset($this->filter[$field]))
		{
            $values = $this->filter[$field];
        }
		else
		{
            $values = array();
        }

        foreach ($metas as $meta) {
            if ($meta['name'] != $field) {
                continue;
            }
            $type = $meta['native_type'];
            break;
        }

        if ($link) {
            return array(
                array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Entspricht',
                    'name' => 'is',
                    'linkId' => '../tableOutput/link?k='.$field.'&tableOutputId='.$tableOutputId,
                    'content' => isset($values['is']) ? $this->get_autocomplete_value($field, $values['is']) : '',
                    'value' => isset($values['is']) ? $values['is'] : 'NULL',
                ), array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet',
                    'name' => 'includes',
                    'value' => isset($values['includes']) ? $values['includes'] : '',
                ), array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet nicht',
                    'name' => 'excludes',
                    'value' => isset($values['excludes']) ? $values['excludes'] : '',
            ), );
        }

        $arr = array();

        array_push($arr, array(
            'type' => 'VAR_STRING',
            'caption' => 'Entspricht',
            'name' => 'is',
            'linkId' => '../tableOutput/link?a='.$field.'&tableOutputId='.$tableOutputId,
            'content' => isset($values['is']) ? $this->get_autocomplete_value($field, $values['is']) : '',
            'value' => isset($values['is']) ? $values['is'] : 'NULL',
        ));

        switch ($type) {
            case 'VAR_STRING':
                array_push($arr, array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet',
                    'name' => 'includes',
                    'value' => isset($values['includes']) ? $values['includes'] : '',
                ), array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet nicht',
                    'name' => 'excludes',
                    'value' => isset($values['excludes']) ? $values['excludes'] : '',
                ));
                break;
            case 'TINY':
                array_push($arr, array(
                    'type' => 'bool',
                    'caption' => 'Wert',
                    'name' => 'value',
                    'value' => isset($values['value']) ? $values['value'] : '',
                ));
                break;
            case 'LONG':
                array_push($arr, array(
                    'type' => 'LONG',
                    'caption' => 'Von',
                    'name' => 'from',
                    'value' => isset($values['from']) ? $values['from'] : '',
                ), array(
                    'type' => 'LONG',
                    'caption' => 'Bis',
                    'name' => 'to',
                    'value' => isset($values['to']) ? $values['to'] : '',
                ));
                break;
            case 'DATE':
                array_push($arr, array(
                    'type' => 'DATE',
                    'caption' => 'Datum von',
                    'name' => 'from',
                    'value' => isset($values['from']) ? $values['from'] : '',
                ), array(
                    'type' => 'DATE',
                    'caption' => 'Datum bis',
                    'name' => 'to',
                    'value' => isset($values['to']) ? $values['to'] : '',
                ));
                break;
            default:
                return $type;
        }

        return $arr; */
	}
}
