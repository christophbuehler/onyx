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
        return sprintf("<table-output id='%s' orientation='%s' structure='%s' number-of-records='%s' print-page-buttons='%s' page-buttons='%s'></table-output>",
          $this->tableOutput->id,
          $this->tableOutput->config->orientation,
          json_encode($this->get_structure()),
          $pageButtons['total'],
          'true',
          json_encode($pageButtons['buttons'])
        );
    }

    /**
     * Get the table-output field structure.
     * @return Array the structure
     */
    public function get_structure() {
      $structure = [];
      $sFields = $this->tableOutput->config->fields;

      // loop fields
      foreach ($sFields as $sField) {

        $field = [
          'type' => $sField->type,
          'header' => $sField->header,
          'name' => $sField->name,
          'required' => $sField->notNull,
          'independent' => $sField->independent,
          'hidden' => !isset($sField->header),
          'suggestion' => $sField->suggestion,
          'link' => [],
          'filter' => isset($sField->filter) ? $sField->filter->parse($this->tableOutput->parser, $sField) : [],
        ];

        // the field is predefined
        if (isset($sField->predefined)) {
            $field['type'] = 'hidden';
            $field['disabled'] = true;

            // set the predefined value
            $field['value'] = $sField->predefined;
        }

        // field has link
        if (isset($sField->link)) {
          $field['type'] = 'text';

          // set auto-completion link
          $field['link'] = array(
            'id' => sprintf('../tableOutput/link?f=%s&id=%s', $sField->name, $this->tableOutput->id),
          );

          // this field links to another table
          if (isset($sField->link->reference)) {
              $field['link']['reference'] = $sField->link->reference;
          }
        }

        array_push($structure, $field);
      }

      return $structure;
    }

    /**
     * Get the records of a single page.
     * @param  integer $page the page
     * @return Array         the page records
     */
    public function get_records($page = 0) {
      $records = [];

      $query = $this->tableOutput->queryHandler->select(
        $page * $this->tableOutput->config->pageRecords,
        $this->tableOutput->config->pageRecords);

      // prepare query
      $sth = $this->tableOutput->db->prepare($query);

      // try to get the records
      if (!$sth->execute($this->tableOutput->filterHandler->get_values())) {
        throw new SQLException('Could not get page content.', $sth);
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
      $conf = $this->tableOutput->config;

      // vertical table
      if ($this->tableOutput->config->orientation == 'vertical') {

        // create table-output row
        return (new TableOutputRow($row[$conf->idAlias], $row, $conf->fields, $this->tableOutput->parser))
          ->parse();
      }

      // TODO: horizontal table
    }

    public function show_content($query, $printPageActions, $printTableHead = true)
    {
        $this->horizontalRows = array();

        $table = sprintf('<table class=\':classestable-output%s\' data-table-output-id=\'%s\'><thead>%s</thead><tbody>',
            ($this->tableOutput->config->can_select() ? ' selectable' : ''),
            $this->tableOutput->id,
            $printTableHead ? $this->get_table_head() : '');

        // $sth = $this->tableOutput->db->prepare($query);

        // if (!$sth->execute($this->tableOutput->filterHandler->get_values())) {
        //     throw new SQLException('Could not get page content.', $sth);
        // }

        // if ($sth->rowCount() == 0) {
        //     $table .= sprintf('<div class=\'no-entries-found\'>%s</div>',  TABLE_OUTPUT_NO_ENTRIES_FOUND);
        // }

        // while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        //     $table .= $this->get_table_row($row);
        // }

        if ($this->tableOutput->config->orientation == 'horizontal') {
            foreach ($this->horizontalRows as $row) {
                $table .= '<tr>'.$row.'</tr>';
            }
        }

        if ($printTableHead) {
            $table .= '</tbody></table>';

            $addClasses = array();

            if ($this->singlePage) {
                array_push($addClasses, 'single-page');
            }

            $str = '<div class=\'table-output-header\'>';
        } else {
            $str = '';
        }

        if ($printPageActions && $this->tableOutput->config->orientation == 'vertical' && ($this->tableOutput->config->allowAppend || !$this->tableOutput->config->singlePage || $this->tableOutput->config->allowDelete)) {
            $str .= ($this->tableOutput->config->allowAppend ? "<div class='link-btn new-btn'>neu</div>" : '').'<div class="page-actions-container">'.
                ($this->tableOutput->config->allowDelete ? "<button class='btn-blue delete-btn'>L&ouml;schen</button>" : '').
                ($this->tableOutput->config->allowEdit ? "<button class='btn-blue edit-btn'>Bearbeiten</button>" : '').'</div>';
        } elseif ($printTableHead) {
            array_push($addClasses, 'no-page-actions');
        }

        $pageButtons = $this->get_page_buttons();

        if ($printTableHead) {
            $str .= sprintf('<div class=\'total-count\'><div class=\'count\'>%s</div> %s</div></div>', $pageButtons['total'], TABLE_OUTPUT_ENTRIES);
        }

        if ($printPageActions) {
            $str .= '<div class=\'table-output-content\'>';
        }

        if ($printTableHead) {
            $str .= str_replace(':classes', (count($addClasses) > 0 ? implode(' ', $addClasses).' ' : ''), $table);
        } else {
            $str .= $table;
        }

        if ($printPageActions) {
            $str .= '</div>';
        }

        if ($printPageActions && !$this->singlePage) {
            $str .= "<div class='page-nav-container'><div class='prev-btn material-icons'>navigate_before</div><div class='overflow-btns'>".$pageButtons['buttons']."</div><div class='next-btn material-icons'>navigate_next</div></div>";
        }

        return $str;
    }

    // public function get_table_row($row)
    // {
    //     $values = array();
    //
    //     /*
    //      * Vertical table view with multiple entries.
    //      */
    //     if ($this->tableOutput->config->orientation == 'vertical') {
    //         $str = sprintf('<tr data-row-id=\'%s\'>',
    //             $row[$this->tableOutput->config->idAlias]);
    //
    //         if (!$this->tableOutput->config->readOnly) {
    //             $str = sprintf('%s<td class=\'check\'><div><div class=\'square\'></div><div class=\'check-icon material-icons\'>done</div></div></td>', $str);
    //         }
    //
    //         foreach ($this->tableOutput->config->fields as $field) {
    //
    //             // // independent field - just render it
    //             // if ($field->independent) {
    //             //     $str .= sprintf('<td>%s</td>', $this->validate_content($field->type, $this->tableOutput->get_link_value($field, null, $row)));
    //             //     continue;
    //             // }
    //
    //             // $hdn = isset($field->header) ? '' : 'hidden';
    //             // $content = $this->validate_content($field->type, $this->tableOutput->get_link_value($field, $row[$field->name], $row));
    //             //
    //             // $str .= sprintf('<td class=\'%s\' data-name=\'%s\' %s data-type=\'%s\' data-value=\'%s\'>%s</td>',
    //             //     $hdn,
    //             //     $field->name,
    //             //     (($content != $row[$field->name]) ? " data-content='".$content."'" : ''),
    //             //     $field->type,
    //             //     $row[$field->name],
    //             //     $this->tableOutput->get_href_value($field, $row[$field->name], $row, $content)
    //             // );
    //         }
    //
    //         $str .= '</tr>';
    //
    //         return $str;
    //     }

        /*
         * Horizontal table view with one entry.
         */

        // $i = 0;
        //
        // foreach ($this->tableOutput->config->fields as $field) {
        //     $hdn = isset($field->header) ? '' : 'hidden';
        //     $content = $this->validate_content($field->type, $this->tableOutput->get_link_value($field, $row[$field->name], $values));
        //
        //     $str = sprintf('<td class=\'%s\' data-name=\'%s\' %s data-type=\'%s\' data-value=\'%s\'>%s</td>',
        //         $hdn,
        //         $field->name,
        //         (($content != $row[$field->name]) ? " data-content='".$content."'" : ''),
        //         $field->type,
        //         $row[$field->name],
        //         $this->tableOutput->get_href_value($field, $row[$field->name], $row, $content)
        //     );
        //
        //     $this->horizontalRows[$i + 1] .= $str;
        //     ++$i;
        // }
        //
        // return '';
    // }

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

    public function get_table_head()
    {
        $this->tableOutput->get_metas();

        // render horizontal table
        if ($this->tableOutput->config->orientation == 'horizontal') {
            $i = 0;

            foreach ($this->tableOutput->config->fields as $field) {
                $str = sprintf('<tr class="%s"><td>%s</td>', isset($field->header) ? '' : 'hidden', $field->header);
                $this->horizontalRows[$i + 1] = $str;
                ++$i;
            }

            return;
        }

        // render vertical table

        $str = "<tr class='table-head'>";
        $independentOffset = 0;

        if (!$this->tableOutput->config->readOnly) {
            $str = sprintf('%s<td></td>', $str);
        }

        foreach ($this->tableOutput->config->fields as $field) {

            // independent field - just render it
            /*if ($field->independent) {
                $str .= sprintf('<th>%s</th>', $field->header);
                continue;
            } */

            $filterStr = '';
            if ($this->tableOutput->config->allowFilter) {
                $filterStr = sprintf('<div data-active=\'%s\' class="filter material-icons">filter_list</div>',
                    $field->filter->isApplied ? 'true' : 'false');
            }

            $str .= sprintf('<th class=\'%s\'><div class=\'sticky\' data-order-by=\'%s\' data-type=\'%s\' data-display-name=\'%s\' data-name=\'%s\'><span>%s</span>%s</div></th>',
                isset($field->header) ? '' : 'hidden',
                ($this->tableOutput->config->orderBy == $field->name ? 'true' : 'false'),
                $field->type,
                $field->header || '',
                $field->name,
                $field->header,
                $filterStr
            );
        }

        $str .= '</tr>';

        return $str;
    }

    public function get_page_buttons()
    {
        $arr = array(
          'buttons' => [],
        );

        $h = 1;
        $sth = $this->tableOutput->db->prepare($this->tableOutput->queryHandler->select(0, 999999999));
        $sth->execute($this->tableOutput->filterHandler->get_values());
        $rows = $sth->fetchAll();
        $orderBy = $this->tableOutput->get_order_by();

        for ($i = 0; $i < $sth->rowCount(); $i += $this->tableOutput->config->pageRecords) {
          array_push($arr['buttons'], [
            'num' => $h,
            'from' => $this->tableOutput->parser->get_link_value($this->tableOutput->config->get_field_by_name($orderBy), $rows[$i][$orderBy]),
            'to' => $this->tableOutput->parser->get_link_value($this->tableOutput->config->get_field_by_name($orderBy), $rows[min($i + $this->tableOutput->config->pageRecords, $sth->rowCount()) - 1][$orderBy]),
          ]);
            // $arr['buttons'] .= sprintf('<div class=\'nav-btn\'><span class=\'num\'>%s</span><span class=\'desc\'><span class=\'l\'>%s</span> to <span class=\'l\'>%s</span></span></div>',
            //     $h,
            //     $this->tableOutput->parser->get_link_value($this->tableOutput->config->get_field_by_name($orderBy), $rows[$i][$orderBy]),
            //     $this->tableOutput->parser->get_link_value($this->tableOutput->config->get_field_by_name($orderBy), $rows[min($i + $this->tableOutput->config->pageRecords, $sth->rowCount()) - 1][$orderBy])
            // );

            ++$h;
        }

        $arr['total'] = $sth->rowCount();

        return $arr;
    }
}
