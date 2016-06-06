<?php

namespace Onyx\Extensions\TableOutput\Models;

use Onyx\Extensions\TableOutput\Exceptions\SQLException;
use Onyx\Extensions\TableOutput\TableOutput;

class TableOutputQueryHandler
{
    private $tableOutput;

    public function __construct(TableOutput $tableOutput)
    {
        $this->tableOutput = $tableOutput;
    }

    /**
     * Compose a SELECT query.
     * @param  int $start the first entry offest
     * @param  int $limit the last entry offset
     * @param  string $orderBy order by (format: fieldName.linkName.fieldName..)
     * @param  bool $orderByReversed whether the order by is reversed
     * @param  bool $withoutFilter whether no filter should be applied
     * @return string the select query
     */
    public function select(int $start, int $limit, string $orderBy = '', bool $orderByReversed = false, bool $withoutFilter = false): string
    {
        if (!$this->tableOutput->config->allowFilter) {
            $withoutFilter = true;
        }

        $orderBy = $this->generate_order_by($orderBy, $orderByReversed);
        $filter = ($withoutFilter ? '' : $this->filter($this->tableOutput->rootTable));

        return sprintf('SELECT %s FROM %s WHERE CONCAT(%s) IN (%s) %s %s LIMIT %s, %s',
            $this->get_field_string($this->tableOutput->rootTable),
            $this->tableOutput->rootTable->name,
            $this->tableOutput->rootTable->get_concat_id(),
            $this->tableOutput->rootTable->query,
            $filter == '' ? '' : ' AND ' . $filter,
            (($orderBy == '') ? '' : ' ORDER BY ' . $orderBy),
            ($this->tableOutput->config->singlePage ? 0 : $start),
            ($this->tableOutput->config->singlePage ? $this->tableOutput->config->pageRecords : $limit));
    }

    /**
     * Compose a SELECT query for autocompletion.
     * @param  TableOutputLinkTable $link the link table from which to autocomplete
     * @param  string $autocompleteValue the value, on which the autocompletion is based
     * @return string the select autocomplete query
     */
    public function select_autocomplete(TableOutputLinkTable $link, string $autocompleteValue = ''): string
    {
        return sprintf('SELECT %s FROM %s WHERE CONCAT(%s) IN (%s) %s LIMIT %s',
            $this->get_field_string($link),
            $link->name,
            $link->get_concat_id(),
            $link->query,
            $this->filter($link, $autocompleteValue != '', $autocompleteValue),
            $link->autocompleteLimit);
    }

    public function get_reverse_autocomplete(TableOutputLinkTable $link, $id = '')
    {
        $contents = [];
        $fieldPaths = $link->get_paths();

        foreach ($fieldPaths as $path) {
            $fields = $this->tableOutput->path_to_fields($path, $link, false);
            $sql = ':p';

            for ($i = count($fields) - 1; $i > 0; $i--) {
                $sql = str_replace(':p', sprintf('SELECT %s AS text FROM %s WHERE %s = (:p)',
                    $fields[$i]['field']->name,
                    $fields[$i]['table']->name,
                    $fields[$i]['table']->id), $sql);
            }

            $sql = str_replace(':p', sprintf('SELECT %s AS text FROM %s WHERE %s = :p',
                $fields[0]['field']->name,
                $link->name,
                $link->id), $sql);

            $sth = $this->tableOutput->db->prepare($sql);

            if (!$sth->execute(array(
                ':p' => $id,
            ))
            ) {
                throw new SQLException('Could not get link autocomplete.', $sth);
            }

            $txt = $sth->fetch()['text'];
            if (strlen($txt) > 0) array_push($contents, $txt);
        }

        return implode(' ', $contents);
    }

    /**
     * Compose the order by query.
     * @param  string $orderBy the order by definition
     * @param  bool $orderByReversed whether order by is reversed
     */
    private function generate_order_by(string $orderBy, bool $orderByReversed = false): string
    {
        $n = ':p';
        $parts = array_reverse(explode('.', $orderBy));

        if ($orderBy == '') return '';
        $fields = $this->tableOutput->path_to_fields($orderBy, $this->tableOutput->rootTable);

        for ($i = count($fields) - 1; $i > 0; $i--) {
            $n = str_replace(':p', sprintf('(SELECT %s FROM %s WHERE %s = :p)',
                $fields[$i]['field']->name,
                $fields[$i]['table']->name,
                $fields[$i]['table']->id), $n);
        }

        $n = str_replace(':p', $parts[0], $n);

        if ($orderByReversed) $n .= ' DESC';

        return $n;
    }

    /**
     * Compose the filter query.
     * @param  TableOutputTable $table the table
     * @param  boolean $isAutocomplete whether this is an autocomplete query
     * @param  string $autocompleteValue the autocomplete value
     * @return [type]                              the filter query
     */
    private function filter(TableOutputTable $table, bool $isAutocomplete = false, string $autocompleteValue = ''): string
    {
        $filter = '';
        $fieldPaths = $table->get_paths();
        $first = true;

        foreach ($fieldPaths as $path) {
            $current = $this->get_filter_from_path($path, $table, $isAutocomplete, $autocompleteValue);
            if ($current == '') continue;
            $filter .= sprintf('%s%s',
                $first ? '' : ($isAutocomplete ? ' OR ' : ' AND '),
                $current);

            $first = false;
        }

        if ($filter == '') return '';
        if ($isAutocomplete) return sprintf(' AND (%s)', $filter);

        return ' ' . $filter;
    }

    private function get_filter_from_path(string $path, TableOutputTable $table, bool $isAutocomplete = false, string $autocompleteValue = ''): string
    {
        $fields = $this->tableOutput->path_to_fields($path, $table, false);

        $s = sprintf('%s IN (:q)', $fields[0]['field']->name);

        $parts = array_reverse(explode('.', $path));

        for ($i = 1; $i < count($fields) - 1; $i++) {
            $s = str_replace(':q', sprintf('SELECT %s FROM %s WHERE %s IN (:q)',
                $fields[$i]['table']->id,
                $fields[$i]['table']->name,
                $fields[$i]['field']->name), $s);
        }
        $last = $fields[count($fields) - 1];

        // this specific filter does not exist or is not applied
        if (!isset($last['field']->filter) || !$last['field']->filter->isApplied && !$isAutocomplete) return '';

        if ($isAutocomplete) {
            $filter = new TableOutputStringFilter($last['field']);
            $filter->includes['value'] = $autocompleteValue;
            $filter->isApplied = true;
            $last['field']->filter = $filter;
        } else {
            $filter = $last['field']->filter;
        }

        if (count($fields) == 1) {
            return $filter->get_sql($this->tableOutput->path_to_pdo_parameter($path));
        }

        $s = str_replace(':q', sprintf('SELECT %s FROM %s WHERE %s',
            $last['table']->id,
            $last['table']->name,
            $filter->get_sql($this->tableOutput->path_to_pdo_parameter($path))), $s);

        return $s;
    }

    public function get_field_string(TableOutputTable $table): string
    {
        $arr = array(
            sprintf('CONCAT(%s) AS %s',
                $table->get_concat_id(),
                $table->idAlias));

        foreach ($table->fields as $field) {
            if ($field->independent) continue;
            array_push($arr, $field->name);
        }

        return implode(',', $arr);
    }

    /**
     * Compose a DELETE query.
     * @return string the query
     */
    public function delete(): string
    {
        return sprintf('DELETE FROM %s WHERE CONCAT(%s) = :id',
            $this->tableOutput->rootTable->name,
            $this->tableOutput->rootTable->get_concat_id());
    }

    /**
     * Compose the INSERT query.
     * @return string the query
     */
    public function insert(): string
    {
        $rootTable = $this->tableOutput->rootTable;

        $str = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $rootTable->name,
            implode(',', $rootTable->get_field_names()),
            ':' . implode(',:', $rootTable->get_field_names()));

        return $str;
    }

    /**
     * Compose the UPDATE query.
     * @return string the query
     */
    public function update(): string
    {
        $set = '';
        foreach ($this->tableOutput->rootTable->fields as $field) {
            if ($field->name == $this->tableOutput->rootTable->id) continue;

            $set .= $field->name . '=:' . $field->name . ',';
        }

        $set = rtrim($set, ",");

        $str = sprintf('UPDATE %s SET %s WHERE CONCAT(%s) = :id',
            $this->tableOutput->rootTable->name,
            $set,
            $this->tableOutput->rootTable->get_concat_id());

        return $str;
    }
}
