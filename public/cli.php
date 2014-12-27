<?php

use Phalcon\CLI\Console as ConsoleApp;
use Phalcon\DI\FactoryDefault\CLI as CliDI;

set_time_limit(0);

require(__DIR__ . '/../app/config/config.php');
require(__DIR__ . '/../app/library/PHPMailer/PHPMailerAutoload.php');
//include('/usr/share/php/libphp-phpmailer/class.phpmailer.php');

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
    $mail->SMTPSecure = 'tls';
    $mail->Host = 'mail.ohmcoe.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->Username = $config->gmail->username;
    $mail->Password = $config->gmail->password;
    $mail->From = $config->gmail->username;
    $mail->FromName = 'CoEproject';

    return $mail;
});

$di->set('projecttube', function() use ($config)
{
   return $config->tube->tube;
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