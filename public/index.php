<?php


//date_default_timezone_set('America/Los_Angeles');
date_default_timezone_set('America/Chihuahua');
//date_default_timezone_set('Etc/GMT+1');
//$date = date('m/d/Y h:i:s a', time());
//echo $date; exit;

// utils defines
define('DS', DIRECTORY_SEPARATOR);
define('UD', "/");

// path defines
define('PATH_PUBLIC', __DIR__);
define('PATH_BASE', dirname(dirname(__FILE__)));
//
define('PATH_APP', PATH_BASE.DS.'App');
define("PATH_VENDOR", PATH_BASE.DS."vendor");
define("PATH_STORAGE", PATH_BASE.DS."storage");
define('PATH_SCHEDULER', PATH_BASE.DS.'scheduler');
define("PATH_ROUTES", PATH_BASE.DS."ARoutes");
define("PATH_CONTROLLERS", PATH_BASE.DS."Controllers");
define("PATH_HELPERS", PATH_BASE.DS."Helpers");
define("PATH_CACHE", PATH_BASE.DS."cache");
define("PATH_VIEWS", PATH_BASE.DS."views");
//
//define("HOST_NAME", "phitphuel.us");
//
require PATH_BASE.DS.'constants.php';
require PATH_BASE.DS.'native_functions.php';


//
$settings = require PATH_BASE.DS.'settings.php';
//dd($settings);

//echo phpinfo(); exit;
//$tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir(); die($tmp_dir); exit;

/*
*
SE HA DESCARGADO LA VERSION MAS ACTUAL EL DIA 14 DE SEPTIEMBRE
CAMBIOS EN A VERSION:
https://www.slimframework.com/docs/v3/start/upgrade.html
 *
 * */
$prod_mode = false;



//
if ($settings['is_production']){
   error_reporting( E_ALL ^ ( E_NOTICE | E_WARNING | E_DEPRECATED ) );    
} else {
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ini_set('display_startup_errors', 1);
}

 



//----------------------------------------- Load Slim App

//
require PATH_VENDOR.DS.'autoload.php';



// 2. CONFIGURAR SESIONES AQUÍ (antes de crear la app)
$session_path = PATH_BASE.DS.'tmp'.DS.'sessions';
//echo $session_path; exit;
if (!is_dir($session_path)) {
    mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);



/*
// Iniciar sesión
session_start();

// Obtener información
$session_id = session_id();
$session_save_path = session_save_path();
$session_file = $session_save_path . '/sess_' . $session_id;

echo "<h2>🔍 Diagnóstico de Sesiones</h2>";

echo "<h3>📂 Configuración:</h3>";
echo "<strong>Save Path:</strong> " . $session_save_path . "<br>";
echo "<strong>Session ID:</strong> " . $session_id . "<br>";
echo "<strong>Archivo de sesión:</strong> " . $session_file . "<br>";
exit;
*/





// Creamos el Slim app junto con los settings
$app = new \Slim\App($settings);


//
require PATH_BASE.DS.'dependencies.php';

// las rutas van entre el inicio del app y run
require PATH_ROUTES.DS.'Routes.php';

//
$app->run();
