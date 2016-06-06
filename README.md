# Onyx

Onyx is a lightweight PHP framework for building web-applications.

```php
require 'Onyx/autoloader.php';

use Onyx\Libs\Database;
use Onyx\Libs\User;
use Onyx\Onyx;

$app = new Onyx();

$app
    ->route('/^login/')
	->via(['GET', 'POST']);

$app
    ->route('/^home/')
	->via(['GET']);

$app
    ->route('/^internal/')
	->via(['GET'])
    ->roles(['user']);

$app->run();
```

# Using a database

```php
$app->set_db(new Database(
    DB_TYPE, DB_HOST, DB_NAME, DB_CHARSET, DB_USER, DB_PASS
));
```

# Authorization

Onyx provides built-in, role-based authorization.
User roles can be assigned using the 'set_user_roles' method.

```php
$app->set_user_roles(function (User $user, Database $db) use ($app) {
    if (!$user->is_authenticated()) return;
    $sth = $db->prepare('
		SELECT name
		FROM role
		  LEFT JOIN login_has_role ON 
		  login_has_role.role_id = role.id
		WHERE login_has_role.login_id = :userId');
    $sth->execute([':userId' => $user->id]);
    $user->set_roles($sth->fetchAll(PDO::FETCH_COLUMN, 0));
});
```

# Application Structure

The preferred structure of an Onyx application:
* */Onyx*
* */Resources*
* */Resources/HomeController.php*

# Resource Controllers

```php
namespace Resources;

use Onyx\Libs\Controller;
use Onyx\Http\JSONResponse;

class HomeController extends Controller
{
	/**
	 * GET: /home
	 */
	public function get(): JSONResponse
	{
		return new JSONResponse('This is the home page.');
	}
	
	/**
	 * GET: /home?method=page-title
	 */
	public function get_page_title(): JSONResponse
	{
		return new JSONResponse('Home');
	}

    /**
     * POST: /home
     * { message = 'test' }
     */
    public function post(string $message): PlainResponse
    {
        // TODO: Post a message.
        return new PlainResponse('Success');
    }
}
```