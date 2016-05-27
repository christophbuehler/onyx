<?php
class Session {
    public static function init() {
        try {
          @session_start();
        } catch (Exception $err) {
          // session error handling
        }
    }

    public static function set($key, $value) {
      echo "setting " . $key;
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
      echo "destroy";
      session_destroy();
    }
}
