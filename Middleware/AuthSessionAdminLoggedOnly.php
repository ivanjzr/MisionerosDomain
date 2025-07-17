<?php
namespace Middleware;
/**
 *
 * CODIGOS DE ESTADO:
 * https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
 *
 */


use App\App;
use App\Users\Users;
use App\Users\UsersSucursales;




/*
 *
 * VERIFICA QUE UNICAMENTE ESTE LOGEADO UN USER
 *
 * */
class AuthSessionAdminLoggedOnly
{




    public $session_user_type = null;
    public $login_url = null;



    //
    public function __construct()
    {
        $this->session_user_type = strtolower(APP_TYPE_ADMIN);
        $this->login_url = strtolower("/admin/login");
    }






    //
    public function __invoke($request, $response, $next){

        //
        $user_info = App::getUserSession($this->session_user_type);
        //dd($user_info);
        

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