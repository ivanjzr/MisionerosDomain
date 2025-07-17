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



//
class AuthApiStore
{



    //
    public static $only_active = true;


    //
    public function __construct($only_active = true)
    {
        self::$only_active = $only_active;
    }




    //
    public function __invoke($request, $response, $next)
    {

        //
        $results = [];
        //
        $headers = apache_request_headers();
        //Helper::printFull($headers); exit;
        //
        $request = $request->withAttribute('ses_data', false);

        //
        if ( (isset($headers['Authorization']) && strlen($headers['Authorization']) >= 30) ) {

            //echo $headers['Authorization']; exit;
            $api_key = base64_decode($headers['Authorization']);
            //
            $results = AuthTokens::GetAccountByToken(PROD_TYPE_STORE_ID, $api_key);
            //Helper::printFull($results); exit;
            //
            if ( isset($results['id']) && $results['id'] ){
                //
                $request = $request->withAttribute('ses_data', $results);
                $response = $next($request, $response);
                return $response;
            }
        }

        //
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'text/html')
            ->write("api call not authorized");
    }
}