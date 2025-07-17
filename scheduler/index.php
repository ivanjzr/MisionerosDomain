<?php

// include initializer for variables use
require 'initializer.php';

//
$task_id 		= $argv[1];
$handler_file 	= $argv[2];


//
$handler_file = getDotedfile($handler_file);

//
$handler_file_path = PATH_SCHEDULER.DS.$handler_file;
//echo $handler_file_path; exit;




//
if ( file_exists($handler_file_path) ){


    //
    $settings = require PATH_BASE.DS.'settings.php';


    // Creamos el Slim app junto con los settings
    $app = new \Slim\App($settings);


    require PATH_BASE.DS.'dependencies.php';


    // archivo a ejecutar
    require $handler_file_path;


    //
    $app->run();

}

//
else {

    echo "handler file does not exists"; return;

}
