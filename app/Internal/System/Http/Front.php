<?php
namespace App\Internal\System\Http;

class Front
{
    /**
     * Singleton instance
     *
     * @var Front
     */
    private static $instance;

    /**
     * :id
     *
     * @var
     */
    protected $id;

    /**
     * HTTP methods
     *
     * @var
     */
    protected $requestMethod;

    /**
     * Controller handle request
     *
     * @var
     */
    protected $controllerName;

    /**
     * List of HTTP method that allowed to process
     *
     * @var string[]
     */
    protected $allowedMethods = ["POST","GET"];
    
    const DEFAULT_API_MODULE = 'api';

    /**
     * Singleton instance
     *
     * @return Front
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Dispatch an HTTP request to a controller/action.
     */
    public function dispatch()
    {
    	if (!$this->validateRequest()) {
		    header("HTTP/1.1 404 Not Found");
		    exit();
	    }
    	
        $this->_dispatch();
    }
    
    protected function _dispatch()
    {
    	try {
		    $objController = new $this->controllerName();
		    $actionName = $this->getAction();
		    $objController->{$actionName}();
	    } catch (\Exception $exception) {
            header("HTTP/1.1 500 Server Error");
            exit();
	    }
    }

    /**
     * Get action from request
     *
     * @return string
     */
	public function getAction()
	{
		switch ($this->requestMethod) {
			case 'GET':
				$actionName = "getAction";
				
				if (empty($this->id)) {
					$actionName = "index";
				}
				break;
			case 'POST':
				$actionName = "postAction";
				break;
		}
		
		return $actionName;
	}

    /**
     * Validate request
     * @return bool
     */
    protected function validateRequest()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode( '/', $uri );
	    
	    if (isset($uri[1]) && $uri[1] != self::DEFAULT_API_MODULE) {
		   return false;
	    }
	
	    if (!isset($uri[2])) {
		    return false;
	    }
	    
	    $controllerName = ucfirst(strtolower($uri[2])) . "Controller";
	    $controller = HTTP_PATH . "/Controllers/API/{$controllerName}.php";
	    
	    if (!file_exists($controller)) {
	    	return false;
	    }
		
	    require_once $controller;
	    
	    $this->controllerName = $controllerName;
	    
	    if (isset($uri[3])) {
		    if (is_numeric($uri[3])){
			    $this->id = $uri[3];
		    } else {
			    $this->requestMethod = $uri[3];
		    }
	    }
	    
	    $requestMethod = $_SERVER["REQUEST_METHOD"];
	    
	    if (!in_array($requestMethod, $this->allowedMethods)) {
	    	return false;
	    }
	    
	    $this->requestMethod = $requestMethod;
	    
	    return true;
    }
}