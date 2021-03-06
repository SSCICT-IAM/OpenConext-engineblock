<?php

use OpenConext\EngineBlockFunctionalTestingBundle\Fixtures\DataStore\JsonDataStore;
use OpenConext\EngineBlockFunctionalTestingBundle\Fixtures\SuperGlobalsFixture;

class EngineBlock_Application_SuperGlobalManager
{
    /**
     * File where.
     */
    const FILE = 'tmp/eb-fixtures/superglobals.json';

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $_logger;

    public function __construct()
    {
        $this->_logger = EngineBlock_ApplicationSingleton::getLog();
    }

    public function injectOverrides()
    {
        $jsonDataStore = new JsonDataStore(ENGINEBLOCK_FOLDER_ROOT . self::FILE);
        $fixture = new SuperGlobalsFixture($jsonDataStore);

        $overrides = $fixture->getAll();

        foreach ($overrides as $superGlobalName => $values) {
            $superGlobalName = '_' . $superGlobalName;

            global $$superGlobalName;
            $global = &$$superGlobalName;

            foreach ($values as $name => $value) {
                $this->_logger->notice(
                    sprintf('Overwriting $%s[%s]', $superGlobalName, $name),
                    array('super_global' => array('from' => $global[$name], 'to' => $value))
                );

                $global[$name] = $value;
            }
        }
        return true;
    }
}
