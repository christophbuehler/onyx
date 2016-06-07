<?php

namespace Onyx\Extensions\TableOutput;

require_once 'Langs/de.php';
require_once 'Onyx/Extensions/TableOutput/config.php';

use Exception;
use Onyx\DataProviders\PDODb;
use Onyx\Extensions\TableOutput\Controllers\TableOutputController;
use Onyx\Extensions\TableOutput\Controllers\TableOutputParser;
use Onyx\Extensions\TableOutput\Models\TableOutputConfig;
use Onyx\Extensions\TableOutput\Models\TableOutputField;
use Onyx\Extensions\TableOutput\Models\TableOutputQueryHandler;
use Onyx\Extensions\TableOutput\Models\TableOutputRootTable;
use Onyx\Extensions\TableOutput\Models\TableOutputTable;
use Onyx\Extensions\TableOutput\Views\TableOutputRenderer;

/**
 * TableOutput is an Onyx Extension for creating dynamic data tables
 * out of a MySQL data source.
 */
class TableOutput
{
    private $controller;

    public $db;
    public $id;

    public $rootTable;
    public $config;

    public $renderer;
    public $queryHandler;
    public $parser;

    /**
     * TableOuptut constructor.
     * @param PDODb $db
     * @param String $id
     * @param TableOutputController $controller
     * @param array $rootTableArgs
     * @param TableOutputConfig $config the table-output configuration
     * @throws Exception
     * @internal param TableOutputTable $table the table-output root table
     */
    public function __construct(PDODb $db, String $id, TableOutputController $controller, array $rootTableArgs, TableOutputConfig $config)
    {
        $this->db = $db;
        $this->id = $id;
        $this->controller = $controller;
        $this->config = $config;

        $this->queryHandler = new TableOutputQueryHandler($this);
        $this->rootTable = new TableOutputRootTable($rootTableArgs, $this);
        $this->renderer = new TableOutputRenderer($this);

        $this->parser = new TableOutputParser($this);

        $this->rootTable->assign_filters_to_fields($this->renderer);
    }

    /**
     * Display the TableOutput table.
     * @return string
     */
    public function show()
    {
        return $this->renderer->render_table();
    }

    /**
     * Get the order by value.
     * @param  string
     * @return string
     */
    public function check_order_by($orderBy): string
    {

        // check if the order by field is valid
        foreach ($this->rootTable->fields as $field) {
            if ($field->independent) continue;
            if ($field->name == $orderBy) {
                return $orderBy;
            }
        }

        // get the first valid order by field
        foreach ($this->rootTable->fields as $field) {
            if ($field->independent) continue;
            if ($field->type == 'hidden') continue;
            if (!isset($field->header)) continue;
            return $field->name;
        }

        return '';
    }

    /**
     * Set filter values for one particular field.
     * @param string $path
     * @param array $values
     * @return bool
     * @throws Exception
     */
    public function set_filter(string $path, array $values): bool
    {

        // get field by its name
        $field = $this->get_field_from_path($path);

        $field->filter->isApplied = $values['isApplied'] == 'true';

        if (isset($values['fields'])) {
            foreach ($values['fields'] as $key => $value) {
                $field->filter->{$key}['value'] = $value;
            }
        }

        return true;
    }

    /**
     * Get a TableOutputField object from the relational construction path.
     * @param string $path
     * @param bool $reverse
     * @param TableOutputTable|null $table
     * @return TableOutputField
     * @throws Exception
     */
    public function get_field_from_path(string $path, $reverse = true, TableOutputTable $table = null): TableOutputField
    {
        $parts = explode('.', $path);
        if ($reverse) $parts = array_reverse($parts);

        $table = $table ?? $this->rootTable;

        for ($i = 0; $i < count($parts); $i++) {
            $field = $table->get_field_by_name($parts[$i]);
            if ($i == count($parts) - 1) return $field;
            if (!isset($field->link)) throw new Exception(sprintf('Invalid path: %s. No link was defined for field "%s"', $path, $field->name));
            $table = $field->link;
        }
    }

    /**
     * Get the field equivalent from a path of string entities.
     * @param string $path
     * @param TableOutputTable $table
     * @param bool $reverse
     * @return array
     * @throws Exception
     */
    public function path_to_fields(string $path, TableOutputTable $table, bool $reverse = true): array
    {
        $parts = explode('.', $path);
        if ($reverse) $parts = array_reverse($parts);

        $f = $table->get_field_by_name($parts[0]);
        $path = [[
            'table' => $table,
            'field' => $f,
        ]];

        for ($i = 1; $i < count($parts); $i++) {
            $table = $f->get_link();
            $f = $table->get_field_by_name($parts[$i]);

            array_push($path, [
                'table' => $table,
                'field' => $f,
            ]);
        }

        return $path;
    }

    /**
     * Delete a record.
     * @param $rowId
     * @return string
     * @throws Exception
     */
    public function delete($rowId): string
    {
        // user is not allowed to delete
        if (!$this->config->allowDelete)
            throw new Exception('Not allowed to delete an entry.');

        $sth = $this->db->prepare($this->queryHandler->delete());

        if ($sth->execute(array(':id' => $rowId,)) &&
            $sth->rowCount() == 1
        ) return 'Entry successfully deleted.';

        throw new Exception($this->parseSQLError($sth->errorInfo()));
    }

    /**
     * Insert a new data set.
     * @param $fieldValues
     * @return array
     * @throws Exception
     */
    public function insert($fieldValues): array
    {
        if (!$this->config->allowAppend) {
            return array(
                'code' => 1,
                'msg' => 'Not allowed to create an entry.',
            );
        }

        $sth = $this->db->prepare($this->queryHandler->insert());

        $valArr = array();

        foreach ($this->rootTable->fields as $field) {
            $val = '';
            if (isset($field->predefined)) {
                $val = $field->predefined;
            } elseif (isset($fieldValues[$field->name]) && $fieldValues[$field->name] != '') {
                $val = $fieldValues[$field->name] == '' ? null : $fieldValues[$field->name];
            } else if ($field->notNull) {
                throw new Exception(sprintf('Fehlender Wert: %s', $field->header ?? $field->name));
            }

            $valArr[':' . $field->name] = $val;
        }

        if ($sth->execute($valArr)) {
            return [
                'id' => $this->db->lastInsertId(),
                'values' => $valArr,
                'msg' => 'Entry successfully added.',
            ];
        }

        throw new Exception($this->parseSQLError($sth->errorInfo()));
    }

    /**
     * Format an SQL error.
     * @param array $error
     * @return String
     */
    public function parseSQLError(Array $error): String
    {
        switch ($error[0]) {
            default:
                return sprintf('Unknown error: %s / %s / %s', $error[0], $error[1], $error[2]);
        }
    }

    /**
     * Edit a record.
     * @param $rowId
     * @param $fieldValues
     * @return array
     * @throws Exception
     */
    public function edit($rowId, $fieldValues)
    {
        if (!$this->config->allowEdit) {
            throw new Exception('No edit permission.');
        }

        $sth = $this->db->prepare($this->queryHandler->update());

        $valArr = array(
            ':id' => $rowId,
        );

        foreach ($this->rootTable->fields as $field) {
            if ($field->independent) {
                continue;
            }

            if ($field->name == $this->rootTable->id) {
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

            $valArr[':' . $field->name] = $fieldValues[$field->name];
        }

        if (!$sth->execute($valArr)) {
            return [
                'code' => 1,
                'msg' => $this->parseSQLError($sth->errorInfo()),
            ];
        }

        return array(
            'code' => 0,
            'msg' => 'Entry successfully updated.',
        );
    }

    /**
     * Convert a path to a PDO parameter string.
     * @param string $path
     * @return string
     */
    public function path_to_pdo_parameter(string $path): string
    {
        return str_replace('.', '_', $path);
    }
}
