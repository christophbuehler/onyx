<?php

class TableOutputRenderer
{
    private $singlePage = false;
    private $tableOutput;

    public function __construct(TableOutput $tableOutput)
    {
        $this->tableOutput = $tableOutput;
    }

    public function render_table()
    {
        // get the page buttons
        $pageButtons = $this->get_page_buttons();

        // return the table-output component
        return sprintf("<table-output id='%s' orientation='%s' structure='%s' number-of-records='%s' print-page-buttons='%s' page-buttons='%s' url='%s'></table-output>",
          $this->tableOutput->id,
          $this->tableOutput->config->orientation,
          json_encode($this->get_structure()),
          $pageButtons['total'],
          'true',
          json_encode($pageButtons['buttons']),
          $_GET['url']
        );
    }

    /**
     * Get the table-output field structure.
     * @return Array the structure
     */
    public function get_structure() {
      $structure = [];
      $sFields = $this->tableOutput->rootTable->fields;

      // loop fields
      foreach ($sFields as $sField) {
        $field = $this->parse_field($sField);
        array_push($structure, $field);
      }

      return $structure;
    }

    private function parse_field(TableOutputField $field): array
    {
      $pField = [
        'type' => $field->type,
        'header' => $field->header,
        'name' => $field->name,
        'required' => $field->notNull,
        'independent' => $field->independent,
        'hidden' => !isset($field->header),
        'suggestion' => $field->suggestion,
        'link' => [],
        'filter' => isset($field->filter) ? $field->filter->parse($this->tableOutput->parser, $field) : [],
      ];

      // the field is predefined
      if (isset($field->predefined)) {
          $pField['type'] = 'hidden';
          $pField['disabled'] = true;

          // set the predefined value
          $pField['value'] = $field->predefined;
      }

      // field has link
      if (isset($field->link)) {
        $lFields = [];

        // loop link fields
        foreach ($field->link->fields as $lField) {

          // parse link fields
          array_push($lFields, $this->parse_field($lField));
        }

        // set link
        $pField['link'] = array(
          'id' => sprintf('link?f=%s&id=%s', $field->name, $this->tableOutput->id),
          'fields' => $lFields,
        );

        // this field links to another table
        if (isset($field->link->reference)) {
            $pField['link']['reference'] = $field->link->reference;
        }
      }

      return $pField;
    }

    /**
     * Get the records of a single page.
     * @param  integer $page the page
     * @return Array         the page records
     */
    public function get_records(int $page = 0, array $filterValues = [], string $orderBy = '', bool $orderByReversed = false) {
      $records = [];

      // assign filter values
      if ($this->tableOutput->config->allowFilter) {
        foreach ($filterValues as $key => $filter) {
          $this->tableOutput->set_filter($key, $filter);
        }
      }

      $query = $this->tableOutput->queryHandler->select(
        $page * $this->tableOutput->config->pageRecords,
        $this->tableOutput->config->pageRecords,
        $orderBy,
        $orderByReversed);

      // prepare query
      $sth = $this->tableOutput->db->prepare($query);

      // try to get the records
      if (!$sth->execute($this->tableOutput->rootTable->filterHandler->get_values())) {
        return [
          'code' => 1,
          'msg' => $this->tableOutput->parseSQLError($sth->errorInfo()),
        ];
      }

      while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {

        // parse row and add it to records
        array_push($records, $this->evaluate_table_row($row));
      }

      return $records;
    }

    /**
     * Converts database values to the required format.
     * @param  Array $row the database row
     * @return Array  	  the row for a table-output component
     */
    private function evaluate_table_row($row)
    {
      $tblOutput = $this->tableOutput;

      // create table-output row
      return (new TableOutputRow($row[$tblOutput->rootTable->idAlias], $row, $tblOutput->rootTable->fields, $tblOutput->parser))
        ->parse();
    }

    public function create_filter($field)
    {
        switch ($field->type) {
            case 'bool':
                return new TableOutputBoolFilter($field);
            case 'date':
                return new TableOutputDateFilter($field);
            case 'text':
            case 'email':
                return new TableOutputStringFilter($field);
            case 'stars':
            case 'number':
                return new TableOutputNumberFilter($field);
            default:
                throw new Exception(
                    sprintf('Invalid filter type provided: %s. Valid types are "bool", "date", "text" and "number".', $field->type));
        }
    }

    public function get_page_buttons(int $page = 0, array $filterValues = [], string $orderBy = '', bool $orderByReversed = false): array
    {
        $arr = array(
          'buttons' => [],
        );

        $orderBy = $this->tableOutput->check_order_by($orderBy);
        $orderByField = $this->tableOutput->rootTable->get_field_by_name($orderBy);

        $h = 1;
        $sth = $this->tableOutput->db->prepare($this->tableOutput->queryHandler->select(0, 999999999, $orderBy, $orderByReversed));

        $sth->execute($this->tableOutput->rootTable->filterHandler->get_values());
        $rows = $sth->fetchAll();

        for ($i = 0; $i < $sth->rowCount(); $i += $this->tableOutput->config->pageRecords) {
          array_push($arr['buttons'], [
            'num' => $h,
            'from' => $this->tableOutput->parser->get_reverse_link_value($orderByField, $rows[$i][$orderBy]),
            'to' => $this->tableOutput->parser->get_reverse_link_value($orderByField, $rows[min($i + $this->tableOutput->config->pageRecords, $sth->rowCount()) - 1][$orderBy]),
          ]);
          ++$h;
        }

        $arr['total'] = $sth->rowCount();

        return $arr;
    }
}
