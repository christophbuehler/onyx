<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Libs;

use Onyx\DataProviders\iDb;

class Controller
{
    public $db;
    public $user;

    /**
     * Controller constructor.
     * @param iDb $db
     * @param User $user
     */
    function __construct(iDb $db, User $user)
    {
        $this->db = $db;
        $this->user = $user;
    }
}