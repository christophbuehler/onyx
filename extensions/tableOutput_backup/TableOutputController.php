<?php

class TableOutputController
{
  public $tableOutputs = array();
  private $sessionPrefix = 'tblOutput';
  private $db;

  public function __construct(Database $db)
  {
    $this->db = $db;
  }

  /**
  * Register a table-output from the session.
  * @param  String $id   the table-output identifier
  * @param  Array  $args the arguments
  * @return TableOutput  the registered table-output
  */
  public function register(string $id, array $args): TableOutput
  {
    return $this->registerTable($id, new TableOutput(
      new TableOutputConfig($args[0]),
      $this->get_table($args[1])));
  }

  /**
   * Create a table-output table.
   * @param  array $args       the table arguments
   * @return TableOutputTable  the table
   */
  private function get_table($args): TableOutputTable
  {
    $fields = [];

    // check if field name is already in use
    foreach ($args['fields'] as $field) {
      if (isset($field['name']) && count(array_filter($fields, function($filterField) {
        return $filterField->name == trim($field['name']);
      })) > 0) throw new Exception(sprintf('This field name is already in use: %s', trim($field['name'])));
      array_push($fields, new TableOutputField($field));
    }

    return = new TableOutputTable($args['name'], $args['id'], $fields);
  },

  /**
  * Register a table-output by its configuration.
  * @param  String            $id     the table-output identifier
  * @param  TableOutputConfig $config the table-output configuration
  * @param  TableOutputTable $table   the table-output table
  * @return TableOutput               the registered table-output
  */
  public function registerTable(String $id, TableOuptut $tableOutput): TableOutput
  {
    array_push($this->tableOutputs, $tableOutput);
    $tableOutput->register($this->db, $id, $this);
    $this->save_to_session($tableOutput);
    return $tableOutput;
  }

  private function create_from_session($id)
  {
    return $this->registerTable($id, unserialize($_SESSION[$this->sessionPrefix . $id]));
  }

  /**
  * Save table-output configuration to session.
  * @param  Object $table table-output object
  * @return bool          saving was successful
  */
  public function save_to_session($tableOutput)
  {
    $_SESSION[$this->sessionPrefix . $tableOutput->id] = serialize($tableOutput);
    return true;
  }

  public function remote_get_page_buttons()
  {
    $tableOutput = $this->create_from_session($_POST['id']);
    return $tableOutput->renderer->get_page_buttons();
  }

  /**
  * Get a table-output by its ID.
  * @param  string $id  the id
  * @return TableOutput the table-output
  */
  public function get_table_by_id(string $id): TableOutput
  {
    foreach ($this->tableOutputs as $tableOutput)
    if ($tableOutput->id == $id) return $tableOutput;
    return null;
  }

  /**
  * Get page records.
  * @return Array success indicator
  */
  public function remote_get_records()
  {
    $tableOutput = $this->create_from_session($_POST['id']);
    return $tableOutput->renderer->get_records($_POST['startPage']);
  }

  /**
  * Set the table order by.
  * @return Array success indicator
  */
  public function remote_set_order_by()
  {
    $tableOutput = $this->create_from_session($_POST['id']);
    return $tableOutput->set_order_by($_POST['orderBy'], $_POST['orderByReversed']);
  }

  // public function remote_get_page()
  // {
  //     $tableOutput = $this->create_from_session($_POST['id']);
  //     if (!isset($_POST['startPage'])) {
  //         throw new Exception(
  //             new MissingParameterError('start'));
  //     }
  //     $args = array(
  //         'start' => $_POST['startPage'] * $tableOutput->config->pageRecords,
  //         'limit' => $tableOutput->config->pageRecords,
  //     );
  //     if (isset($_POST['orderBy'])) {
  //         $args['orderBy'] = $_POST['orderBy'];
  //     }
  //
  //     if (isset($_POST['filter'])) {
  //         $args['filter'] = $_POST['filter'];
  //     }
  //
  //     if (isset($_POST['printPageActions'])) {
  //         $args['printPageActions'] = $_POST['printPageActions'];
  //     }
  //
  //     if (isset($_POST['printTableHead'])) {
  //         $args['printTableHead'] = $_POST['printTableHead'];
  //     }
  //
  //     return $tableOutput->get_page($args);
  // }

  public function remote_delete()
  {
    $tableOutput = $this->create_from_session($_POST['id']);

    if (!isset($_POST['rowId'])) {
      throw new Exception(
      new MissingParameterError('rowId'));
    }
    return $tableOutput->delete($_POST['rowId']);
  }

  /**
  * Set the filter values for one particular field.
  */
  public function remote_set_filter()
  {
    // get table-output from session
    $tableOutput = $this->create_from_session($_POST['id']);
    return $tableOutput->set_filter($_POST['fieldName'], $_POST['filterValues']);
  }

  // public function remote_get_filter()
  // {
  //     $tableOutput = $this->create_from_session($_POST['id']);
  //     if (!isset($_POST['field'])) {
  //         throw new Exception(
  //             new MissingParameterError('field'));
  //     }
  //
  //     return $tableOutput->filterHandler->get(
  //         $tableOutput->config->get_field_by_name($_POST['field']));
  // }

  public function remote_insert()
  {
    $tableOutput = $this->create_from_session($_POST['id']);
    if (!isset($_POST['values'])) {
      throw new Exception(
      new MissingParameterError('values'));
    }
    return $tableOutput->insert($_POST['values']);
  }

  public function remote_edit()
  {
    $tableOutput = $this->create_from_session($_POST['id']);
    if (!isset($_POST['rowId'])) {
      throw new Exception(
      new MissingParameterError('rowId'));
    }

    if (!isset($_POST['values'])) {
      throw new Exception(
      new MissingParameterError('values'));
    }

    try {
      return $tableOutput->edit($_POST['rowId'], $_POST['values']);
    } catch (SQLException $e) {
      return array(
        'code' => 1,
        'msg' => $e->getMessage(),
      );
    }
  }

  /**
  * Called for auto-completion of link fields.
  *
  * @return Array a list of links
  */
  public function remote_link()
  {
    $tableOutput = $this->create_from_session($_GET['id']);

    if (isset($_POST['reverse'])) {
      return $tableOutput->parser->link_text($_GET['f'], $_POST['id']);
    }

    return $tableOutput->parser->link($_GET['f'], $_GET['l']);
  }

  public function remote_get_new_fields()
  {
    $tableOutput = $this->create_from_session($_POST['id']);
    return $tableOutput->get_new_fields();
  }
}
