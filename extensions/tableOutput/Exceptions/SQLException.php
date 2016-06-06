<?php

namespace Onyx\Extensions\TableOutput\Exceptions;

use Exception;

class SQLException extends Exception
{
    public function __construct(string $msg, \PDOStatement $sth)
    {
        parent::__construct(
            sprintf('%s %s %s %s', $msg, $sth->errorInfo()[0], $sth->errorInfo()[1], $sth->errorInfo()[2]));
    }
}
