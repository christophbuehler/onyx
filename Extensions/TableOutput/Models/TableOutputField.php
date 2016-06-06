<?php

namespace Onyx\Extensions\TableOutput\Models;

use Onyx\Extensions\TableOutput\TableOutput;

class TableOutputField
{
    public $name;
    public $header;
    public $predefined;
    public $link;
    public $independent = false;
    public $suggestion;
    
    public $filter;
    public $type;
    public $autoIncrement = false;
    public $notNull = false;

    private $tableOutput;

    public function get_link(): TableOutputLinkTable
    {
        return $this->link;
    }

    function __construct(array $args, TableOutput $tableOutput)
    {
        $this->tableOutput = $tableOutput;
        $this->independent = $args['independent'] ?? false;

        $this->name = $args['name'] ?? null;

        if (isset($args['header']))
            $this->header = trim($args['header']);

        if (isset($args['predefined']))
            $this->predefined = $args['predefined'];

        if (isset($args['link']))
            $this->link = new TableOutputLinkTable($args['link'], $this->tableOutput);

        if (isset($args['href']))
            $this->href = $args['href'];

        if (isset($args['suggestion']))
            $this->suggestion = $args['suggestion'];

        if (isset($args['type']))
            $this->type = $args['type'];
    }

    public function get_paths($root = ''): array
    {
        $paths = [];
        $root = sprintf('%s%s', $root, $this->name);

        // this field has a link
        if (isset($this->link)) {
            $paths = array_merge($paths, $this->link->get_paths(sprintf('%s.', $root)));
            return $paths;
        }

        // this is an end node field
        array_push($paths, $root);
        return $paths;
    }
}
