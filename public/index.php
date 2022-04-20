<?php

define('PROJECT_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/..');
define('APP_PATH', PROJECT_ROOT . '/app');
define('CONF_PATH', PROJECT_ROOT . '/config');
define('HTTP_PATH', APP_PATH . '/Http');

require_once APP_PATH . "/boostrap/app.php";
use App\Internal\System\Http\Front;

if (getenv("APP_ENV") == 'local') {
    ini_set('display_errors', '1');
    error_reporting(phpversion() >= 5.4 ? ~E_STRICT : E_ALL);
}

$front = Front::getInstance();
$front->dispatch();
exit();





