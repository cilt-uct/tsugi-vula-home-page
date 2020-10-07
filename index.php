<?php
require_once "../config.php";

error_reporting(E_ALL & ~E_NOTICE);

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;

$LAUNCH = LTIX::requireData();
$app = new \Tsugi\Silex\Application($LAUNCH);

$app->get('/', 'AppBundle\\Home::getPage')->bind('main');
$app->get('info', 'AppBundle\\Home::getInfo');


$app->run();

