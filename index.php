<?php

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;

$app = new \Slim\Slim();

$app->config('debug', true);

require_once ("site.php");
require_once ("admin.php");
require_once ("admin-users.php");
require_once ("admin-categories.php");
require_once ("admin-products.php");
require_once ("functions.php");
require_once ("site-login.php");
require_once ("site-profile.php");
require_once ("site-cart.php");
require_once ("site-checkout.php");

$app->run();

 ?>