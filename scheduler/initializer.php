<?php


// utils defines
define('DS', DIRECTORY_SEPARATOR);
define('UD', "/");

// path defines
define('PATH_PUBLIC', __DIR__);
define('PATH_BASE', dirname(dirname(__FILE__)));
//
define('PATH_SCHEDULER', PATH_BASE.DS.'scheduler');
define('PATH_APP', PATH_BASE.DS.'App');
define("PATH_VENDOR", PATH_BASE.DS."vendor");
define("PATH_CONTROLLERS", PATH_APP.DS."Controllers");
define("PATH_HELPERS", PATH_BASE.DS."Helpers");
define("PATH_CACHE", PATH_BASE.DS."cache");
define("PATH_VIEWS", PATH_BASE.DS."views");
//
require PATH_BASE.DS.'native_functions.php';



//
require PATH_VENDOR.DS.'autoload.php';



