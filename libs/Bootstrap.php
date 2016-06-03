<?php

/**
* Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
* This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
* The Onyx project is a web-application-framework, designed and optimized
* for simple usage and programmer efficiency.
*/

class Bootstrap
{
  private static $smarty;
  private static $reqArgs = [];
  private static $url = [];
  private static $reqMethod;
  private static $pageController;
  private static $indexController;
  private static $extensions;
  private static $model;

  /**
  * Process request.
  */
  public function process()
  {
    $errors = [];
    self::init();

    // first of all, fetch ressources
    try {
      self::get_resource();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    // handle this global request
    try {
      self::handle_global_request();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    return;

    // handle this page request
    try {
      self::handle_page_request();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    // handle this extension requst
    try {
      self::handle_extension_request();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    // this request did not match any criteria
    (new PlainResponse(400, $errors))
      ->send();
  }

  /**
   * Initialize components.
   */
  private function init() {

    // session handling
    session_save_path(getcwd() . '/session');
    ini_set('session.gc_probability', 1);
    Session::init();

    require 'Exceptions.php';

    // smarty
    include ONYX_REPOSITORY . 'libs/smarty/Smarty.class.php';
    self::$smarty = new Smarty();

    // model
    self::$model = new Model();

    // set the request method
    self::$reqMethod = strtolower($_SERVER['REQUEST_METHOD']);

    // set arguments
    self::set_args();

    // set url
    self::set_url();

    // get extensions
    self::$extensions = self::require_extensions();
  }

  /**
   * Handle a global request.
   */
  private function handle_global_request() {

    // create the index controller
    self::$indexController = new IndexController(self::$smarty, self::$model, self::$url);

    echo "a";

    return;

    // initialize the index controller
    self::$indexController->init();
  }

  /**
   * Handle a page request.
   */
  private function handle_page_request() {

    // create the page controller
    self::$pageController = self::$get_page_controller();

    // initialize the page controller
    self::$pageController->init();

    // try to call a page ajax function
    self::call_page_ajax_function();

    // load this page
    self::$pageController
      ->add_css_dirs(self::$extensions, true)
      ->add_js_dirs(self::$extensions, true)
      ->add_components(self::$extensions)
      ->init_resources();

    // view the page
    self::$pageController->view_page();
  }

  /**
   * Handle an extension request.
   */
  private function handle_extension_request() {

    // create the extension controller
    self::$extensionController = self::$get_extension_controller() ;

    // try to call an extension ajax function
    self::call_extension_ajax_function();
  }

  /**
   * Set the request arguments.
   */
  private function set_args() {

    // fix post data encoding
    $postData = json_encode($_POST);

    $_POST = json_decode(utf8_urldecode(
      str_replace('+', '%2B', $postData) // replace plus sign
    ), true);

    switch (self::$reqMethod) {
      case 'get':
      case 'delete':
        self::$reqArgs = $_GET;
        break;
      default:
        self::$reqArgs = $_POST;
        break;
    }
  }

  /**
   * Set the request url parts.
   */
  private function set_url() {

    // url parts
    $url = isset($_GET['url']) ? $_GET['url'] : null;

    // capitalize every letter after dash or space
    $url = preg_replace_callback('/(?<=( |-))./', function($m) { return strtoupper($m[0]); }, $url);

    // remove dashes and spaces
    $url = str_replace('-', '', str_replace(' ', '', $url));

    // url segments
    $url = rtrim($url, '/');

    define('URL', $url);

    $urlArr = explode('/', $url);

    // if url does not exist, set it to MAIN_VIEW
    $urlArr[0] = $urlArr[0] ? $urlArr[0] : MAIN_VIEW;

    self::$url = $urlArr;
  }

  /**
  * Get the requested resource.
  */
  private function get_resource()
  {
    $filePath = self::$url;

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
    if (!$isResource) return false;

    // this is a valid resource file type

    if (file_exists($filePath)) {
      header('Location: ' . $filePath);
      exit;
    }

    throw new PageLoadException(
      sprintf('Resource "%s" could not be found.', $filePath));
  }

  private function call_global_ajax_function()
  {

    // try to call a global ajax function
    return call_xhr_method(self::$indexController, compose_ajax_method_name(self::$reqMethod, self::$url), $params);
  }

  /**
  * Call the ajax function of a page.
  * @param  string $url the page ajax function url
  * @return string      whether a page ajax function could be called
  */
  private function call_page_ajax_function()
  {

    // try to call a page ajax function
    call_xhr_method(self::$pageController, compose_ajax_method_name(self::$reqMethod, self::$url), self::$reqArgs);
  }

  /**
  * Call the ajax function of an extension.
  * @param  string $url the ajax function url
  * @return bool      whether an extension ajax function could be called
  */
  public function call_extension_ajax_function($url)
  {

    // try to call an extension ajax function
    call_xhr_method(self::$extensionController, compose_ajax_method_name(self::$reqMethod, self::$url), self::$reqArgs);
  }

  /**
  * Get the page controller of a page.
  */
  public function get_page_controller()
  {
    $url = self::$url;

    // Set root directory
    $pagePath = 'views/' . $url[0];
    $controllerName = ucfirst($url[0]);

    // loop the url parts and compose the final controller name
    for ($i = 1; $i < count($url); ++$i) {
      $pagePath = $pagePath . '/' . $url[$i];
      $controllerName .= ucfirst($url[$i]);
    }

    $controllerName .= 'Controller';

    require_once $pagePath . '/' . $controllerName . '.php';
    return new $controllerName(self::$smarty, self::$model, $url, self::$indexController);
  }

  /**
   * Get the targeted extension controller.
   */
  public function get_extension_controller()
  {

    // set the controller name
    $controllerName = ucfirst(self::$url[0]) . 'Controller';

    require_once $pagePath . '/' . $controllerName . '.php';
    return new $controllerName(self::$model->db);
  }

  /**
   * Set smarty variables.
   */
  private function set_smarty_vars()
  {
    self::$smarty->assign('pageName', self::$url[ count(self::$url) - 1] );
    self::$smarty->assign('siteName', SITE_NAME);
    self::$smarty->assign('domain', DOMAIN);
    self::$smarty->assign('siteNameShort', SITE_NAME_SHORT);
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
