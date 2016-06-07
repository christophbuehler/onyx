<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Views;

use Exception;
use Onyx\DataProviders\PDODb;
use Onyx\Extensions\TableOutput\Controllers\TableOutputController;
use Onyx\Extensions\TableOutput\Exceptions\SQLException;
use Onyx\Extensions\TableOutput\TableOutput;
use Onyx\Http\JSONResponse;
use Onyx\Http\PlainResponse;
use Onyx\Libs\Controller;
use Onyx\Libs\User;

class TableOutputViewController extends Controller
{
    private $tableOutputController;
    public $allowXHR = true;

    /**
     * TableOutputViewController constructor.
     * @param PDODb $db
     * @param User $user
     */
    function __construct(PDODb $db, User $user)
    {
        parent::__construct($db, $user);
        $this->tableOutputController = new TableOutputController($db);
        $this->compose();
    }

    /**
     * Show the table by its ID.
     * @param string $id
     * @return string
     * @throws Exception
     */
    public function show(string $id): string
    {
        return $this->tableOutputController->get_table_by_id($id)->show();
    }

    /**
     * Register a TableOutput element.
     * @param string $id
     * @param array $rootTableArgs
     * @param array|null $configArgs
     * @return TableOutput
     */
    public function register(string $id, array $rootTableArgs, array $configArgs = null): TableOutput
    {
        return $this->tableOutputController->register($id, [$rootTableArgs, $configArgs]);
    }

    /**
     * Sub class TableOutput composition.
     */
    public function compose() { }

    /**
     * Get structure endpoint.
     * @param string $id
     * @return JSONResponse
     */
    public function remote_get_structure(string $id): JSONResponse
    {
        if (!$this->allowXHR) return false;

        return new JSONResponse($this->tableOutputController
            ->get_table_by_id($id)
            ->renderer
            ->get_structure());
    }

    /**
     * Get records endpoint.
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

    /**
     * Delete endpoint.
     * @param string $id
     * @param int $rowId
     * @return JSONResponse
     */
    public function remote_get_delete(string $id, int $rowId): JSONResponse
    {
        $tableOutput = $this->tableOutputController->get_table_by_id($id);
        return new JSONResponse($tableOutput->delete($rowId));
    }

    /**
     * Insert endpoint.
     * @param string $id
     * @param array $values
     * @return JSONResponse
     * @throws \Exception
     */
    public function remote_post_insert(string $id, array $values): JSONResponse
    {
        $tableOutput = $this->tableOutputController->get_table_by_id($id);
        return new JSONResponse($tableOutput->insert($values));
    }

    /**
     * Edit endpoint.
     * @param string $id
     * @param int $rowId
     * @param array $values
     * @return PlainResponse
     * @throws \Exception
     */
    public function remote_post_edit(string $id, int $rowId, array $values): PlainResponse
    {
        $tableOutput = $this->tableOutputController->get_table_by_id($id);
        return new PlainResponse($tableOutput->edit($rowId, $values));
    }

    /**
     * Get link endpoint.
     * @param string $id
     * @param string $f
     * @param int|null $reverse
     * @param string|null $v
     * @param string|null $l
     * @return JSONResponse
     * @throws SQLException
     * @throws \Exception
     */
    public function remote_get_link(string $id, string $f = '', int $reverse = null, string $v = null, string $l = null): JSONResponse
    {
        $tableOutput = $this->tableOutputController->get_table_by_id($id);

        // get the link content from an id
        if ($reverse === null) {

            if ($v === null)
                return new JSONResponse('Missing parameter.', 400);

            return $tableOutput->parser->reverse_link($tableOutput->rootTable->get_field_by_name($f), $v);
        }

        if ($l === null)
            return new JSONResponse('Missing parameter.', 400);

        // get proposals, that match a string l
        return new JSONResponse($tableOutput->parser->link($f, $l));
    }
}

?>
