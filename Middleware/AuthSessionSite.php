<?php
namespace Middleware;
/**
 *
 * CODIGOS DE ESTADO:
 * https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
 *
 */


//
use App\Models\Users;


//
class AuthSessionSite
{





    //
    public function __construct()
    {
        $this->user_type_id = APP_TYPE_SITE;
    }




    //
    public function __invoke($request, $response, $next)
    {

        //
        $headers = apache_request_headers();


        //
        $request = $request->withAttribute('user_data', false);


        //
        if ( isset($headers['Authorization']) && $headers['Authorization'] ) {

            //
            $api_key = $headers['Authorization'];

            //
            $results = Users::GetByApiKey($api_key);

            //
            if ( isset($results['id']) && $results['id'] ){

                //
                $request = $request->withAttribute('user_data', array(
                    "id" => $results['id'],
                    "name" => $results['name'],
                    "type_id" => 2
                ));

                //
                $response = $next($request, $response);

                //
                return $response;
            }
        }

        //
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'text/html')
            ->write('Access Denied. Missing or Invalid Api Key');
    }
}