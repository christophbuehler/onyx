<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput\Models;

use Exception;
use Onyx\Extensions\TableOutput\Exceptions\SQLException;
use Onyx\Extensions\TableOutput\TableOutput;
use Onyx\Extensions\TableOutput\Views\TableOutputRenderer;

class TableOutputTable
{
    public $name;
    public $id;
    public $fields = [];

    private $metaQuery;
    public $query;

    public $metas = null;
    public $didAssignMetas = false;
    public $idAlias = 'tblOutputId';

    public $tableOutput;

    /**
     * TableOutputTable constructor.
     * @param array $args
     * @param TableOutput $tableOutput
     * @throws Exception
     */
    public function __construct(array $args, TableOutput $tableOutput)
    {
        if (!isset($args['name']))
            throw new Exception('The argument "name" was not provided.');

        $this->tableOutput = $tableOutput;
        $this->name = $args['name'];
        $this->id = str_replace(' ', '', $args['id']);
        $this->assign_fields($args['fields']);

        $this->metaQuery = $this->compose_meta_query();

        $this->assign_query();

        $this->get_metas();
        $this->assign_metas();
    }

    /**
     * Recursively assign filters to table fields.
     * @param TableOutputRenderer $renderer
     * @return bool
     * @throws Exception
     */
    public function assign_filters_to_fields(TableOutputRenderer $renderer)
    {
        if (!$this->didAssignMetas)
            throw new Exception('Can\'t assign filters to fields before metas were assigned.');

        foreach ($this->fields as $field) {
            if ($field->independent) continue;

            // this is a link field
            if (isset($field->link)) {

                // update the filter for the linked table
                $field->link->assign_filters_to_fields($renderer);
                continue;
            }

            if (!isset($field->type)) {
                throw new Exception(sprintf('No field type assigned to %s', $field->name));
            }

            $field->filter = $renderer->create_filter($field);
        }

        return true;
    }

    /**
     * Get all the field paths of this table.
     * @param string $root
     * @return array
     */
    public function get_paths($root = ''): array
    {
        $paths = [];
        foreach ($this->fields as $field)
            $paths = array_merge($paths, $field->get_paths($root));

        return $paths;
    }

    /**
     * Assign metas to fields.
     * @throws Exception
     */
    private function assign_metas()
    {
        foreach ($this->fields as $field)
            $this->get_field_type_meta($field);
    }

    /**
     * Returns the query for link metas.
     * @return String the query
     */
    private function compose_meta_query(): string
    {
        return sprintf('SELECT %s FROM %s LIMIT 1',
            implode($this->get_field_names(), ','), $this->name);
    }

    /**
     * Get an array of this tables' field names.
     * @return array
     */
    public function get_field_names(): array
    {
        $fieldNames = [];
        foreach ($this->fields as $field) {
            if ($field->independent) continue;
            array_push($fieldNames, $field->name);
        }
        return $fieldNames;
    }

    /**
     * Assign the TableOutput query.
     */
    private function assign_query()
    {
        $predefined = [];
        $qry = sprintf('SELECT concat(%s) FROM %s',
            $this->get_concat_id(),
            $this->name);

        // assign predefined field values to query
        foreach ($this->fields as $field) {
            if (!isset($field->predefined) || strtolower($field->predefined) == 'default') continue;
            array_push($predefined, sprintf('%s = %s',
                $field->name,
                $field->predefined));
        }

        if (count($predefined) > 0) {
            $qry = sprintf('%s WHERE %s',
                $qry,
                implode(' AND ', $predefined));
        }

        $this->query = $qry;
    }

    /**
     * Get the database field type of a field.
     * @param $field
     * @return bool
     * @throws Exception
     */
    public function get_field_type_meta($field): bool
    {
        if (!$this->didAssignMetas) throw new Exception('Can\'t assign field types to fields before metas were assigned.');

        foreach ($this->metas as $meta) {

            // this meta belongs to another field
            if ($field->name != $meta['name']) continue;

            // assign the table-output equivalent of the database field type
            $field->type = isset($field->link) ? 'link' : ($field->type ?? $this->get_table_output_type($meta['native_type']));

            // assign not null
            $field->notNull = in_array('not_null', $meta['flags']);

            return true;
        }

        // no metas were found for this field
        return false;
    }

    /**
     * Assign table-output fields.
     * @param $fields
     * @throws Exception
     */
    private function assign_fields($fields)
    {
        // check if field name is already in use
        foreach ($fields as $field) {
            if (isset($field['name']) && $this->field_name_exists($field['name']))
                throw new Exception(sprintf('This field name is already in use: %s', trim($field['name'])));
            array_push($this->fields, new TableOutputField($field, $this->tableOutput));
        }
    }

    /**
     * Check if a field name was already taken.
     * @param string $name
     * @return bool
     */
    public function field_name_exists(string $name): bool
    {
        foreach ($this->fields as $field)
            if ($field->name == $name) return true;
        return false;
    }

    /**
     * Get the id for SQL concat expressions.
     * @return string
     */
    public function get_concat_id(): string
    {
        return str_replace(',', ',\',\',', $this->id);
    }

    /**
     * Get a child field by its name.
     * @param string $name
     * @return TableOutputField
     * @throws Exception
     */
    public function get_field_by_name(string $name): TableOutputField
    {
        foreach ($this->fields as $field)
            if ($field->name == $name) return $field;

        throw new Exception(sprintf('Field could not be found: %s', $name));
    }

    /**
     * Get the TableOutput field type from native SQL.
     * @param $type
     * @return int|string
     * @throws Exception
     */
    function get_table_output_type($type): string
    {
        foreach (TABLE_OUTPUT_TYPES as $key => $tableOutputType)
            if (in_array($type, $tableOutputType)) return $key;

        throw new Exception(sprintf('The native field type %s was not defined as a table-output-type in the configuration file.', $type));
    }

    /**
     * Get the column metas of the main table.
     * @return array
     * @throws SQLException
     */
    private function get_metas(): array
    {

        // metas have already been loaded
        if ($this->didAssignMetas) {
            return $this->metas;
        }

        // get metas
        $sth = $this->tableOutput->db->prepare($this->metaQuery);

        if (!$sth->execute()) {
            throw new SQLException('Could not get table metas.', $sth);
        }

        $metas = array();

        for ($i = 0; $i < $sth->columnCount(); ++$i) {
            array_push($metas, $sth->getColumnMeta($i));
        }

        $this->metas = $metas;
        $this->didAssignMetas = true;

        return $this->metas;
    }
}
