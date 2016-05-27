<?php

/**
* Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
* This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
* The Onyx project is a web-application-framework, designed and optimized
* for simple usage and programmer efficiency.
*/

class Bootstrap
{
  private $smarty;

  /**
  * Bootstrap constructor.
  */
  public function __construct()
  {

    // error handling
    if (DEBUG_OUTPUT == 'true') {
      error_reporting(-1);
      ini_set('display_errors', 'On');
    }

    // session handling
    session_save_path(getcwd() . '/session');
    Session::init();

    echo Session::get('loggedIn');

    Session::set('loggedIn', 'lorem')

    require 'Exceptions.php';

    // smarty
    include ONYX_REPOSITORY . 'libs/smarty/Smarty.class.php';
    $this->smarty = new Smarty();

    // model
    $this->model = new Model();

    // url parts
    $url = isset($_GET['url']) ? $_GET['url'] : null;

    // get resource (images, videos, etc)
    try {
      $res = $this->get_resource($url);
    } catch(Exception $e) {
      echo $e;
      return;
    }

    if ($res) {
      // it was just a resource.. get over it
      exit;
    }

    // capitalize every letter after dash or space
    $url = preg_replace_callback('/(?<=( |-))./', function ($m) { return strtoupper($m[0]); }, $url);

    // remove dashes and spaces
    $url = str_replace('-', '', str_replace(' ', '', $url));

    // url segments
    $url = rtrim($url, '/');
    define('URL', $url);
    $url = explode('/', $url);

    // initialize extensions
    $extensions = $this->require_extensions();

    // fix param encoding
    $postData = json_encode($_POST);

    $_POST = json_decode($this->utf8_urldecode(
      str_replace('+', '%2B', $postData) // replace plus sign
    ), true);

    // if url does not exist, set it to MAIN_VIEW
    $url[0] = ($url[0]) ? $url[0] : MAIN_VIEW;

    $this->indexController = new IndexController($this->smarty, $this->model, $url);

    $path = '';
    foreach ($url as $pathPart) {
      $path = $path.$pathPart.'/';
    }

    define('PAGE_PATH', $path);

    $ajax_call = $this->call_page_ajax_function($url);

    // if an ajax function has been called
    if ($ajax_call) {
      // Session::close();
      exit;
    }

    $ajax_call = $this->call_extension_ajax_function($url);

    // if an ajax function has been called
    if ($ajax_call) {
      // Session::close();
      exit;
    }

    $controller = $this->get_page_controller($url);

    // if no page was found    if ($controller == false) {
      throw new PageLoadException(
      sprintf('Page controller "%s" does not exist.', implode('/', $url)));
      // Session::close();
      exit;
    }

    try {
      $controller
        ->add_css_dirs($extensions, true)
        ->add_js_dirs($extensions, true)
        ->add_components($extensions)
        ->init_resources();

      $this->indexController->init();
      $controller->init();
    } catch(Exception $e) {
      echo $e->getMessage();
      return;
    }

    $controller->view_page();
    // Session::close();
  }

  /**
  * Converts a string to a valid url.
  * @param  string $str the string
  * @return string      the formatted url
  */
  public function utf8_urldecode($str)
  {
    $str = preg_replace('/%u([0-9a-f]{3,4})/i', '&#x\\1;', urldecode($str));
    return html_entity_decode($str, null, 'UTF-8');
  }

  /**
  * Get the requested resource.
  * @param  string $url the resource url
  * @return bool        whether it was a valid resource or not
  */
  private function get_resource($filePath)
  {
    // check if it is a resource
    $isResource = false;
    $fileEnding = pathinfo($filePath, PATHINFO_EXTENSION);

    // loop legit resources
    foreach (LEGIT_RESOURCES as $res) {
      if ($res != $fileEnding) {
        continue;
      }
      $isResource = true;
      break;
    }

    // no valid resource
    if (!$isResource) {
      return false;
    }

    if (file_exists($filePath)) {
      header('Location: ' . $filePath);
      return true;
    }

    throw new PageLoadException(
    sprintf('Resource "%s" could not be found.', $filePath));
  }

  /**
  * Call the ajax function of a page.
  * @param  string $url the page ajax function url
  * @return string      whether a page ajax function could be called
  */
  private function call_page_ajax_function($url)
  {
    $reqMethod = strtolower($_SERVER['REQUEST_METHOD']);

    $funcName = $reqMethod . '_' . $url[count($url) - 1];
    unset($url[count($url) - 1]);

    switch ($reqMethod) {
      case 'get':
      case 'delete':
        $params = [ $_GET ];
        break;
      default:
        $params = [ $_POST ];
        break;
    }

    if (count($url) == 0) {
      if (!method_exists($this->indexController, REMOTE_FUNCTION_START . $funcName)) {
        return false;
      }

      $this->define_global_page_constants();
      echo json_encode(call_user_func_array(array($this->indexController, REMOTE_FUNCTION_START . $funcName), $params));
      return true;
    }

    $pageController = $this->get_page_controller($url);

    if (!$pageController) {
      return false;
    }

    if (!method_exists($pageController, REMOTE_FUNCTION_START . $funcName)) {
      return false;
    }

    echo json_encode(call_user_func_array(array($pageController, REMOTE_FUNCTION_START . $funcName), $params));
    return true;
  }

  /**
  * Call the ajax function of an extension.
  * @param  string $url the ajax function url
  * @return bool      whether an extension ajax function could be called
  */
  public function call_extension_ajax_function($url)
  {
    $pagePath = ONYX_REPOSITORY . 'extensions/' . $url[0];

    if (count($url) != 2) {
      return false;
    }

    $controllerName = ucfirst($url[0]);
    $controllerName .= 'Controller';

    if (!file_exists($pagePath . '/' . $controllerName . '.php')) {
      return false;
    }

    require_once $pagePath . '/' . $controllerName . '.php';
    $extensionController = new $controllerName($this->model->db);

    if (method_exists($extensionController, REMOTE_FUNCTION_START.$url[1])) {
      $params = explode(',', (isset($_GET['p']) ? $_GET['p'] : ''));
      $this->define_global_page_constants();
      $this->indexController->init();
      echo json_encode(call_user_func_array(array($extensionController, REMOTE_FUNCTION_START.$url[1]), $params));
      return true;
    }

    return false;
  }

  /**
  * Define page constants for when the request does not apply to a specific page.
  * This is the case in index controller and extension requests.
  * @return void
  */
  private function define_global_page_constants()
  {
    // define('PAGE_NAME', '');
    // define('TEMPLATE', '');
  }

  /**
  * Get the page controller of a page.
  * @param  string $url the page url
  * @return object      the page controller, if it was found
  */
  public function get_page_controller($url)
  {
    // Set root directory
    $pagePath = 'views/' . $url[0];
    $controllerName = ucfirst($url[0]);

    for ($i = 1; $i < count($url); ++$i) {
      $pagePath = $pagePath.'/'.$url[$i];
      $controllerName .= ucfirst($url[$i]);
    }

    if (!file_exists($pagePath . '/index.tpl')) {
      return false;
    }

    $this->smarty->assign('pageName', $url[count($url) - 1]);
    $this->smarty->assign('siteName', SITE_NAME);
    $this->smarty->assign('domain', DOMAIN);
    $this->smarty->assign('siteNameShort', SITE_NAME_SHORT);

    if (!defined('PAGE_NAME'))
      define('PAGE_NAME', $url[0]);

    $controllerName .= 'Controller';
    require $pagePath . '/' . $controllerName . '.php';

    // controls for page render
    return new $controllerName($this->smarty, $this->model, $url, $this->indexController);
  }

  /**
  * Require all php files of all extensions.
  * @return array a list of the extension directories
  */
  public function require_extensions()
  {
    $extensions = EXTENSIONS;
    $extensionDirs = array();

    foreach ($extensions as $extension) {
      $path = ONYX_REPOSITORY . 'extensions/' . $extension . '/';

      // get all php files from that folder and the subfolders
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename => $file)
      {
        if (pathinfo($file, PATHINFO_EXTENSION) != 'php') continue;
        require_once $file;
      }

      array_push($extensionDirs, $path);
    }

    return $extensionDirs;
  }
}
