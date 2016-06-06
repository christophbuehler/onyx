# Onyx

Onyx is a lightweight PHP framework for building view-based web-applications.

```php
require 'Onyx/autoloader.php';
use Onyx\Onyx;

$app = new Onyx();

$app
    ->route('/^auth/')
	->via;

$app
    ->route('/^home/')
	->via(['GET']);

$app
    ->route('/^internal/')
	->via(['GET'])
    ->roles(['user']);

$app->run();
```

# Authorization

Onyx provides built-in, role-based authorization.
User roles can be assigned using 'set_user_roles'.

```php
$app->set_user_roles(function (User $user) use ($app) {
    if (!$user->is_authenticated()) return;
    $sth = $app->db->prepare('
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

The prefered structure of an Onyx application:
** /Onyx **
** /Resources **

# View Controllers

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
}
```