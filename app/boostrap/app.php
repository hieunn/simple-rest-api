<?php
require_once PROJECT_ROOT . '/vendor/autoload.php';

use App\Internal\Helper\EnvLoader;
use App\Internal\System\Db\DatabaseConnector;

//Load env file
$dotenv = new EnvLoader(PROJECT_ROOT . '/.env');
$dotenv->load();

//Prepare database connection
$dbConnection = DatabaseConnector::getInstance();
$dbConnection->connect();



