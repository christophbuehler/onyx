<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Extensions\TableOutput;


/**
 * The default number of page records.
 */
define('TABLE_OUTPUT_DEFAULT_PAGE_RECORDS', 24);

/**
 * The resulting table-output types from native SQL.
 * These types are used only, if no explicit data type
 * was applied to the field.
 */
define('TABLE_OUTPUT_TYPES', [
  'text' => [ 'VAR_CHAR', 'VAR_STRING', 'BLOB' ],
  'number' => [ 'LONG' ],
  'date' => [ 'DATE' ],
  'bool' => [ 'TINY' ],
]);
