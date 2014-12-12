<?php

/**
 * A Caching Proxy for the Service Registry, will cache all function calls.
 *
 * Can even detect Service Registry problems and chug along on the (stale) cache.
 */
class Janus_Client_CacheProxy
{
    const DEFAULT_LIFETIME = 5;

    public function __call($name, $arguments)
    {
        $client = $this->_getClient();

        $cache = $this->_getCacheFrontend();
        if (!$cache) {
            return call_user_func_array(array($client, $name), $arguments);
        }

        // Clone the original client because calling it will alter the $client object
        // making it impossible to reuse stale cache
        $originalClient = clone $client;

        try {
            return $cache->call(array($client, $name), $arguments);

        } catch(Exception $e) { // Whoa, something went wrong, maybe the SR is down? Trying to use stale cache...
            $httpClient = $client->getRestClient()->getHttpClient();

            $application = EngineBlock_ApplicationSingleton::getInstance();
            $application->getLogInstance()->attach(
                $httpClient->getLastRequest(),
                'HTTP Request'
            );

            if ($httpClient->getLastResponse()) {
                $response = $httpClient->getLastResponse();
                $application->getLogInstance()->attach(
                    $response->getHeadersAsString() . PHP_EOL . $response->getBody(),
                    'HTTP Response'
                );
            }

            $e = new Janus_Client_CacheProxy_Exception(
                "Unable to access JANUS?!? Using stale cache",
                EngineBlock_Exception::CODE_WARNING,
                $e
            );
            $application->reportError($e);

            // Give any stale cache some more time
            $callback = array($originalClient, $name);
            $cacheId = $cache->makeId($callback, $arguments);
            $cacheBackend = $cache->getBackend();
            $data = $cacheBackend->load($cacheId, TRUE);
            if ($data !== false) {
                $cacheBackend->save($data, $cacheId, array(), self::DEFAULT_LIFETIME);

                try {
                    // And try to use that cache.
                    return $cache->call($callback, $arguments);
                } catch (Exception $e) {
                    throw new Janus_Client_CacheProxy_Exception(
                        "Unable to contact JANUS and unable to reuse stale cache!",
                        EngineBlock_Exception::CODE_ALERT,
                        $e
                    );
                }
            }

            throw new Janus_Client_CacheProxy_Exception(
                "Unable to contact JANUS and no stale cache found for possible reuse!",
                EngineBlock_Exception::CODE_ALERT,
                $e
            );
        }
    }

    /**
     * @return Zend_Cache_Frontend_Function
     */
    protected function _getCacheFrontend()
    {
        $application = EngineBlock_ApplicationSingleton::getInstance();
        $serviceRegistryConfig = $application->getConfiguration()->serviceRegistry;
        if (!isset($serviceRegistryConfig->caching)) {
            return null;
        }
        $cachingConfiguration = $serviceRegistryConfig->caching;

        $backendCaching        = $cachingConfiguration->backend->get('name', 'File');
        $backendCachingOptions = $cachingConfiguration->backend->options->toArray();

        $cache = Zend_Cache::factory(
            'Function',
            $backendCaching,
            array(),
            $backendCachingOptions
        );
        $cache->setLifetime($cachingConfiguration->get('lifetime', self::DEFAULT_LIFETIME));
        return $cache;
    }

    protected function _getClient()
    {
        return new Janus_Client();
    }
}
