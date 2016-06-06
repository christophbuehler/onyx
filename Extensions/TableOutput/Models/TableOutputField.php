<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

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

    /**
     * TableOutputField constructor.
     * @param array $args
     * @param TableOutput $tableOutput
     */
    function __construct(array $args, TableOutput $tableOutput)
    {
        $this->tableOutput = $tableOutput;
        $this->independent = $args['independent'] ?? false;

        $this->name = $args['name'] ?? null;

        if ($args['header'] !== null)
            $this->header = trim($args['header']);

        if ($args['predefined'] !== null)
            $this->predefined = $args['predefined'];

        if ($args['link'] !== null)
            $this->link = new TableOutputLinkTable($args['link'], $this->tableOutput);

        if ($args['href'] !== null)
            $this->href = $args['href'];

        if ($args['suggestion'] !== null)
            $this->suggestion = $args['suggestion'];

        if ($args['type'] !== null)
            $this->type = $args['type'];
    }

    /**
     * Get the link this field is referring to.
     * @return TableOutputLinkTable
     */
    public function get_link(): TableOutputLinkTable
    {
        return $this->link;
    }

    /**
     * Get recursive field paths.
     * @param string $root
     * @return array
     */
    public function get_paths($root = ''): array
    {
        $paths = [];
        $root = sprintf('%s%s', $root, $this->name);

        // this field has a link
        if ($this->link !== null) {
            $paths = array_merge($paths, $this->link->get_paths(sprintf('%s.', $root)));
            return $paths;
        }

        // this is an end node field
        array_push($paths, $root);
        return $paths;
    }
}
