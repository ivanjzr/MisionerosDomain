<?php
namespace Controllers\Users;

//
use App\Users\Users;
use App\Users\UsersSucursales;
use App\Users\UsersSucursalesPermisos;

use Controllers\BaseController;
//
use Helpers\Helper;



//
class UsersSucursalesPermisosController extends BaseController
{









    //
    public function GetAll($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];



        //
        $user_id = $args['user_id'];
        $sucursal_id = $args['sucursal_id'];


        //
        $results = UsersSucursalesPermisos::GetPermisos($account_id, $user_id, $sucursal_id);
        //dd($results);

        //
        return $response->withJson($results, 200);
    }






    //
    public function upsertPermisos($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];



        //
        $results = array();



        //
        $user_id = $args['user_id'];
        $sucursal_id = $args['sucursal_id'];


        //
        $secciones_ids = Helper::safeVar($request->getParsedBody(), 'checkedIds');
        $tipos_permisos = Helper::safeVar($request->getParsedBody(), 'tipos_permisos');
        $tipos_permisos = is_numeric($tipos_permisos) ? (int)$tipos_permisos : null;
        //echo $tipos_permisos; dd($secciones_ids);


        //
        if ( !($tipos_permisos === 0 || $tipos_permisos === 1 || $tipos_permisos === 2) ){
            $results['error'] = "Proporciona un tipo de permiso valido";
            return $response->withJson($results, 200);
        }

        if ( $tipos_permisos === 2 ){
            //
            if ( !(is_array($secciones_ids) && count($secciones_ids) > 0) ){
                return $response->withJson(array(
                    "error" => "Escoge los permisos del usuario o selecciona otra opcion"
                ), 200);
            }
        }



        /*
         * SIEMPRE ACTUALIZA LOS PRIVILEGIOS INDEPENDIENTEMENTE DE LOS PERMISOS
         * */
        $results = UsersSucursales::UpsertUserSucursal($account_id, $user_id, $sucursal_id, $tipos_permisos);
        //dd($results);

        // SI EL TIPO DE PERMISO ES 2 (ESCOGER PERMISOS) ENTONCES ACTUALIZAMOS LOS PERMISOS 
        if ( $tipos_permisos === 2 ){
            //
            $results['upsert_permisos'] = UsersSucursalesPermisos::upsertPermisos($account_id, $user_id, $sucursal_id, $secciones_ids);
            //dd($results);
        }


        //
        return $response->withJson($results, 200);
    }





}
