<?php
namespace Middleware;
/**
 *
 * CODIGOS DE ESTADO:
 * https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
 *
 */


use App\App;



//
class AuthSessionSupadmin
{





    public $session_user_type = null;
    public $login_url = null;
    


    //
    public function __construct()
    {
        $this->session_user_type = strtolower(APP_TYPE_SUPADMIN);
        $this->login_url = strtolower("/adm27/login");
    }






    //
    public function __invoke($request, $response, $next)
    {
        //
        $user_info = App::getAdminSession($this->session_user_type);
        //var_dump($user_info); exit;


        // USER SE ENCONTRO Y ACTIVO
        if ( isset($user_info['id']) && $user_info['id'] > 0 ){
            //
            $request = $request->withAttribute('ses_data', $user_info);
            //
            $response = $next($request, $response);
            return $response;
        }

        // USER NO SE ENCONTRO O ACTIVO
        return $response->withRedirect($this->login_url);
    }






}