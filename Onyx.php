<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx;

use Exception;
use Onyx\Http\PlainResponse;
use Onyx\Libs\Session;
use Onyx\Libs\User;
use Onyx\Libs\Utils;
use Onyx\Libs\Route;
use Onyx\Libs\Database;

class Onyx
{
    private $user;
    private $routes = [];
    private $reqArgs = [];
    private $url;
    private $reqMethod;
    public $db;

    public function __construct()
    {
        Session::init();
        $this->db = new Database();
        $this->create_user();
        $this->set_req_method();
        $this->set_url();
        $this->set_req_args();
    }

    /**
     * Create the default user of this request.
     */
    private function create_user()
    {
        $this->user = new User();
        if (Session::get('userId') === null) return;
        $this->user->authenticate(Session::get('userId'));
    }

    public function set_user_roles($f)
    {
        $f($this->user);
    }

    public function route(string $path, string $dest = null)
    {
        $route = new Route($path, $dest);
        array_push($this->routes, $route);
        return $route;
    }

    /**
     * Process this request.
     */
    public function run()
    {
        foreach ($this->routes as $route)
            if ($route->execute($this->url, $this->reqMethod, $this->db, $this->user, $this->reqArgs)) exit;

        (new PlainResponse('Argument match error.', 400))
            ->send();
    }

    /**
     * Set the request method.
     */
    private function set_req_method()
    {

        // set the request method
        $this->reqMethod = strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Set the request arguments.
     */
    private function set_req_args()
    {

        // fix post data encoding
        $postData = json_encode($_POST);

        $_POST = json_decode(Utils::utf8_urldecode(

        // replace plus sign
            str_replace('+', '%2B', $postData)
        ), true);

        $this->reqArgs = array_merge($_POST, $_GET);
    }

    /**
     * Set the request url parts.
     */
    private function set_url()
    {

        if (!isset($_GET['url']))
            throw new Exception('No url was provided.');

        $this->url = $_GET['url'];
    }
}
