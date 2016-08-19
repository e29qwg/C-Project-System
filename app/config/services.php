<?php

require_once(__DIR__ . '/../library/PHPExcel/Classes/PHPExcel.php');
require_once(__DIR__ . '/../library/PHPExcel/Classes/PHPExcel/IOFactory.php');
require_once(__DIR__ . '/../library/html2pdf/html2pdf.class.php');

$di = new \Phalcon\DI\FactoryDefault();

$di->set('debug', function () use ($config)
{
    return $config->debug;
});

$di->set('permission', function ()
{
    return new Permission();
});

$di->set('oauth', function () use ($config)
{
    return $config->oauth;
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

$di->set('mode', function () use ($config)
{
    $mode = $config->maintain_mode->active;
    return $mode;
});

$di->set('dispatcher', function () use ($di)
{

    $eventsManager = $di->getShared('eventsManager');

    $security = new Security($di);

    $eventsManager->attach('dispatch', $security);

    $dispatcher = new \Phalcon\Mvc\Dispatcher();
    $dispatcher->setEventsManager($eventsManager);

    return $dispatcher;
});

$di->set('transactionManager', function ()
{
    return new \Phalcon\Mvc\Model\Transaction\Manager();
});

$di->set('url', function () use ($config)
{
    $url = new \Phalcon\Mvc\Url();
    $url->setBaseUri($config->application->baseUri);
    $url->setStaticBaseUri($config->application->baseUri);
    return $url;
});

$di->set('furl', function () use ($config)
{
    return $config->application->furl;
});

$di->set('view', function () use ($config)
{
    $view = new \Phalcon\Mvc\View();
    $view->setViewsDir($config->application->viewsDir);
    return $view;
});

$di->setShared('db', function () use ($config)
{
    $dbConfig = $config->database->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

    return new $class($dbConfig);
});

$di->setShared('dbLog', function () use ($config)
{
    $dbConfig = $config->databaselog->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

    return new $class($dbConfig);
});


$di->set('session', function ()
{
    $session = new Phalcon\Session\Adapter\Files();
    $session->setOptions(array(
        'uniqueId' => 'project'
    ));
    $session->start();
    return $session;
});

$di->set('flash', function ()
{
    $flash = new Phalcon\Flash\Direct(array(
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ));

    return $flash;
});

$di->set('flashSession', function ()
{
    $flashSession = new Phalcon\Flash\Session(array(
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ));

    return $flashSession;
});

$di->set('ShowExcel', function ()
{
    return new ShowExcel();
});

$di->set('CheckQuota', function ()
{
    return new CheckQuota();
});

$di->set('Exam', function ()
{
    return new Exam();
});

$di->set('Score', function ()
{
    return new Score();
});

$di->set('DownloadFile', function ()
{
    return new DownloadFile();
});

$di->set('elements', function ()
{
    return new Elements();
});

$di->set('PSUService', function ()
{
    return new PSUService();
});

$di->set('Topic', function ()
{
    return new Topic();
});