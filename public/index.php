<?php
define('APP_PATH', realpath('..'));

date_default_timezone_set('Asia/Bangkok');

try
{
    $config = include APP_PATH . "/app/config/config.php";

    if ($config->debug)
        error_reporting(E_ALL);
    else
        error_reporting(0);

    include APP_PATH . "/app/config/loader.php";

    include APP_PATH . "/app/config/services.php";


    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);

    echo $application->handle()->getContent();
}
catch (\Exception $e)
{

    if ($config->debug)
    {
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
