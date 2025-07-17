<?php
namespace Middleware;
/**
 *
 * CODIGOS DE ESTADO:
 * https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
 *
 */


//
use App\Auth\AuthTokens;
use Helpers\Helper;


//
class AuthApiOptional
{



    //
    public function __construct()
    {        
    }




    //
    public function __invoke($request, $response, $next)
    {
        //
        $results = [];
        //
        $headers = apache_request_headers();

        // Debug Middleware Anterior de "SiteAccountHandler"
        // $app = $request->getAttribute("app");
        //Helper::printFull($headers); exit;

        //
        $request = $request->withAttribute('token_data', null);

        //
        if ( (isset($headers['Authorization']) && strlen($headers['Authorization']) >= 30) ) {
            //
            $api_key = base64_decode($headers['Authorization']);
            //
            $results = AuthTokens::GetAccountByToken($api_key);
            //Helper::printFull($results); exit;
            //
            if ( isset($results['id']) && $results['id'] ){
                $request = $request->withAttribute('token_data', $results);                
            }
        }

        //
        $response = $next($request, $response);
        return $response;
    }



}