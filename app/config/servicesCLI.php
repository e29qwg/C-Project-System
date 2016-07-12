<?php

$di = new \Phalcon\Di\FactoryDefault\Cli();

$di->set('debug', function () use ($config) {
    return $config->debug;
});


$di->set('queue', function () use ($config)
{
    $queue = new Phalcon\Queue\Beanstalk(array(
        'host' => $config->queue->host,
        'port' => '11300'
    ));
    return $queue;
});

$di->setShared('config', function () use ($config)
{
    return $config;
});

$di->setShared('db', function () use ($config)
{
    $dbConfig = $config->database->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

    return new $class($dbConfig);
});
