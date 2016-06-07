<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Libs;

class User
{
    public $id;
    private $roles = [];
    private $isAuthenticated = false;

    /**
     * User constructor.
     */
    function __construct()
    {
    }

    /**
     * Authenticate this user.
     * @param int $id
     */
    public function authenticate(int $id)
    {
        $this->id = $id;
        $this->isAuthenticated = true;
    }

    /**
     * Logout this user.
     */
    public function logout()
    {
        Session::remove('userId');
        $this->id = null;
        $this->isAuthenticated = false;
    }

    /**
     * @param array $roles
     */
    public function set_roles(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param string $role
     * @return bool
     */
    public function has_role(string $role): bool
    {
        foreach ($this->roles as $r)
            if ($r == $role) return true;
        return false;
    }

    /**
     * @return bool
     */
    public function is_authenticated(): bool
    {
        return $this->isAuthenticated;
    }
}
