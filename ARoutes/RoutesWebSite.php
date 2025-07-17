<?php


//
use \App\Apps\Apps;
use Helpers\Helper;


// Método recomendado (más preciso)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



//
$app->get('[/]', function($request, $response, $args) use ($app) {


    


    




    //
    $app_info = Apps::GetApp();
    //Helper::printFull($app_info); exit;
    //
    if ( isset($app_info['id']) && $app_info['domain_controller'] ) {



        //
        $views_name = $app_info['views_name'];


        //define("PATH_ABS_VIEWS", PATH_VIEWS_SITES . DS . $views_name);
        define("PATH_REL_VIEWS", "sites/{$views_name}/");
        define("appId", $app_info['id']);

        // PROD MODE
        define("hostUrl", "http://".$app_info['current_host']);
        // DEV MODE
        //define("hostUrl", getProtocol().$app_info['current_host']); /* $app_info['domain_prod'] */


        define("appName", $app_info['app_name']);
        define("fbAppId", getFbId());


        //
        $site_controller_path = "Controllers\\Sites\\" . $app_info['domain_controller'];
        //echo $site_controller_path; exit;

        //
        //$app->get("/store/{store_id:[0-9]{0,11}}/{store_url}[/]", $site_controller_path.":ViewStoreInfo");
        $app->get("/create-account[/]", $site_controller_path.":ViewCreateAccount");
        $app->get("/stores/create-account[/]", $site_controller_path.":ViewCreateAccountStore");
        $app->get("/[{path:.*}]", $site_controller_path.":ViewAll");




    } else {
        //
        return $response->withJson('', 404);
    }


    

});



