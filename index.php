<?php
require_once "../config.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();
$app = new \Tsugi\Silex\Application($LAUNCH);

$app->get('/', 'AppBundle\\Home::getPage')->bind('main');
$app->get('info', 'AppBundle\\Home::getInfo');
$app->get('static/{file}', 'AppBundle\\Home::getFile')->assert('file', '.+');
$app->post('edit', 'AppBundle\\Home::editFileInfo');
$app->post('add', 'AppBundle\\Home::addFile');
$app->post('delete', 'AppBundle\\Home::deleteFile');
$app->post('filteredInfo', 'AppBundle\\Home::getFilteredData');
$app->post('getUser', 'AppBundle\\Home::fetchUserProfile');

$app->run();
