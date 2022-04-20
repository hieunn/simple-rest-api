<?php

namespace App\Models\BaseModel;

use App\Internal\Helper\ArrayHelper;
use App\Internal\System\Db\DatabaseConnector;

abstract class BaseModel
{
    /**
     * ID of element
     *
     * @var null|mixed
     */
    protected $id;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Table name
     *
     * @var string
     */
    protected $table;

    /**
     * Columns name
     *
     * @var array
     */
    protected $cols = [];

    /**
     * Flag that stores if object was loaded
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * Table data container
     *
     * @var array
     */
    protected $data;

    /**
     * Database connection
     *
     * @var \PDO
     */
    protected $db = null;

    /**
     * Set DB connection
     * BaseModel constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $connector = DatabaseConnector::getInstance();

        if (!$connector->isConnected()) {
            $connector->connect();
        }

        $this->db = $connector->getConnection();
    }


    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * object ID
     *
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets object ID
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Tells, if object was loaded
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Get property of record
     *
     * @param $property
     * @param null $default
     * @return mixed|null
     */
	protected function getProperty($property, $default = null)
	{
		if (!empty($property) && isset($this->data[$property])) {
			return $this->data[$property];
		}
		
		return $default;
	}

    /**
     * Finds record by filters
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function find(array $filters = [])
    {
        if (empty($filters)) {
            return $this->findAll();
        }

        $filters = $this->prepareData($filters);
	    $sql = $this->buildQuery('find', $filters);

	    try {
		    $statement = $this->execute($sql, $filters);
		    
		    if (!$statement) {
			    return [];
		    }
		
		    $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
		    return $result;
	    } catch (\PDOException $e) {
		    throw new \Exception($e);
	    }
    }

    /**
     * Get all record from table
     *
     * @return array|false
     * @throws \Exception
     */
    public function findAll()
    {
        $sql = $this->buildQuery('findAll');

        try {
	        $statement = $this->execute($sql);
	
	        if (!$statement) {
		        return [];
	        }
            
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return $result;
        } catch (\PDOException $e) {
	        throw new \Exception($e);
        }
    }

    /**
     * Process save data to database
     *  object has loaded -> update
     *
     * @param array $input
     * @throws \Exception
     */
    public function save(array $input = [])
    {
        if ($this->isLoaded()) {
            $this->update($input);
        } else {
            $this->id = $this->insert($input);
        }
        
        $this->load();
    }

    /**
     * Process insert data to database
     *
     * @param array $input
     * @return int|string
     * @throws \Exception
     */
    public function insert(array $input = [])
    {
        $data = $this->prepareData($input);
        $sql = $this->buildQuery('insert', $data);

        try {
        	/** @var \PDOStatement $statement */
	        $statement = $this->execute($sql, $input);
	
	        if (!$statement) {
		        return 0;
	        }
	        
            if ($statement->rowCount()) {
                return $this->db->lastInsertId();
            }

            return 0;
        } catch (\PDOException $e) {
	        throw new \Exception($e);
        }
    }

    /**
     * Process update data to database by Id
     *
     * @param array $input
     * @param int $id
     * @return false|int
     * @throws \Exception
     */
    public function update(array $input = [], $id = 0)
    {
	    $data = $this->prepareData($input);
	    $sql = $this->buildQuery('update', $data);

	    if (!empty($this->id)) {
		    $data['id'] = $this->id;
	    }

        if (!empty($id)) {
            $data['id'] = $id;
        }
	    
	    try {
		    /** @var \PDOStatement $statement */
		    $statement = $this->execute($sql, $data);
		    if (!$statement) {
			    return false;
		    }
		    
		    return $statement->rowCount();
	    } catch (\PDOException $e) {
		    throw new \Exception($e);
	    }
    }

    /**
     * Delete record
     *
     * @param $id
     * @return false|int
     */
    public function delete($id = null)
    {
        $sql = $this->buildQuery('delete');

        try {
            $statement = $this->db->prepare($sql);
            $statement->execute(["id" => $id]);
            return $statement->rowCount();
        } catch (\PDOException $e) {
	        throw new \Exception($e);
        }
    }

    /**
     * Load object by Id
     *
     * @param $id
     */
    public function load($id = null)
    {
        if (!empty($id)) {
            $this->id = $id;
        }

        if (empty($this->id)) {
            return null;
        }
        
        $this->loadByFields(['id' => $id]);
    }
    
    public function loadByFields(array $filters = [])
    {
        try {
            $data = $this->find($filters);
        } catch (\Exception $exception) {
            return new static();
        }
    	
    	if (!empty($data)) {
    		$this->set(reset($data));
	    }
    }

    protected function set($data)
    {
        if (!empty($data)) {
        	$this->id = $data[$this->primaryKey];
            $this->loaded = true;
            $this->data = $data;
        }
    }

    protected function bind($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    protected function buildQuery($action, $input = [])
    {
        $sql = '';
        
        switch ($action) {
            case "load":
                $sql = "SELECT * FROM {$this->table} WHERE id = ?";
                break;
            case "findAll":
                $sql = "SELECT * FROM {$this->table}";
                break;
            case "delete":
                $sql = " DELETE FROM {$this->table} WHERE id = :id";
                break;
            case "insert":
                $keys = array_keys($input);
                $fields = implode(", ", $keys);
                $values = array_map(function ($val) {
                    return ":$val";
                }, $keys);
                $valuesInput = implode(", ", $values);

                $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ($valuesInput);";
                break;
	        case "find":
		        $keys = array_keys($input);
		        $fields = array_map(function ($val) {
			        return "$val = :$val";
		        }, $keys);
		
		        $fieldsInput = implode(" AND ", $fields);
		        
		        $sql = "SELECT * FROM {$this->table} WHERE {$fieldsInput};";
		        break;
	        case "update":
		        $keys = array_keys($input);
		        $fields = array_map(function ($val) {
			        return "$val = :$val";
		        }, $keys);
		
		        $fieldsInput = implode(", ", $fields);
		        $sql = "UPDATE {$this->table} SET $fieldsInput WHERE id = :id";
	        	break;
        }

        return $sql;
    }

    protected function buildQueryFields($action, $input = [])
    {
        $fieldsInput = '';
        $keys = array_keys($input);

        switch ($action) {
            case "search":
                $fields = array_map(function ($val) {
                    return "$val = :$val";
                }, $keys);

                $fieldsInput = implode(" AND ", $fields);
                break;
            case "update":
                $fields = array_map(function ($val) {
                    return "$val = :$val";
                }, $keys);

                $fieldsInput = implode(", ", $fields);
                break;
        }

        return $fieldsInput;
    }
	
	/**
     * Execute query
     *
	 * @param string $sql
	 * @param array $input
	 * @return PDOStatement
	 */
    protected function execute($sql = '', $input = [])
    {
    	if (empty($sql)) {
    		false;
	    }
    	
	    try {
		    $statement = $this->db->prepare($sql);
		    
		    if (!$statement->execute($input)){
		    	return false;
		    }
		    
		    return $statement;
	    } catch (\PDOException $e) {
		    
	    	throw new \Exception($e);
	    }
    }

    /**
     * Prepare input data to only have matching data with cols
     * @param array $input
     * @return array
     */
    protected function prepareData($input = [])
    {
    	$input = ArrayHelper::only($input, $this->cols);
	    return array_filter($input);
    }
}