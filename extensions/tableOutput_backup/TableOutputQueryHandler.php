<?php

class TableOutputQueryHandler
{
    private $tableOutput;

    public function __construct($tableOutput)
    {
        $this->tableOutput = $tableOutput;
    }

    public function select($start, $limit, $withoutFilter = false)
    {
        if (!$this->tableOutput->config->allowFilter) {
            $withoutFilter = true;
        }

        $orderBy = $this->tableOutput->config->orderBy;
        if ($this->tableOutput->config->orderByReversed) $orderBy .= ' DESC';

        return sprintf('SELECT %s FROM %s WHERE CONCAT(%s) IN (%s) %s %s LIMIT %s, %s',
            $this->get_field_string(),
            $this->tableOutput->config->table,
            $this->tableOutput->config->get_concat_id(),
            $this->tableOutput->config->query,
            ($withoutFilter ? '' : $this->filter()),
            (($orderBy == '') ? '' : ' ORDER BY ' . $orderBy),
            ($this->tableOutput->config->singlePage ? 0 : $start),
            ($this->tableOutput->config->singlePage ? $this->tableOutput->config->pageRecords : $limit)
        );
    }

    /**
     * Get the filter SQL string.
     * @return String the filter string
     */
    private function filter()
    {
      $filter = '';

      // loop fields
      foreach ($this->tableOutput->config->fields as $field) {

        // the filter for this field is not applied
        if (!isset($field->filter) || !$field->filter->isApplied) continue;

        // this field does not link to another table
        if (!isset($field->link)) {

          // add filter
          $filter .= sprintf(' AND %s', $field->filter->get_sql($field->name));
          continue;
        }

        // add filter
        $filter .= sprintf(' AND %s IN (SELECT %s FROM %s WHERE %s)',
          $field->link->fieldName, $field->link->id, $field->link->table, $field->filter->get_sql($field->link->text));
      }

      return $filter;
    }

    // public function filter()
    // {
    //     $metas = $this->tableOutput->get_metas();
    //     $str = '';
    //
    //     if (!$this->tableOutput->config->allowFilter) {
    //         return '';
    //     }
    //
    //     foreach ($this->tableOutput->config->fields as $field) {
    //
    //         // this field does not have a filter
    //         if (!isset($field->filter)) {
    //             continue;
    //         }
    //
    //         // the filter is not applied
    //         if (!$field->filter->isApplied) {
    //             continue;
    //         }
    //
    //         $str .= $field->filter->get_sql();
    //     }
    //
    //     return $str;
    // }

    public function get_field_string()
    {
        $arr = array(
            sprintf('concat(%s) AS %s',
                $this->tableOutput->config->get_concat_id(),
                $this->tableOutput->config->idAlias), );

        foreach ($this->tableOutput->config->fields as $field) {
            if ($field->independent) continue;
            array_push($arr, $field->name);
        }

        return implode(',', $arr);
    }

    public function delete()
    {
        return sprintf('DELETE FROM %s WHERE %s = :id', $this->tableOutput->config->table, $this->tableOutput->config->id);
    }

    public function insert()
    {
        $str = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->tableOutput->config->table,
            implode(',', $this->tableOutput->config->get_field_names()),
            ':'.implode(',:', $this->tableOutput->config->get_field_names()));

        return  $str;
    }

    public function update()
    {
        $set = '';
        foreach ($this->tableOutput->config->fields as $field) {
			if ($field->name == $this->tableOutput->config->id) continue;

            $set .= $field->name.'=:'.$field->name.',';
        }

		$set = rtrim($set, ",");

        $str = sprintf('UPDATE %s SET %s WHERE %s = :id',
            $this->tableOutput->config->table,
            $set,
            $this->tableOutput->config->id);

        return  $str;
    }
}
