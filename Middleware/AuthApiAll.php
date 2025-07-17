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
class AuthApiAll
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
        if ( (isset($headers['Authorization']) && strlen($headers['Authorization']) >= 30) && (isset($headers['Utype']) && $headers['Utype']) ) {
            //
            $api_key = base64_decode($headers['Authorization']);
            $prod_type_id = (int)$headers['Utype'];
            //
            $results = AuthTokens::GetAccountByToken($prod_type_id, $api_key);
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
        $err_msg = ( $results && isset($results['error']) && $results['error'] ) ? $results['error'] : 'Access Denied. Missing or Invalid Api Key';
        //
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'text/html')
            ->write($err_msg);
    }
}