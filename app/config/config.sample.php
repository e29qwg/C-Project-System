<?php

    $config = new Phalcon\Config(array(
        'maintain_mode' => array(
            'active' => false 
        ),
        'database' => array(
            'adapter' => 'Mysql',
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'name' => ''
        ),
        'phalcon' => array(
            'controllersDir' => '../app/controllers/',
            'modelsDir' => '../app/models/',
            'viewsDir' => '../app/views/',
            'pluginsDir' => '../app/plugins/',
            'libraryDir' =>'../app/library/',
            'formsDir' => '../app/forms/',
            'baseUri' => '/'
        ),
    ));

?>
