<?php

namespace Onyx\Libs;

use Invoker\Invoker;
use Resources;

class Route
{
    private $path;
    private $dest;
    private $via;
    private $roles;

    function __construct(string $path = '*', string $dest = null)
    {
        $this->path = $path;
        if ($dest === null) return;
        $this->dest = $dest;
    }

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
     * @param Database $db
     * @param User $user
     * @param array $args
     * @return bool
     * @throws \Invoker\Exception\NotCallableException
     * @throws \Invoker\Exception\NotEnoughParametersException
     */
    public function execute(string $url, string $method, Database $db, User $user, array $args): bool
    {
        if (
            !$this->method_matches($method) ||
            !$this->path_matches($url) ||
            !$this->role_matches($user)
        ) return false;

        $methodName = isset($args['method']) ? 'remote_' . $method . "_" . str_replace('-', '_', $args['method']) : $method;

        $controllerName = $this->get_resource_controller_name($url);

        echo (new Invoker())
            ->call([new $controllerName($db, $user), $methodName], $args)
            ->send();

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

    private function method_matches(string $method): bool
    {
        if ($this->via === null) return true;
        foreach ($this->via as $via)
            if ($via == $method) return true;
        return false;
    }

    private function path_matches(string $url): bool
    {
        return preg_match($this->path, $url);
    }

    private function get_resource_controller_name(string $url): string
    {
        // capitalize every letter after dash
        $url = implode('/', array_map('ucfirst', explode('/', $url)));

        // replace dash
        $url = str_replace('/', '', $url);

        $className = '\\Resources\\' . $url . 'Controller';
        return $className;
    }
}
