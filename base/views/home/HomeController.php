<?php
class HomeController extends Controller {
    function init() {
    	$this->smarty->assign("welcomeMsg", "Welcome to this Onyx project.");
    }
}