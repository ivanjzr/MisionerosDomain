<?php
namespace Middleware;
/**
 *
 * CODIGOS DE ESTADO:
 * https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
 *
 */


//
use App\Stores\CompaniesAuthTokens;


//
class AuthApiUserPrev
{





    //
    public function __construct($is_admin = false)
    {
        $this->is_admin = $is_admin;
    }




    //
    public function __invoke($request, $response, $next)
    {

        //
        $results = [];
        //
        $headers = apache_request_headers();


        //
        if ( isset($headers['Authorization']) && strlen($headers['Authorization']) >= 30) {

            //
            $api_key = base64_decode($headers['Authorization']);
            //echo $api_key; exit;

            //
            $results = CompaniesAuthTokens::GetActiveCompanyByToken($api_key, false);
            //var_dump($results); exit;

            //
            if ( isset($results['id']) && $results['id'] ){
                //
                $results['utype'] = "user";
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