<?php
require_once "../config.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();
$app = new \Tsugi\Silex\Application($LAUNCH);

$app->get('/', 'AppBundle\\Home::getPage')->bind('main');
$app->get('info', 'AppBundle\\Home::getInfo');
$app->get('static/{file}', 'AppBundle\\Home::getFile')->assert('file', '.+');

$app->run();
