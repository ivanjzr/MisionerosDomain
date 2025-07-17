<?php
namespace Middleware;


use App\Users\UsersSucursales;

/**
 *
 * CODIGOS DE ESTADO:
 * https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
 *
 */





//
class AuthApiUserSucursalCheckPrev
{





    //
    public function __construct()
    {
        //
    }




    //
    public function __invoke($request, $response, $next)
    {

        //
        $user_session_data = $request->getAttribute("user_session_data");
        //var_dump($user_session_data); exit;


        //
        $app_id = $user_session_data['app_id'];
        $user_id = $user_session_data['id'];
        $is_admin = $user_session_data['is_admin'];


        /*
         * SI ES ADMIN PASAMOS DIRECTO SIN VERIFICACION DE SUCURSAL
         * */
        if ($is_admin){
            //
            $response = $next($request, $response);
            return $response;
        }

        /*
         * SI NO ES ADMIN TENEMOS QUE VERIFICAR LA SUCURSAL
         * */
        else {
            //
            $check_sucursal_id = $request->getAttribute('routeInfo')[2]["id"];
            //echo " $app_id $user_id $check_sucursal_id "; exit;
            //
            $allowed_sucursal_results = UsersSucursales::CheckApiUserAllowed($app_id, $user_id, $check_sucursal_id);
            //var_dump($allowed_sucursal_results); exit;
            //
            if ( isset($allowed_sucursal_results['allowed']) && $allowed_sucursal_results['allowed'] == 1 ){
                //
                $response = $next($request, $response);
                return $response;
            }
        }



        //
        $err_msg = (isset($allowed_sucursal_results['error']) && $allowed_sucursal_results['error']) ? $allowed_sucursal_results['error'] : "Sucursal not found";
        //
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'text/html')
            ->write($err_msg);
    }
}