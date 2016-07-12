<?php

set_time_limit(0);

date_default_timezone_set('Asia/Bangkok');

define('APP_PATH', __DIR__.'/..');

try {

    /**
     * Read the configuration
     */
    $config = include APP_PATH . "/app/config/config.php";

    if ($config->debug)
        error_reporting(E_ALL);
    else
        error_reporting(0);
    /**
     * Read auto-loader
     */
    include APP_PATH . "/app/config/loader.php";

    /**
     * Read services
     */
    include APP_PATH . "/app/config/servicesCLI.php";

    /**
     * Handle the request
     */
    $application = new \Phalcon\CLI\Console ($di);

    $arguments = array();
    foreach ($argv as $k => $arg)
    {
        if ($k == 1)
        {
            $arguments['task'] = $arg;
        }
        elseif ($k == 2)
        {
            $arguments['action'] = $arg;
        }
        elseif ($k >= 3)
        {
            $arguments['params'][] = $arg;
        }
    }

    $application->handle($arguments);

} catch (\Exception $e) {

    if ($config->debug)
    {
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
