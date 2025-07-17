<?php
namespace Middleware;


//
use App\Apps\Apps;
use Helpers\Helper;



//
class SiteAccountHandler
{





    public static $custom_domain;



    //
    public function __construct(){
    }






    //
    public function __invoke($request, $response, $next)
    {
        //
        $app_info = Apps::GetApp();
        //var_dump($app_info); exit;
        if (isset($app_info['id'])){
            //
            $request = $request->withAttribute('app', $app_info);
            //
            $response = $next($request, $response);
            return $response;
        }

        //
        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->write("Domain Not Associated to Platform");
    }






}