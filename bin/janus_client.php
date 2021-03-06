#!/usr/bin/php
<?php

if (PHP_SAPI !== 'cli') {
    die('Command line access only' . PHP_EOL);
}

if (!isset($argv[1])) {
    die(
        'Please supply a method to call, format: ./bin/janus_client.php {method name}[ {querystring}]' . PHP_EOL .
        'Example: ./bin/janus_client.php getMetadata "entityid=https://example.edu&keys=certData"' . PHP_EOL
    );
}

ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);

try {

    require_once __DIR__ . '/../app/bootstrap.php.cache';
    require_once __DIR__ . '/../app/AppKernel.php';

    $symfonyEnvironment = getenv('SYMFONY_ENV') ?: 'prod';
    $kernel = new AppKernel($symfonyEnvironment, false);
    $kernel->boot();

    $application = EngineBlock_ApplicationSingleton::getInstance();

    $config = EngineBlock_ApplicationSingleton::getInstance()->getConfiguration()->serviceRegistry;
    $restClient = new Janus_Rest_Client($config->location, $config->user, $config->user_secret);

    try {

        $methodName = $argv[1];
        $restClient->$methodName();

        if (isset($argv[2])) {
            $arguments = array();
            parse_str($argv[2], $arguments);
            foreach ($arguments as $argumentName => $argumentValue) {
                $restClient->$argumentName($argumentValue);
            }
        }

        $result = $restClient->get();

        var_dump($restClient->getHttpClient()->getLastRequest());
        var_dump($restClient->getHttpClient()->getLastResponse()->getHeadersAsString());
        var_dump($restClient->getHttpClient()->getLastResponse()->getBody());
        var_dump($result);
    } catch (Exception $e) {
        var_dump($e);
        var_dump($restClient->getHttpClient()->getLastRequest());
        if ($restClient->getHttpClient()->getLastResponse()) {
            var_dump($restClient->getHttpClient()->getLastResponse()->getHeadersAsString());
            var_dump($restClient->getHttpClient()->getLastResponse()->getBody());
        }
    }
} catch (Exception $e) {
    var_dump($e);
}

