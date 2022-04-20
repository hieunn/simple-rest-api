<?php
namespace App\Internal\System\Db;

use App\Internal\Helper\ArrayHelper;

class DatabaseConnector
{
    /**
     * Database connection
     *
     * @var \PDO|null
     */
    protected $connection = null;

    protected $config = [];

    protected $pdoType = self::DEFAULT_PDO;

    private static $instance;

    const DEFAULT_PDO = 'mysql';


    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * @return \PDO|null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Singleton instance
     *
     * @return DatabaseConnector
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load database connection information
     *
     * @throws Exception
     */
    private function loadConfig()
    {
        $databaseConfig = include CONF_PATH . "/database.php";

        $pdoType = !empty($databaseConfig['default']) ? $databaseConfig['default'] : self::DEFAULT_PDO;

        if (empty($databaseConfig['connections'][$pdoType])) {
            throw new \Exception("PDO configuration is missing!");
        }

        $pdoConfig = $databaseConfig['connections'][$pdoType];
        $this->checkRequiredOptions($pdoConfig);

        $this->pdoType = $pdoType;
        $this->config = $pdoConfig;
    }

    /**
     * Validate db configs
     *
     * @param array $config
     * @throws Exception
     */
    private function checkRequiredOptions(array $config)
    {
        if (! array_key_exists('dbname', $config)) {
            throw new \Exception("Configuration array must have a key for 'database' that names the database instance");
        }

        if (! array_key_exists('username', $config)) {
            throw new \Exception("Configuration array must have a key for 'username' for login credentials");
        }

        if (! array_key_exists('password', $config)) {
            throw new \Exception("Configuration array must have a key for 'password' for login credentials");
        }
    }

    /**
     * Creates a PDO DSN for the adapter
     *
     * @return string
     */
    protected function getDsn()
    {
        $dsn = ArrayHelper::only($this->config, ['host','port','dbname']);

        // use all remaining parts in the DSN
        foreach ($dsn as $key => $val) {
            $dsn[$key] = "$key=$val";
        }

        if (isset($this->config['charset'])) {
            $dsn['charset'] = 'charset=' . $this->config['charset'];
        }

        return $this->pdoType . ':' . implode(';', $dsn);
    }

    /**
     * Creates a connection to the database.
     *
     * @return void
     * @throws \Exception
     */
    public function connect()
    {
        // if we already have a PDO object, no need to re-connect.
        if ($this->connection) {
            return;
        }

        // get the dsn first, because some adapters alter the $_pdoType
        $dsn = $this->getDsn();

        $this->beforeConnection();

        try {
            $this->connection = new \PDO(
                $dsn,
                $this->config['username'],
                $this->config['password']
            );
        } catch (\PDOException $e) {
            $this->closeConnection();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Return true if a connection is active
     *
     * @return boolean
     */
    public function isConnected()
    {
        return ((bool) ($this->connection instanceof \PDO));
    }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection()
    {
        $this->connection = null;
    }

    /**
     * Check extension that needed to database connect
     */
    protected function beforeConnection()
    {
        // check for PDO extension
        if (!extension_loaded('pdo')) {
            throw new Exception('The PDO extension is required but the extension is not loaded');
        }

        // check the PDO driver is available
        if (!in_array($this->pdoType, \PDO::getAvailableDrivers())) {
            throw new Exception('The ' . $this->pdoType . ' driver is not currently installed');
        }
    }
}