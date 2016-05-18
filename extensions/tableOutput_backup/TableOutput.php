<?php

class TableOutput
{
    private $controller;

    public $db;
    public $id;

    public $config;
    public $renderer;

    public $queryHandler;
    public $filterHandler;
    public $parser;

    /**
     * TableOuptut constructor.
     * @param TableOutputConfig $config the table-output configuration
     * @param TableOutputTable  $table  the table-output root table
     */
    public function __construct(TableOutputConfig $config, TableOutputTable $table)
    {
      $this->config = $config;
      $this->table = $table;

      $this->renderer = new TableOutputRenderer($this);
      $this->queryHandler = new TableOutputQueryHandler($this);
      $this->filterHandler = new TableOutputFilterHandler($this);
      $this->parser = new TableOutputParser($this);
    }

    public function register(Database $db, String $id, TableOutputController $controller)
    {
      $this->db = $db;
      $this->id = $id;
      $this->controller = $controller;
      $this->get_metas();
      $this->config->assign_filters_to_fields($this->renderer);
    }

    public function show()
    {
        return $this->renderer->render_table();
    }

    /**
     * Get the database field type of a field.
     * @param  Object $field the field object
     * @return String        the field type
     */
    public function get_field_type_meta($field)
    {
      // this field links to another table
      if (isset($field->link)) {

        // get a fresh meta value from the databases
        $sth = $this->db->prepare($field->link->compose_meta_query());
        $sth->execute();

        // assign the table-output equivalent of the database field type
        $field->type = $this->config->get_table_output_type(
          $sth->getColumnMeta(0)['native_type']);

        return true;
      }

      foreach ($this->config->metas as $meta) {

        // this meta belongs to another field
        if ($field->name != $meta['name']) continue;

        // assign the table-output equivalent of the database field type
        $field->type = $this->config->get_table_output_type(
          $meta['native_type']);

        return true;
      }

      // no metas were found for this field
      return false;
    }

    /**
     * Get the column metas of the main table.
     * @return Array a list of column metas
     */
    public function get_metas()
    {
        // metas have already been loaded
        if (isset($this->config->metas)) {
          return $this->config->metas;
        }

        // get metas
        $sth = $this->db->prepare(
            $this->queryHandler->select(0, 1, true));

        if (!$sth->execute()) {
            throw new SQLException('Could not get table metas.', $sth);
        }

        $metas = array();

        for ($i = 0; $i < $sth->columnCount(); ++$i) {
            array_push($metas, $sth->getColumnMeta($i));
        }

        $this->config->metas = $metas;

        // loop fields
        foreach($this->config->fields as $field) {

          // if no custom type was applied
          if (!isset($field->type)) {

            // get the field type
            $this->get_field_type_meta($field);
          }
        }

        $this->config->assign_metas_to_fields();

        return $metas;
    }

    /**
     * Set table order by.
     * @param String $orderBy the new order by value
     */
    public function set_order_by($orderBy, $reversed)
    {

      // set reversed
      $this->config->orderByReversed = ($reversed == 'true');

      // remove order by
      if ($orderBy == '') {
        $this->config->orderBy = '';

        // save the order by value
        $this->controller->save_to_session($this);

        return [
          "code" => 0
        ];
      }

      // loop fields
      foreach ($this->config->fields as $field) {

        // this is the wrong field
        if ($field->name != $orderBy) continue;

        $this->config->orderBy = $field->name;

        // save the order by value
        $this->controller->save_to_session($this);

        return [
          "code" => 0
        ];
      }

      return [
        "code" => 1,
        "msg" => sprintf('The provided field does not exist: %s', $orderBy)
      ];
    }

    public function get_order_by()
    {
        $this->get_metas();

        $orderBy = $this->config->orderBy;

        foreach ($this->config->metas as $meta) {
            if ($meta['name'] == $orderBy) {
                return $orderBy;
            }
        }

        return $this->config->fields[0]->name;
    }

    // public function get_filter_definitions()
    // {
    //     return (object) $this->filterHandler->get_values();
    // }

    // public function get_page($args)
    // {
    //     // check required parameters
    //     if (!isset($args['start'])) {
    //         throw new MissingParameterException('start');
    //     }
    //
    //     if (!isset($args['limit'])) {
    //         throw new MissingParameterException('limit');
    //     }
    //
    //     $start = $args['start'];
    //     $limit = $args['limit'];
    //
    //     if (isset($args['filter'])) {
    //         foreach ($args['filter'] as $key => $filterValues) {
    //             $filter = $this->config->get_field_by_name($key)->filter;
    //             $filter->isApplied = isset($filterValues['apply']) ? $filterValues['apply'] : 1;
    //
    //             foreach ($filterValues as $key => $filterValue) {
    //                 $filter->{$key}['value'] = $filterValue;
    //             }
    //         }
    //     }
    //
    //     $orderBy = $this->config->orderBy;
    //
    //     if (isset($args['orderBy'])) {
    //         $orderBy = $args['orderBy'];
    //         $this->config->orderBy = $orderBy;
    //         $this->controller->save_to_session($this);
    //     }
    //
    //     $printPageActions = isset($args['printPageActions']) && $args['printPageActions'] == 'false' ? false : true;
    //     $printTableHead = isset($args['printTableHead']) && $args['printTableHead'] == 'false' ? false : true;
    //
    //     return $this->renderer->show_content($this->queryHandler->select($start, $limit), $printPageActions, $printTableHead);
    // }

    /**
     * Set filter values for one particular field.
     * @param String $fieldName    the field name
     * @param Array $filterValues  the filter values
     */
    public function set_filter($fieldName, $values)
    {

      // get field by its name
      $field = $this->config->get_field_by_name($fieldName);

      $field->filter->isApplied = $values['isApplied'] == 'true';

      if (isset($values['fields'])) {
        foreach($values['fields'] as $key => $value) {
          $field->filter->{$key}['value'] = $value;
        }
      }

      // save the filter values
      $this->controller->save_to_session($this);

      return [
        'code' => 0,
      ];
    }

    public function delete($rowId)
    {
        // user is not allowed to delete
        if (!$this->config->allowDelete) {
            return array(
                'code' => 1,
                'msg' => 'Not allowed to delete an entry.',
            );
        }

        $sth = $this->db->prepare($this->queryHandler->delete());

        if ($sth->execute(array(
            ':id' => $rowId,
        )) && $sth->rowCount() == 1) {
            return array(
            'code' => 0,
            'msg' => 'Entry successfully deleted.',
        );
        }

        return array(
          'code' => 1,
          'msg' => 'Entry Could not be deleted.',
          'error' => $sth->errorInfo(),
        );
    }

    public function insert($fieldValues)
    {
        if (!$this->config->allowAppend) {
            return array(
                'code' => 1,
                'msg' => 'Not allowed to create an entry.',
            );
        }

        $sth = $this->db->prepare($this->queryHandler->insert());

        $valArr = array();

        foreach ($this->config->fields as $field) {
            if (isset($field->predefined)) {
              $val = $field->predefined;
            } elseif (isset($fieldValues[$field->name])) {
              $val = $fieldValues[$field->name] == '' ? null : $fieldValues[$field->name];
            } else if ($field->notNull) {
                return array(
                    'code' => 1,
                    'msg' => 'A value for the field "'.$field->name.'" is required.',
                );
            }

            $valArr[':'.$field->name] = $val;
        }

        if ($sth->execute($valArr)) {
            return array(
                'code' => 0,
                'id' => $this->db->lastInsertId(),
                'msg' => 'Entry successfully added.',
            );
        }

        return array(
            'code' => 1,
            'msg' => $sth->errorInfo(),
        );
    }

    public function edit($rowId, $fieldValues)
    {
        if (!$this->config->allowEdit) {
            throw new Exception('No edit permission.');
        }

        $sth = $this->db->prepare($this->queryHandler->update());

        $valArr = array(
          ':id' => $rowId,
        );

        foreach ($this->config->fields as $field) {
          if ($field->independent) {
            continue;
          }

          if ($field->name == $this->config->id) {
            continue;
          }

          if (isset($field->predefined)) {
            $valArr[':' . $field->name] = $field->predefined;
            continue;
          }

          // value not passed
          if (!isset($fieldValues[$field->name])) {
            return array(
              'code' => 1,
              'msg' => 'A value for the field "' . $field->name . '" is required.',
            );
          }

          $valArr[':'.$field->name] = $fieldValues[$field->name];
        }

        if (!$sth->execute($valArr)) {
          throw new SQLException('Could not update table.', $sth);
        }

        return array(
          'code' => 0,
          'msg' => 'Entry successfully updated.',
        );
    }

    /*
    get the fields for a new row
    */
    // public function get_new_fields()
    // {
    //     $this->get_metas();
    //
    //     $arr = array();
    //
    //     foreach ($this->config->fields as $field) {
    //
    //         // ignore auto increment fields
    //         if ($field->autoIncrement) {
    //             continue;
    //         }
    //
    //         // ignore predefined fields
    //         if ($field->predefined) {
    //             continue;
    //         }
    //
    //         $newField = array(
    //             'type' => $field->type,
    //             'caption' => $field->header,
    //             'name' => $field->name,
    //             'required' => $field->notNull,
    //             'suggestion' => $field->suggestion,
    //         );
    //
    //         if (isset($field->link)) {
    //             $newField['type'] = 'VAR_STRING';
    //             $newField['link'] = array(
    //               'id' => sprintf('../tableOutput/link?f=%s&id=%s', $field->name, $this->id),
    //             );
    //             if (isset($field->link->reference)) {
    //                 $newField['link']['reference'] = $field->link->reference;
    //             }
    //         }
    //
    //         if (isset($field->predefined)) {
    //             $newField['type'] = 'hidden';
    //             $newField['disabled'] = true;
    //             $newField['value'] = $field->predefined;
    //         }
    //
    //         array_push($arr, $newField);
    //     }
    //
    //     return $arr;
    // }
}
