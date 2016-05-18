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

  public function register(string $id, array $args): TableOutput
  {

    // check for provided id
    $val = $_GET[$id] ?? null;
    if (isset($val)) {
      $args['idValue'] = $val;
    }
    return $this->tableOutputController->register($id, $args);
  }

  public function compose() { }

  public function remote_get_structure()
  {
    if (!$this->allowXHR) return false;
    $id = $_GET['id'] ?? null;
    if (!isset($id)) throw new MissingParameterException('id');
    return $this->tableOutputController->get_table_by_id($id)->renderer->get_structure();
  }
}

?>
