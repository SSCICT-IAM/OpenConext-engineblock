<?php

define('ENGINEBLOCK_FOLDER_LIBRARY'    , dirname(__FILE__) . '/../');
define('ENGINEBLOCK_FOLDER_APPLICATION', dirname(__FILE__) . '/../../application/');
define('ENGINEBLOCK_FOLDER_MODULES'    , ENGINEBLOCK_FOLDER_APPLICATION . 'modules/');

class EngineBlock_ApplicationSingleton_BootstrapException extends Exception
{
}

class EngineBlock_ApplicationSingleton
{
    /**
     * @var EngineBlock_ApplicationSingleton
     */
    protected static $s_instance;

    protected $_applicationEnvironmentId;

    protected $_httpRequest;
    protected $_httpResponse;

    protected $_metaData;

    protected $_configuration;

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (!isset(self::$s_instance)) {
            self::$s_instance = new self();
        }
        return self::$s_instance;
    }

    public static function autoLoad($className)
    {
        static $s_modules = array();
        static $s_libraries = array();

        // Find /modules/ directories
        if (empty($s_modules)) {
            $iterator = new DirectoryIterator(ENGINEBLOCK_FOLDER_MODULES);
            foreach ($iterator as $item) {
                if ($item->isDir() && !$item->isDot()) {
                    $s_modules[] = (string)$item;
                }
            }
        }

        // Find /library/ directories
        if (empty($s_libraries)) {
            $iterator = new DirectoryIterator(ENGINEBLOCK_FOLDER_LIBRARY);
            foreach ($iterator as $item) {
                if ($item->isDir() && !$item->isDot()) {
                    $s_libraries[] = (string)$item;
                }
            }
        }

        $classNameParts = explode('_', $className);

        // Library class
        if (in_array($classNameParts[0], $s_libraries)) {
            $fileName = implode('/', explode('_', $className)).'.php';

            if (!file_exists(ENGINEBLOCK_FOLDER_LIBRARY . $fileName)) {
                return false;
            }

            include ENGINEBLOCK_FOLDER_LIBRARY . $fileName;

            return true;
        }

        // Module class?
        if (in_array($classNameParts[0], $s_modules)) {
            $fileName = implode('/', explode('_', $className)).'.php';

            if (!file_exists(ENGINEBLOCK_FOLDER_MODULES . $fileName)) {
                return false;
            }

            include ENGINEBLOCK_FOLDER_MODULES . $fileName;

            return true;
        }

        return false;
    }

    public function bootstrap($applicationEnvironmentId)
    {
        $this->_applicationEnvironmentId = $applicationEnvironmentId;
        
        $this->bootstrapAutoLoading();
        $this->bootstrapConfiguration();
        $this->bootstrapHttpCommunication();
        $this->bootstrapDateTime();
    }

    protected function bootstrapAutoLoading()
    {
        spl_autoload_register(array($this, 'autoLoad'));
    }

    protected function bootstrapConfiguration()
    {
        $config = array();
        $configFilePath = ENGINEBLOCK_FOLDER_APPLICATION . 'configs/application.php';
        require $configFilePath;

        if (!isset($config[$this->_applicationEnvironmentId])) {
            $message = "No configuration in {$configFilePath} for application environment ID '{$this->_applicationEnvironmentId}'";
            throw new EngineBlock_ApplicationSingleton_BootstrapException($message);
        }

        $this->setConfiguration($config[$this->_applicationEnvironmentId]);
    }

    protected function bootstrapHttpCommunication()
    {
        $this->setHttpRequest(EngineBlock_HTTP_Request::createFromEnvironment());
        $this->setHttpResponse(new EngineBlock_Http_Response());
    }

    protected function bootstrapDateTime()
    {
        date_default_timezone_set($this->_configuration['default_timezone']);
    }

    public function setConfiguration($applicationConfiguration)
    {
        $this->_configuration = $applicationConfiguration;
        return $this;
    }

    public function getConfiguration()
    {
        return $this->_configuration;
    }

    public function getConfigurationValue($key, $default = null)
    {
        if (isset($this->_configuration[$key])) {
            return $this->_configuration[$key];
        }

        return $default;
    }

    public function setHttpRequest($request)
    {
        $this->_httpRequest = $request;
        return $this;
    }

    /**
     * @return EngineBlock_Http_Request
     */
    public function getHttpRequest()
    {
        return $this->_httpRequest;
    }

    public function setHttpResponse($response)
    {
        $this->_httpResponse = $response;
        return $this;
    }

    /**
     * @return EngineBlock_Http_Response
     */
    public function getHttpResponse()
    {
        return $this->_httpResponse;
    }
}