<?php

namespace Onyx\Extensions\TableOutput\Controllers;

use MissingParameterException;
use Onyx\Extensions\TableOutput\Exceptions\SQLException;
use Onyx\Extensions\TableOutput\TableOutput;
use Onyx\Http\JSONResponse;
use Onyx\Libs\Controller;
use Onyx\Libs\Database;
use Onyx\Libs\User;

class TableOutputViewController extends Controller
{
    private $tableOutputController;
    public $allowXHR = true;

    function __construct(Database $db, User $user)
    {
        parent::__construct($db, $user);
        $this->tableOutputController = new TableOutputController($this->db);
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
        return $this->tableOutputController->register($id, [$rootTableArgs, $configArgs]);
    }

    public function compose()
    {
    }

    /**
     * @param string $id
     * @return array|bool
     */
    public function remote_get_structure(string $id)
    {
        if (!$this->allowXHR) return false;

        return $this->tableOutputController->get_table_by_id($id)
            ->renderer
            ->get_structure();
    }

    /**
     * @param string $id
     * @param int $page
     * @param array $filter
     * @param string $orderBy
     * @param bool $orderByReversed
     * @return JSONResponse
     */
    public function remote_get_records(string $id, int $page, array $filter = [], string $orderBy = '', bool $orderByReversed = false): JSONResponse
    {
        $params = [$page, $filter, $orderBy, $orderByReversed];

        $tableOutput = $this->tableOutputController->get_table_by_id($id);
        return new JSONResponse([
            'records' => call_user_func_array([$tableOutput->renderer, 'get_records'], $params),
            'pageButtons' => call_user_func_array([$tableOutput->renderer, 'get_page_buttons'], $params),
        ]);
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

    /**
     * @param string $id
     * @param int $rowId
     * @param array $values
     * @return array
     * @throws \Exception
     */
    public function remote_post_edit(string $id, int $rowId, array $values)
    {
        $tableOutput = $this->tableOutputController->get_table_by_id($id);

        try {
            return $tableOutput->edit($rowId, $values);
        } catch (SQLException $e) {
            return array(
                'code' => 1,
                'msg' => $e->getMessage(),
            );
        }
    }
    
    /**
     * @param string $id
     * @param string $f
     * @param string|null $v
     * @param string|null $l
     * @return array|string
     * @throws MissingParameterException
     * @throws SQLException
     * @throws \Exception
     */
    public function remote_get_link(string $id, string $f = '', int $reverse = null, string $v = null, string $l = null)
    {
        $tableOutput = $this->tableOutputController->get_table_by_id($id);

        // get the link content from an id
        if ($reverse === null) {
            if (!isset($v)) throw new MissingParameterException('f');
            return $tableOutput->parser->reverse_link($tableOutput->rootTable->get_field_by_name($f), $v);
        }

        // get proposals, that match a string (l)
        if (!isset($l)) throw new MissingParameterException('l');
        return $tableOutput->parser->link($f, $l);
    }
}

?>
