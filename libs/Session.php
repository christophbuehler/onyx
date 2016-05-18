<?php
class Session {
    public static function init() {
        try {
          @session_start();
          session_regenerate_id(true);
        } catch (Exception $err) {
          // session error handling
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        if (isset($_SESSION[$key]))
            return $_SESSION[$key];
    }

    public static function close() {
      session_write_close();
    }

    public static function destroy() {
        session_destroy();
    }
}
