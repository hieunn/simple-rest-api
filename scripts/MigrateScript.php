<?php
use App\Internal\System\Db\DatabaseConnector;

define('PROJECT_ROOT', __DIR__ . '/..');
define('APP_PATH', PROJECT_ROOT . '/app');
define('CONF_PATH', PROJECT_ROOT . '/config');
define('MIGRATE_PATH', PROJECT_ROOT . '/database/migrations');
define('HTTP_PATH', APP_PATH . '/Http');

require_once APP_PATH . "/boostrap/app.php";

class MigrateScript
{
    public function run()
    {
        $files = scandir(MIGRATE_PATH);
        $files = array_diff($files, ['.','..']);

        foreach ($files as $file) {
            $sql = file_get_contents(MIGRATE_PATH . "/" . $file);
            try {
                $connector = DatabaseConnector::getInstance();
                $db = $connector->getConnection();
                $db->exec($sql);
                echo "Success!";
            } catch (PDOException $e) {
                exit ($e->getMessage());
            }
        }
        $db = DatabaseConnector::getInstance();

    }
}

$script = new MigrateScript();
$script->run();