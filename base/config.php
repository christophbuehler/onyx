// debug
define('DEBUG_OUTPUT', 'false');

// database
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', '{db_name}');
define('DB_USER', '{db_user}');
define('DB_PASS', '{db_pass}');

// onyx
define('ONYX_REPOSITORY', '{onyx_repository}');

// ajax
define('REMOTE_FUNCTION_START', 'remote_');

// paths
define('DOMAIN', '{domain}');
define('SITE_NAME', '{site_name}');
define('SITE_NAME_SHORT', '{site_name_short}');

// views
define('LOGIN_VIEW', 'login');
define('MAIN_VIEW', 'home');

// extensions
define('EXTENSIONS', serialize(array(
    {extensions}
)));