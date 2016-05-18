<?php

// class TableOutputLink
// {
//     public $fieldName;
//     public $table;
//     public $id;
//     // public $text;
//     public $reference;
//     public $value;
//     public $link;
//
//     private $autocompleteLimit;
//
//     public function __construct($args, $fieldName, $autocompleteLimit = 4)
//     {
//         $this->fieldName = $fieldName;
//
//         // check required parameters
//         if (!isset($args['table'])) {
//             throw new Exception(sprintf('No "table" specified for link: %s', $this->fieldName));
//         }
//
//         if (!isset($args['id'])) {
//             throw new Exception(sprintf('No "id" specified for link: %s', $this->fieldName));
//         }
//
//         if (!isset($args['text'])) {
//             throw new Exception(sprintf('No "text" specified for link: %s', $this->fieldName));
//         }
//
//         if (isset($args['value'])) {
//             $this->value = $args['value'];
//         }
//
//         if (isset($args['link'])) {
//             $this->link = new TableOutputLink($args['link'], $this->fieldName);
//         }
//
//         $this->table = $args['table'];
//         $this->id = $args['id'];
//         $this->text = $args['text'];
//         $this->autocompleteLimit = $autocompleteLimit;
//
//         $this->reference = $args['reference'] ?? null;
//     }
//
//     /** Returns the query for autocomplete.
//      */
//     public function compose_autocomplete_query()
//     {
//         $linkQuery = sprintf('SELECT %s AS id, %s AS text FROM %s WHERE %s LIKE :value LIMIT %s',
//             $this->id, $this->text, $this->table, $this->text, $this->autocompleteLimit);
//
//         return $linkQuery;
//     }
//
//     /**
//      * Returns the query for link metas.
//      * @return String the query
//      */
//     function compose_meta_query()
//     {
//       return sprintf('SELECT %s AS text FROM %s LIMIT 1',
//         $this->text, $this->table);
//     }
//
//     /** Returns the query for text.
//      */
//     public function compose_text_query()
//     {
//         $textQuery = sprintf('SELECT %s AS text FROM %s WHERE %s = :value LIMIT 1',
//             $this->text, $this->table, $this->id);
//
//         return $textQuery;
//     }
//
//     /** This query returns the link text of a foreign table by its foreign key.
//      */
//     public function compose_link_query()
//     {
//         $linkQuery = sprintf('SELECT %s AS text FROM %s WHERE %s = :id',
//             $this->text, $this->table, $this->id);
//
//         return $linkQuery;
//     }
// }
