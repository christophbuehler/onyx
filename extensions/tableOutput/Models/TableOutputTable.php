<?php

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
     * @param  string $name the table name
     * @param  string $id the table id
     * @param  array $fields the fields
     */
    public function __construct(array $args, TableOutput $tableOutput)
    {
        if (!isset($args['name'])) {
            throw new Exception('name');
        }

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
     *
     * @param TableOutputRenderer $renderer the table output renderer
     *
     * @return bool
     */
    public function assign_filters_to_fields(TableOutputRenderer $renderer)
    {
        if (!$this->didAssignMetas) throw new Exception('Can\'t assign filters to fields before metas were assigned.');

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

            // The field already has a filter.
            // This is the case, when the table-output
            // object was created from a session.
            // if (isset($field->filter)) continue;
            $field->filter = $renderer->create_filter($field);
        }

        return true;
    }

    public function get_paths($root = ''): array
    {
        $paths = [];
        foreach ($this->fields as $field) {
            $paths = array_merge($paths, $field->get_paths($root));
        }
        return $paths;
    }

    /**
     * Assign metas to fields.
     *
     * @return void
     */
    private function assign_metas()
    {
        // loop fields
        foreach ($this->fields as $field) {

            // get the field type
            $this->get_field_type_meta($field);
        }
    }

    /**
     * Returns the query for link metas.
     * @return String the query
     */
    private function compose_meta_query()
    {
        return sprintf('SELECT %s FROM %s LIMIT 1', implode($this->get_field_names(), ','), $this->name);
    }

    public function get_field_names(): array
    {
        $fieldNames = [];
        foreach ($this->fields as $field) {
            if ($field->independent) continue;
            array_push($fieldNames, $field->name);
        }
        return $fieldNames;
    }

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
     * @param  Object $field the field object
     * @return String        the field type
     */
    public function get_field_type_meta($field)
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
     * @param  array $fields the fields
     * @return void
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

    public function field_name_exists(string $name): bool
    {
        foreach ($this->fields as $field)
            if ($field->name == $name) return true;
        return false;
    }

    public function get_concat_id()
    {
        return str_replace(',', ',\',\',', $this->id);
    }

    public function get_field_by_name(string $name): TableOutputField
    {
        foreach ($this->fields as $field) {
            if ($field->name == $name) return $field;
        }

        throw new Exception(sprintf('Field could not be found: %s', $name));
    }

    /**
     * Get the table-output field type from native SQL.
     * @param  String $type the SQL type
     * @return String       the table-output type
     */
    function get_table_output_type($type)
    {
        foreach (TABLE_OUTPUT_TYPES as $key => $tableOutputType) {
            if (in_array($type, $tableOutputType)) return $key;
        }
        throw new Exception(sprintf('The native field type %s was not defined as a table-output-type in the configuration file.', $type));
    }

    /**
     * Get the column metas of the main table.
     * @return array a list of column metas
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
