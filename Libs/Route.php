<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Libs;

use Exception;
use Invoker\Invoker;
use Onyx\DataProviders\iDb;
use Onyx\Http\PlainResponse;
use Resources;

class Route
{
    private $path;
    private $dest;
    private $via;
    private $roles;

    /**
     * Route constructor.
     * @param string $path
     * @param string|null $dest
     */
    function __construct(string $path = '*', string $dest = null)
    {
        $this->path = $path;
        if ($dest === null) return;
        $this->dest = $dest;
    }

    /**
     * @param array $roles
     * @return Route
     */
    public function roles(array $roles): Route
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @param string $via
     * @return Route
     */
    public function via(string $via): Route
    {
        $this->via = $via;
        return $this;
    }

    /**
     * @param string $url
     * @param string $method
     * @param iDb $db
     * @param User $user
     * @param array $args
     * @return bool
     * @throws \Invoker\Exception\NotCallableException
     * @throws \Invoker\Exception\NotEnoughParametersException
     */
    public function execute(string $url, string $method, iDb $db, User $user, array $args): bool
    {
        if (
            !$this->method_matches($method) ||
            !$this->path_matches($url) ||
            !$this->role_matches($user)
        ) return false;

        $methodName = isset($args['method']) ? 'remote_' . $method . "_" . str_replace('-', '_', $args['method']) : $method;

        $controllerName = $this->get_resource_controller_name($url);

        try {
            echo (new Invoker())
                ->call([new $controllerName($db, $user), $methodName], $args)
                ->send();
        } catch (Exception $e) {
            echo (new PlainResponse($e->getMessage(), 400))
                ->send();
        }


        return true;
    }

    /**
     * @param User $user
     * @return bool
     */
    private function role_matches(User $user): bool
    {
        if ($this->roles === null) return true;
        foreach ($this->roles as $role)
            if ($user->has_role($role)) return true;
        return false;
    }

    /**
     * @param string $method
     * @return bool
     */
    private function method_matches(string $method): bool
    {
        if ($this->via === null) return true;
        foreach ($this->via as $via)
            if ($via == $method) return true;
        return false;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function path_matches(string $url): bool
    {
        return preg_match($this->path, $url);
    }

    /**
     * @param string $url
     * @return string
     */
    private function get_resource_controller_name(string $url): string
    {
        if ($this->dest !== null) $url = $this->dest;

        // capitalize every letter after dash
        $url = implode('/', array_map('ucfirst', explode('/', $url)));

        // replace dash
        $url = str_replace('/', '', $url);

        $className = '\\Resources\\' . $url . 'Controller';
        return $className;
    }
}
