<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * More information about the Onyx framework can be found on the
 * developer page http://norizon.li/onyx
 */

class Controller
{
  public $smarty;
  public $model;
  public $parent;
  public $css;
  public $js;
  public $components;
  public $pathPrefix;

  /**
   * Controller constructor.
   * @param object $smarty    smarty instance
   * @param model $model      model instance
   * @param array $pathArray  request path
   * @param object $error     error controller
   * @param object $parent    parent controller instance
   */
  public function __construct(Smarty $smarty, Model $model, array $pathArray, Controller $parent = null)
  {
    $this->smarty = $smarty;
    $this->model = $model;
    $this->parent = $parent;
    $this->css = [];
    $this->js = [];
    $this->components = [];
    $this->pathPrefix = SITE_PATH;
  }

  /**
   * Implement business logic.
   * @return void
   */
  public function init()
  {

  }

  public function init_resources(): Controller
  {
      $configFile = 'views/' . PAGE_PATH . 'config.php';

      // no config file found
      if (!file_exists($configFile)) {
        define('TEMPLATE', DEFAULT_TEMPLATE);
        define('TITLE', SITE_NAME_SHORT);
      } else {
        require $configFile;
      }

      // add default css directories
      $this->add_css_dirs([
        'global/', // global
        'templates/' . TEMPLATE, // template
        'views/' . PAGE_PATH, // page
      ], true);

      // add default JS directories
      $this->add_js_dirs([
        'global', // global
        'templates/' . TEMPLATE, // template
        'views/' . PAGE_PATH, // page
      ], true);

      // add default component directories
      $this->add_components([
        'global', // global
        'templates/' . TEMPLATE, // template
        'views/' . PAGE_PATH, // page
      ]);

      return $this;
  }

  /**
   * Add css directories.
   * @param array $directories a list of directories
   * @param bool $root         whether this is the root or not
   */
  public function add_css_dirs($directories, $root = false)
  {
      foreach ($directories as $dir) {
          if (substr($dir, -1) != '/') {
              $dir .= '/';
          }
          if ($root) {
              $dir .= 'css/';
          }

          // recurive child directories
          $this->add_css_dirs(glob($dir . '*', GLOB_ONLYDIR));

          for ($i = 0; $i < count($this->css); ++$i) {

              // this file was already included
              if ($this->css[$i] == $dir) {
                  continue 2;
              }
          }

          array_push($this->css, $dir);
      }

      return $this;
  }

  /**
   * Add JavaScript directories.
   * @param array $directories the directories
   * @param bool $root         whether this is the root directory or not
   */
  public function add_js_dirs($directories, $root = false)
  {
      foreach ($directories as $dir) {
          if (substr($dir, -1) != '/') {
              $dir .= '/';
          }
          if ($root) {
              $dir .= 'js/';
          }

          // recurive child directories
          $this->add_js_dirs(glob($dir . '*', GLOB_ONLYDIR));

          for ($i = 0; $i < count($this->js); ++$i) {

              // this file was already included
              if ($this->js[$i] == $dir) {
                  continue 2;
              }
          }

          array_push($this->js, $dir);
      }

      return $this;
  }

  /**
   * Add component directories. This method is not recursive.
   *
   * @param array $directories the component
   * @param bool $root         whether this is the root directory or not
   */
  public function add_components($directories)
  {
      foreach ($directories as $dir) {
          if (substr($dir, -1) != '/') {
              $dir .= '/';
          }
          $dir .= '';

          if (!file_exists($dir . COMPONENT_FILE)) continue;

          for ($i = 0; $i < count($this->components); ++$i) {

              // this file was already included
              if ($this->components[$i] == $dir) {
                  continue 2;
              }
          }

          array_push($this->components, $this->pathPrefix . $dir . COMPONENT_FILE);
      }

      return $this;
  }

  public function view_page()
  {
      // check if template has a value
      if (trim(TEMPLATE) == '') {
          $this->error->template_load('No template name set for this page.');
          exit;
      }

      // check if template exists
      if (!is_dir('templates/' . TEMPLATE)) {
          throw new TemplateLoadException('Template "' . TEMPLATE . '" does not exist.');
          exit;
      }

      // check if header exists
      if (!file_exists('templates/' . TEMPLATE.'/header.tpl')) {
          $this->error->template_load('Template "' . TEMPLATE . '" has no header.');

          exit;
      }

      // check if page has a template
      if (!file_exists('views/' . PAGE_PATH.'/index.tpl')) {
          $this->error->template_load('This page has no main template.');

          exit;
      }

      // check if footer exists
      if (!file_exists('templates/' . TEMPLATE . '/footer.tpl')) {
          $this->error->template_load('Template "' . TEMPLATE . '" has no footer.');

          exit;
      }

      /* variable assignment */

      // assign page name
      $this->smarty->assign('page_name', PAGE_NAME);

      // assign title
      $this->smarty->assign('title', TITLE);

      // assign resource files
      $this->smarty->assign('css_files', array_reverse($this->get_styles($this->css)));
      $this->smarty->assign('js_files', array_reverse($this->get_scripts($this->js)));
      $this->smarty->assign('component_files', array_reverse($this->components));

      $this->smarty->assign('domain', DOMAIN);

      /* page load */      // assign head and foot

      $this->smarty->assign('head', 'templates/'.TEMPLATE.'/header.tpl');
      $this->smarty->assign('foot', 'templates/'.TEMPLATE.'/footer.tpl');

      // load content      $this->smarty->display('views/'.PAGE_PATH.'/index.tpl');
  }

  private function get_styles($directories)
  {
      $styles = array();

      foreach ($directories as $dir) {

          // check if directory exists
          if (!is_dir($dir)) {
              continue;
          }

          foreach (glob($dir . '*.css') as $css) {
              array_push($styles, $this->pathPrefix . $css);
          }
      }

      return $styles;
  }

  private function minify_css($Css)
  {
      $Css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $Css);

      /* remove tabs, spaces, newlines, etc. */

      $Css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $Css);

      return $Css;
  }

  private function get_scripts($directories)
  {
    $scripts = array();

    foreach ($directories as $dir) {

        // check if directory exists
        if (!is_dir($dir)) {
            continue;
        }

        foreach (glob($dir . '*.js') as $js) {
            array_push($scripts, $this->pathPrefix . $js);
        }
    }

    return $scripts;
  }
}
