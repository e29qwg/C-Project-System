<?php

$config = new Phalcon\Config(array(
    'maintain_mode' => array(
        'active' => false
    ),
    'progress' => array(
        'delay' => '604800'
    ),
    'database' => array(
        'adapter' => 'Mysql',
        'host' => 'localhost',
        'username' => '',
        'password' => '',
        'name' => '',
        'charset' => 'utf8'
    ),
    'databaselog' => [
        'adapater' => 'Mysql',
        'host' => 'localhost',
        'username' => '',
        'password' => '',
        'dbname' => '',
        'charset' => 'utf8'
    ],
    'phalcon' => array(
        'controllersDir' => APP_PATH . '/app/controllers/',
        'modelsDir' => APP_PATH . '/app/models/',
        'viewsDir' => APP_PATH . '/app/views/',
        'pluginsDir' => APP_PATH . '/app/plugins/',
        'libraryDir' => APP_PATH . '/app/library/',
        'formsDir' => APP_PATH . '/app/forms/',
        'taskDir' => APP_PATH.'/app/task/',
        'baseUri' => '/',
        'furl' => ''
    ),
    'oauth' => array(
        'client_id' => '',
        'client_secret' => '',
        'url' => '',
        'authorize_url' => '',
        'token_url' => '',
        'profile_url' => ''
    )
));
