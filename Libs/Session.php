<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Libs;

use Exception;
use Onyx\Http\PlainResponse;

class Session
{
    /**
     * Initialize this session.
     */
    public static function init()
    {
        session_save_path(getcwd() . '/Onyx/sessions');

        try {
            session_start();
        } catch (Exception $err) {
            (new PlainResponse(500, 'Could not start session.'))
                ->send();
        }
    }

    /**
     * Set a session value.
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value.
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        if (isset($_SESSION[$key]))
            return $_SESSION[$key];

        return null;
    }

    /**
     * Remove a session key.
     * @param $key
     */
    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Close this session.
     */
    public static function close()
    {
        session_write_close();
    }

    /**
     * Destroy this session.
     */
    public static function destroy()
    {
        session_destroy();
    }
}
