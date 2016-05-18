<?php

// Configuration
require 'config.php';

// Use an autoloader!
require ONYX_REPOSITORY . 'libs/Bootstrap.php';

require ONYX_REPOSITORY . 'libs/Controller.php';
require ONYX_REPOSITORY . 'libs/Model.php';
require ONYX_REPOSITORY . 'libs/View.php';

// Library
require ONYX_REPOSITORY . 'libs/Database.php';
require ONYX_REPOSITORY . 'libs/Session.php';

require 'global/IndexController.php';

$app = new Bootstrap();
?>