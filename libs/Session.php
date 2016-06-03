<?php
class Session
{
    public static function init()
    {
      try {
        session_start();
      } catch (Exception $err) {
        (new PlainResponse(500, 'Could not start session.'))
          ->send();
      }
    }

    public static function set($key, $value)
    {
      $_SESSION[$key] = $value;
    }

    public static function get($key)
    {
      if (isset($_SESSION[$key]))
        return $_SESSION[$key];
    }

    public static function close()
    {
      session_write_close();
    }

    public static function destroy()
    {
      session_destroy();
    }
}
