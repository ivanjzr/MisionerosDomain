<?php

use \App\Apps\Apps;
use Helpers\Helper;

// Función helper para manejar las rutas
function handleRoute($request, $response, $args, $app, $method) {
    $app_info = Apps::GetApp();
    
    if (isset($app_info['id']) && $app_info['domain_controller']) {
        
        // Definir constantes (solo si no están definidas)
        if (!defined('PATH_REL_VIEWS')) {
            $views_name = $app_info['views_name'];
            $domain_controller = $app_info['domain_controller'];

            define("PATH_REL_VIEWS", "sites" . DS . $views_name);
            define("PATH_ABS_VIEWS", PATH_VIEWS .DS . "sites" . DS . $views_name);
            define("accountId", $app_info['account_id']);
            define("appId", $app_info['id']);
            define("hostUrl", "http://".$app_info['current_host']);
            define("appName", $app_info['app_name']);
            define("fbAppId", getFbId());
        }
        
        // Llamar al controller directamente
        $controller_class = "Controllers\\Sites\\{$app_info['domain_controller']}";
        
        if (class_exists($controller_class)) {
            $controller = new $controller_class($app->getContainer());
            return $controller->$method($request, $response, $args);
        } else {
            return $response->write("Controller no encontrado: " . $controller_class);
        }
        
    } else {
        return $response->withJson('', 404);
    }
}

// Ruta para home (/, /home, /index) - usar una sola ruta que maneje múltiples paths
$app->get('/[{route:home|index|$}]', function($request, $response, $args) use ($app) {
    return handleRoute($request, $response, $args, $app, 'ViewHome');
});

// Ruta para contact
$app->get('/contact[/]', function($request, $response, $args) use ($app) {
    return handleRoute($request, $response, $args, $app, 'ViewContact');
});

// Catch-all para cualquier otra ruta - redirigir a home
$app->get('/{path:.*}', function($request, $response, $args) use ($app) {
    return $response->withRedirect('/', 301);
});