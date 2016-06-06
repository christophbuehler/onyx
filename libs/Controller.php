<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 04.06.2016
 * Time: 20:07
 */

namespace Onyx\Libs;

class Controller
{
    public $db;
    public $user;
    
    function __construct(Database $db, User $user)
    {
        $this->db = $db;
        $this->user = $user;
    }
}