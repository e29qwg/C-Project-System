<?php

use Phalcon\CLI\Console as ConsoleApp;
use Phalcon\DI\FactoryDefault\CLI as CliDI;

require(__DIR__ . '/../app/config/config.php');
include('/usr/share/php/libphp-phpmailer/class.phpmailer.php');

$di = new CliDI();

$loader = new \Phalcon\Loader();

$loader->registerDirs(array(
    $config->phalcon->controllersDir,
    $config->phalcon->modelsDir
));

$loader->register();

$console = new ConsoleApp();
$console->setDI($di);

$di->set('mail', function () use ($config)
{
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->Host = 'ssl://smtp.gmail.com';
    $mail->Port = 465;
    $mail->SMTPAuth = true;
    $mail->Username = $config->gmail->username;
    $mail->Password = $config->gmail->password;
    $mail->From = 'xcoephuket@gmail.com';
    $mail->FromName = 'xcoephuket@gmail.com';

    return $mail;
});

$di->set('queue', function ()
{
    $queue = new Phalcon\Queue\Beanstalk(array(
        'host' => '127.0.0.1'
    ));
    return $queue;
});

$di->set('db', function () use ($config)
{
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->name,
        'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
    ));
});

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

try
{
    // handle incoming arguments
    $console->handle($arguments);
} catch (\Phalcon\Exception $e)
{
    echo $e->getMessage();
    exit(255);
}

?>