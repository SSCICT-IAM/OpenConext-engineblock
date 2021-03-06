<?php

use SAML2\Compat\ContainerSingleton;

class EngineBlock_Application_Bootstrapper
{
    const CONFIG_FILE_DEFAULT       = 'configs/application.ini';
    const CONFIG_FILE_ENVIRONMENT   = '/etc/openconext/engineblock.ini';
    const P3P_HEADER = 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"';

    /**
     * @var EngineBlock_ApplicationSingleton
     */
    protected $_application;

    /**
     * @var bool
     */
    protected $_bootstrapped = false;

    /**
     * @param EngineBlock_ApplicationSingleton $application
     */
    public function __construct(EngineBlock_ApplicationSingleton $application)
    {
        $this->_application = $application;
    }

    /**
     * Bootstrap the application.
     *
     * Note that the order or bootstrapping is very important.
     *
     * @return EngineBlock_ApplicationSingleton Bootstrapped application singleton
     */
    public function bootstrap()
    {
        if ($this->_bootstrapped) {
            return $this;
        }

        $this->_bootstrapConfiguration();
        $this->_bootstrapSessionConfiguration();

        $this->_bootstrapPhpSettings();
        $this->_bootstrapErrorReporting();

        $this->_bootstrapSuperGlobalOverrides();
        $this->_bootstrapHttpCommunication();
        $this->_bootstrapSaml2();

        $this->_bootstrapLayout();
        $this->_bootstrapTranslations();

        $this->_bootstrapped = true;

        return $this;
    }

    protected function _bootstrapConfiguration()
    {
        if ($this->_application->getConfiguration()) {
            return;
        }

        $configProxy = new EngineBlock_Config_CacheProxy(
            $this->_getAllConfigFiles(),
            $this->_application->getDiContainer()->getApplicationCache()
        );
        $this->_application->setConfiguration($configProxy->load());
    }

    protected function _bootstrapSessionConfiguration()
    {
        session_set_cookie_params(
            0,
            $this->_application->getConfigurationValue('cookie_path', '/'),
            '',
            $this->_application->getConfigurationValue('use_secure_cookies', true),
            true
        );
        session_name('main');
    }

    /**
     * We need this because we may need to trick EngineBlock into thinking it's hosting on a different URL.
     *
     * Known uses:
     * * SAML Replaying with OpenConext-Engine-Test-Stand.
     *
     */
    protected function _bootstrapSuperGlobalOverrides()
    {
        $superGlobalManager = $this->_application->getDiContainer()->getSuperGlobalManager();
        if (!$superGlobalManager) {
            return;
        }

        $superGlobalManager->injectOverrides();
    }

    /**
     * Return a list of config files (default and environment overrides) that should be loaded
     *
     * @return array
     */
    protected function _getAllConfigFiles()
    {
        return array(
            ENGINEBLOCK_FOLDER_APPLICATION . self::CONFIG_FILE_DEFAULT,
            self::CONFIG_FILE_ENVIRONMENT,
        );
    }

    protected function _bootstrapHttpCommunication()
    {
        $httpRequest = EngineBlock_Http_Request::createFromEnvironment();
        $this->_application->getLogInstance()->info(
            sprintf(
                'Handling incoming request: %s %s',
                $httpRequest->getMethod(),
                $httpRequest->getUri()
            )
        );
        $this->_application->setHttpRequest($httpRequest);

        $response = new EngineBlock_Http_Response();
        // workaround, P3P is needed to support iframes like iframe gadgets in portals
        $response->setHeader('P3P', self::P3P_HEADER);
        $this->_application->setHttpResponse($response);
    }

    private function _bootstrapSaml2()
    {
        $container = new EngineBlock_Saml2_Container($this->_application->getLogInstance());
        ContainerSingleton::setContainer($container);
    }

    protected function _bootstrapPhpSettings()
    {
        $settings = $this->_application->getConfiguration()->phpSettings;
        if (!is_null($settings)) {
            $this->_setIniSettings($settings->toArray());
        }
    }

    protected function _setIniSettings($settings, $prefix = '')
    {
        foreach ($settings as $settingName => $settingValue) {
            if (is_array($settingValue)) {
                $this->_setIniSettings((array)$settingValue, $prefix . $settingName . '.');
            }
            else {
                ini_set($prefix . $settingName, $settingValue);
            }
        }
    }

    protected function _bootstrapErrorReporting()
    {
        $errorHandler = new EngineBlock_Application_ErrorHandler($this->_application);
        register_shutdown_function  (array($errorHandler, 'shutdown'));
        set_error_handler           (array($errorHandler, 'error'));
        set_exception_handler       (array($errorHandler, 'exception'));

        $this->_application->setErrorHandler($errorHandler);
    }

    protected function _bootstrapLayout()
    {
        $layout = new Zend_Layout();

        // Set a layout script path:
        $layout->setLayoutPath(ENGINEBLOCK_FOLDER_APPLICATION . 'layouts/scripts/');

        // Defaults
        $defaultsConfig = $this->_application->getConfiguration()->defaults;
        $layout->title  = $defaultsConfig->title;
        $layout->header = $defaultsConfig->header;

        // choose a different layout script:
        $layout->setLayout($defaultsConfig->layout);

        $this->_application->setLayout($layout);
    }

    protected function _bootstrapTranslations()
    {
        $translationFiles = array(
            'en' => ENGINEBLOCK_FOLDER_ROOT . 'languages/en.php',
            'nl' => ENGINEBLOCK_FOLDER_ROOT . 'languages/nl.php'
        );
        $translationCacheProxy = new EngineBlock_Translate_CacheProxy(
            $translationFiles,
            $this->_application->getDiContainer()->getApplicationCache()
        );

        $locale = $this->_application->getDiContainer()->getLocaleProvider()->getLocale();

        $translator = $translationCacheProxy->load();
        $translator->setLocale($locale);

        $this->_application->setTranslator($translator);
    }
}
