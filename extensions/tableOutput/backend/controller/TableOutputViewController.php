<?php

class TableOutputViewController extends Controller
{
  private $tableOutputController;
  public $allowXHR = true;

  function __construct(Smarty $smarty, Model $model, array $pathArray, Controller $parent = null)
  {
    parent::__construct($smarty, $model, $pathArray, $parent);

    $this->tableOutputController = new TableOutputController($this->model->db);
    $this->compose();
  }

  /**
   * Show the table by its ID.
   * @param  string $id table-output ID.
   * @return string     the table-output html
   */
  public function show(string $id): string
  {
    return $this->tableOutputController->get_table_by_id($id)->show();
  }

  public function register(string $id, array $rootTableArgs, array $configArgs = null): TableOutput
  {

    // check for provided id
    $val = $_GET[$id] ?? null;
    if (isset($val)) {
      $args['idValue'] = $val;
    }
    return $this->tableOutputController->register($id, [ $rootTableArgs, $configArgs ]);
  }

  public function compose() { }

  public function remote_get_structure($args)
  {
    if (!$this->allowXHR) return false;
    if (!isset($args['id'])) throw new MissingParameterException('id');

    return $this->tableOutputController->get_table_by_id($args['id'])
      ->renderer
      ->get_structure();
  }

  /**
  * Get page records.
  * @return Array success indicator
  */
  public function remote_get_records($args)
  {
    if (!isset($args['id'])) throw new MissingParameterException('id');
    if (!isset($args['page'])) throw new MissingParameterException('page');

    $params = [
      $args['page'],
      $args['filter'] ?? [],
      $args['orderBy'] ?? '',
      (bool) ($args['orderByReversed'] ?? false),
    ];

    $tableOutput = $this->tableOutputController->get_table_by_id($args['id']);
    return [
      'records' => call_user_func_array([ $tableOutput->renderer, 'get_records' ], $params),
      'pageButtons' => call_user_func_array([ $tableOutput->renderer, 'get_page_buttons' ], $params),
    ];
  }

  public function remote_get_delete($args)
  {
    if (!isset($args['id'])) throw new MissingParameterException('id');
    if (!isset($args['rowId'])) throw new MissingParameterException('rowId');

    $tableOutput = $this->tableOutputController->get_table_by_id($args['id']);
    return $tableOutput->delete($args['rowId']);
  }

  public function remote_post_insert($args)
  {
    if (!isset($args['id'])) throw new MissingParameterException('id');
    if (!isset($args['values'])) throw new MissingParameterException('values');

    $tableOutput = $this->tableOutputController->get_table_by_id($args['id']);
    return $tableOutput->insert($args['values']);
  }

  public function remote_post_edit($args)
  {
    if (!isset($args['id'])) throw new MissingParameterException('id');
    if (!isset($args['rowId'])) throw new MissingParameterException('rowId');
    if (!isset($args['values'])) throw new MissingParameterException('values');

    $tableOutput = $this->tableOutputController->get_table_by_id($args['id']);

    try {
      return $tableOutput->edit($args['rowId'], $args['values']);
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
  public function remote_get_link($args)
  {
    if (!isset($args['id'])) throw new MissingParameterException('id');
    if (!isset($args['f'])) throw new MissingParameterException('f');

    $tableOutput = $this->tableOutputController->get_table_by_id($args['id']);

    // get the link content from an id
    if (isset($args['reverse'])) {
      if (!isset($args['v'])) throw new MissingParameterException('f');
      return $tableOutput->parser->reverse_link($tableOutput->rootTable->get_field_by_name($args['f']), $args['v']);
    }

    // get proposals, that match a string (l)
    if (!isset($args['l'])) throw new MissingParameterException('l');
    return $tableOutput->parser->link($args['f'], $args['l']);
  }
}

?>
