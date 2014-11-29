<?php
   
error_reporting(E_ALL);

date_default_timezone_set('Asia/Bangkok');

try
{
    require(__DIR__.'/../app/config/config.php');
    require(__DIR__.'/../app/library/glib.php');
    require_once(__DIR__.'/../app/library/PHPExcel/Classes/PHPExcel.php');
    require_once(__DIR__.'/../app/library/PHPExcel/Classes/PHPExcel/IOFactory.php');

    $loader = new \Phalcon\Loader();

    $loader->registerDirs(
        array(
            $config->phalcon->controllersDir,
            $config->phalcon->pluginsDir,
            $config->phalcon->libraryDir,
            $config->phalcon->modelsDir,
            $config->phalcon->formsDir
        )
    );

    $loader->register();

    $di = new \Phalcon\DI\FactoryDefault();

    $di->set('mode', function () use ($config) {
        $mode = $config->maintain_mode->active;
        return $mode;
    });

    $di->set('dispatcher', function() use ($di) {

        $eventsManager = $di->getShared('eventsManager');

        $security = new Security($di);

        $eventsManager->attach('dispatch', $security);

        $dispatcher = new \Phalcon\Mvc\Dispatcher();
        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
    });


    $di->set('url', function() use ($config) {
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri($config->phalcon->baseUri);
        return $url;
    });

    $di->set('view', function() use ($config) {
        $view = new \Phalcon\Mvc\View();
        $view->setViewsDir($config->phalcon->viewsDir);
        return $view;
    });

    $di->set('db', function() use ($config) {
        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            'host' => $config->database->host,
            'username' => $config->database->username,
            'password' => $config->database->password,
            'dbname' => $config->database->name,
            'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
        ));
    });

    $di->set('session', function() {
        $session = new Phalcon\Session\Adapter\Files();
		$session->setOptions(array(
			'uniqueId' => 'project'
		));
        $session->start();
        return $session;
    });

    $di->set('flash', function() {
        $flash = new Phalcon\Flash\Direct(array(
            'error' => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info'
        ));

        return $flash;
    });

    $di->set('flashSession', function() {
        $flashSession = new Phalcon\Flash\Session(array(
            'error' => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info'
        ));

        return $flashSession;
    });
	
	$di->set('ShowExcel', function () {
		return new ShowExcel();
	});

	$di->set('CheckQuota', function() {
		return new CheckQuota();
	});

    $di->set('Exam', function() {
        return new Exam();
    });

    $di->set('Score', function() {
        return new Score();
    });

    $di->set('DownloadFile', function() {
        return new DownloadFile();
    });

    $di->set('elements', function() {
        return new Elements();
    });

    $di->set('PSUService', function() {
        return new PSUService();
    });

    $di->set('Topic', function() {
        return new Topic();
    });

    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);

    echo $application->handle()->getContent();
}
catch (Phalcon\Exception $e)
{
    echo $e->getMessage();
}
catch (PDOException $e)
{
    echo $e->getMessage();
}

?>
