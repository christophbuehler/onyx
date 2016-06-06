<?php
class View {
  function __construct() {

  }
  public function render($name, $noInclude = false) {
    if ($noInclude == true) {
      require 'views/' . $name . '.php';
      return;
    }
    require 'views/header.tpl';
    require 'views/' . $name . '.php';
    require 'views/footer.tpl';
  }
}
