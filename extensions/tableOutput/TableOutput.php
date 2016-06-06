<?php

namespace Onyx\Extensions\TableOutput;

require_once 'Langs/de.php';
require_once 'Onyx/Extensions/TableOutput/config.php';

use Exception;
use Onyx\Extensions\TableOutput\Controllers\TableOutputController;
use Onyx\Extensions\TableOutput\Controllers\TableOutputParser;
use Onyx\Extensions\TableOutput\Models\TableOutputConfig;
use Onyx\Extensions\TableOutput\Models\TableOutputField;
use Onyx\Extensions\TableOutput\Models\TableOutputQueryHandler;
use Onyx\Extensions\TableOutput\Models\TableOutputRootTable;
use Onyx\Extensions\TableOutput\Models\TableOutputTable;
use Onyx\Extensions\TableOutput\Views\TableOutputRenderer;
use Onyx\Libs\Database;

/**
 * TableOutput is a framework for creating dynamic data tables
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
     * @param TableOutputConfig $config the table-output configuration
     * @param TableOutputTable $table the table-output root table
     */
    public function __construct(Database $db, String $id, TableOutputController $controller, array $rootTableArgs, TableOutputConfig $config)
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
     * @return string the table component string
     */
    public function show()
    {
        return $this->renderer->render_table();
    }

    /**
     * Get the order by value.
     * @param  string $orderBy the desired order by field name
     * @return string          the resulting order by field name
     */
    public function check_order_by($orderBy)
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
    }

    /**
     * Set filter values for one particular field.
     * @param string $fieldName the field name
     * @param array $filterValues the filter values
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
     * @param  string $path the path to the field
     * @param  bool $reverse whether the path is in reverse order
     * @param  TableOutputTable $table the table object
     * @return TableOutputField          the resulting TableOutputField from this path
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
     * @param  string $path the path
     * @param  TableOutputTable $table the table object
     * @param  bool $reverse whether the path is in reverse order or not
     * @return array                    an array of TableOutputField items
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
            )) && $sth->rowCount() == 1
        ) {
            return array(
                'code' => 0,
                'msg' => 'Entry successfully deleted.',
            );
        }

        return array(
            'code' => 1,
            'msg' => $this->parseSQLError($sth->errorInfo()),
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

        foreach ($this->rootTable->fields as $field) {
            $val = '';
            if (isset($field->predefined)) {
                $val = $field->predefined;
            } elseif (isset($fieldValues[$field->name]) && $fieldValues[$field->name] != '') {
                $val = $fieldValues[$field->name] == '' ? null : $fieldValues[$field->name];
            } else if ($field->notNull) {
                return array(
                    'code' => 1,
                    'msg' => sprintf('Fehlender Wert: %s', $field->header ?? $field->name),
                );
            }

            $valArr[':' . $field->name] = $val;
        }

        if ($sth->execute($valArr)) {
            return array(
                'code' => 0,
                'id' => $this->db->lastInsertId(),
                'values' => $valArr,
                'msg' => 'Entry successfully added.',
            );
        }

        return array(
            'code' => 1,
            'msg' => $this->parseSQLError($sth->errorInfo()),
        );
    }

    public function parseSQLError(Array $error): String
    {
        switch ($error[0]) {
            default:
                return sprintf('Unknown error: %s / %s / %s', $error[0], $error[1], $error[2]);
        }
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

    public function path_to_pdo_parameter(string $path): string
    {
        return str_replace('.', '_', $path);
    }
}
