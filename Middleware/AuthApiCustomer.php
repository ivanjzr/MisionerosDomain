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
class AuthApiCustomer
{



    public $auth_optional = false;


    //
    public function __construct($auth_optional = false)
    {
        $this->auth_optional = $auth_optional;
    }




    //
    public function __invoke($request, $response, $next)
    {
        //
        $headers = apache_request_headers();
        //dd($headers); exit;

        //
        if ( (isset($headers['Authorization']) && strlen($headers['Authorization'])) ) {

            //echo $headers['Authorization']; exit;
            $api_key = base64_decode($headers['Authorization']);
            //
            $results = AuthTokens::GetAccountByToken($api_key);
            //dd($results); exit;

            //
            if ( isset($results['id']) && $results['id'] ){
                //
                $request = $request->withAttribute('ses_data', $results);
                $response = $next($request, $response);
                return $response;
            }
            //
            else if ( $this->auth_optional ){
                //
                $request = $request->withAttribute('ses_data', null);
                $response = $next($request, $response);
                return $response;
            }
        }
        //
        else if ( $this->auth_optional ){
            //
            $request = $request->withAttribute('ses_data', null);
            $response = $next($request, $response);
            return $response;
        }

        //
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'text/html')
            ->write("api call not authorized");
    }
}