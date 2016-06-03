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
  private $reqArgs = [];
  private $url = [];
  private $reqMethod;
  private $pageController;
  private $indexController;
  private $extensions;

  /**
  * Process request.
  */
  public function process()
  {
    $errors = [];
    this.init();

    // first of all, fetch ressources
    try {
      $this->get_resource();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    // handle this global request
    try {
      $this->handle_global_request();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    // handle this page request
    try {
      $this->handle_page_request();
    } catch(Exception $e) {
      array_push($errors, $e);
    }

    // handle this extension requst
    try {
      $this->handle_extension_request();
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
    $this->smarty = new Smarty();

    // model
    $this->model = new Model();

    // set the request method
    $this->reqMethod = strtolower($_SERVER['REQUEST_METHOD']);

    // set arguments
    $this.set_args();

    // set url
    $this->set_url();

    // get extensions
    $this->extensions = $this->require_extensions();

    // define the page path
    $this->define_page_path();
  }

  /**
   * Handle a global request.
   */
  private function handle_global_request() {

    // initialize the index controller
    $this->indexController->init();

    // create the index controller
    $this->indexController = new IndexController($this->smarty, $this->model, $this->url);
  }

  /**
   * Handle a page request.
   */
  private function handle_page_request() {

    // create the page controller
    $this->pageController = $this->get_page_controller();

    // initialize the page controller
    $this->pageController->init();

    // try to call a page ajax function
    $this->call_page_ajax_function();

    // load this page
    $this->pageController
      ->add_css_dirs($this->extensions, true)
      ->add_js_dirs($this->extensions, true)
      ->add_components($this->extensions)
      ->init_resources();

    // view the page
    $this->pageController->view_page();
  }

  /**
   * Handle an extension request.
   */
  private function handle_extension_request() {

    // create the extension controller
    $this->extensionController = $this->get_extension_controller() ;

    // try to call an extension ajax function
    $this->call_extension_ajax_function();
  }

  /**
   * Set the request arguments.
   */
  private function set_args() {

    // fix post data encoding
    $postData = json_encode($_POST);

    $_POST = json_decode($utf8_urldecode(
      str_replace('+', '%2B', $postData) // replace plus sign
    ), true);

    switch ($this->reqMethod) {
      case 'get':
      case 'delete':
        $this->reqArgs = $_GET;
        break;
      default:
        $this->reqArgs = $_POST;
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

    $this->url = $urlArr;
  }

  /**
  * Get the requested resource.
  */
  private function get_resource()
  {
    $filePath = $this->url;

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
    return call_xhr_method($this->indexController, compose_ajax_method_name($this->reqMethod, $this->url), $params);
  }

  /**
  * Call the ajax function of a page.
  * @param  string $url the page ajax function url
  * @return string      whether a page ajax function could be called
  */
  private function call_page_ajax_function()
  {

    // try to call a page ajax function
    call_xhr_method($this->pageController, compose_ajax_method_name($this->reqMethod, $this->url), $this->reqArgs);
  }

  /**
  * Call the ajax function of an extension.
  * @param  string $url the ajax function url
  * @return bool      whether an extension ajax function could be called
  */
  public function call_extension_ajax_function($url)
  {

    // try to call an extension ajax function
    call_xhr_method($this->extensionController, compose_ajax_method_name($this->reqMethod, $this->url), $this->reqArgs);
  }

  /**
  * Get the page controller of a page.
  */
  public function get_page_controller()
  {
    $url = $this->url;

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
    return new $controllerName($this->smarty, $this->model, $url, $this->indexController);
  }

  /**
   * Get the targeted extension controller.
   */
  public function get_extension_controller()
  {

    // set the controller name
    $controllerName = ucfirst($this->url[0]) . 'Controller';

    require_once $pagePath . '/' . $controllerName . '.php';
    return new $controllerName($this->model->db);
  }

  /**
   * Set smarty variables.
   */
  private function set_smarty_vars()
  {
    $this->smarty->assign('pageName', $this->url[ count($this->url) - 1] );
    $this->smarty->assign('siteName', SITE_NAME);
    $this->smarty->assign('domain', DOMAIN);
    $this->smarty->assign('siteNameShort', SITE_NAME_SHORT);
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
