<?php

//
//ini_set( 'session.cookie_httponly', 1 );
//ini_set( 'session.cookie_secure', 1 );
//ini_set( 'session.cookie_domain', "phitphuel.us" );
//echo phpinfo(); exit;


// Report simple running errors
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Reporting E_NOTICE can be good too (to report uninitialized
// variables or catch variable name misspellings ...)
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

// Report all errors except E_NOTICE
// This is the default value set in php.ini
//error_reporting(E_ALL & ~E_NOTICE);
// For PHP < 5.3 use: E_ALL ^ E_NOTICE

// Report all PHP errors (see changelog)
//error_reporting(E_ALL);

// Report all PHP errors
//error_reporting(-1);

// Same as error_reporting(E_ALL);
//ini_set('error_reporting', E_ALL);



//
$lang_path = "/".getBrowserLang()."/";
//echo $lang_path; exit;




require PATH_ROUTES.DS."RoutesPublic.php";
require PATH_ROUTES.DS."RoutesApi.php";
require PATH_ROUTES.DS."RoutesAdmin.php";
require PATH_ROUTES.DS."RoutesSupAdmin.php";

// Esta tiene que ir al final
require PATH_ROUTES.DS."RoutesWebSite.php";
