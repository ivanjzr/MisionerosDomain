<?php
namespace Controllers\Admin;

//
use Controllers\BaseController;
//
use App\App;
use Helpers\Helper;
//
use App\Sucursales\Sucursales;
//
use App\Users\Users;
use App\Users\UsersSucursales;
use App\Users\UsersSucursalesPermisos;
//
use Helpers\ValidatorHelper;




//
class UserController extends BaseController
{




    //
    public function ViewSelectSucursal($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/select-sucursal.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }





    //
    public function GetUserSucursales($request, $response, $args) {



        /*
        * GET SESSION DATA
        * */
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];




        // TRAEMOS TODAS LAS SUCURSALES CUANDO ES ADMIN
        if ( isset($ses_data["is_admin"]) && $ses_data["is_admin"] ){
            //
            $results = Sucursales::GetAll($account_id);
        }
        //
        else {
            //
            $results = UsersSucursales::GetAll($account_id, $ses_data['id']);
        }

        //dd($results);
        return $response->withJson($results, 200);
    }












    /*
     *
     * Login de Administradores
     *
     * */
    public function PostSelectSucursal ($request, $response, $args) {

        //
        $v = new ValidatorHelper();


        /*
        * GET SESSION DATA
        * */
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $is_admin = $ses_data['is_admin'];



        //
        $sucursal_id = $v->safeVar($request->getParsedBody(), 'sucursal_id');
        //echo $sucursal_id; exit;
        //
        if ( !is_numeric($sucursal_id) ){
            $results['error'] = "proporciona tu clave";
            return $response->withJson($results, 200);
        }


        /*
         * SI ES ADMIN NO HAY NECESIDAD DE CHECAR SI ES VALIDA PARA EL USER
         * ASI QUE OBTENEMOS LA INFO DIRECTAMENTE Y ACTUALIZAMOS LA SELECTED SUCURSAL (AUNQUE SEA ADMIN)
         * */
        if ($is_admin){
            //echo "is admin"; exit;
            //
            $update_results = Users::UpdateSelectedSucursal($sucursal_id, $account_id, $user_id);
            //dd($update_results);
            return $response->withJson($update_results, 200);

        }

        /*
         * SI NO ES ADMIN CHECAMOS SI ES VALIDA PARA EL USER
         * Y ACTUALIZAMOS LA INFO
         * */
        else {
            //echo "NO es admin"; exit;
            //
            $check_user_info = UsersSucursales::IsValidForUser($account_id, $user_id, $sucursal_id);
            //dd($check_user_info);
            //
            if ( $check_user_info && isset($check_user_info['id']) ){
                $update_results = Users::UpdateSelectedSucursal($sucursal_id, $account_id, $user_id);
                //var_dump($update_results); exit;
                return $response->withJson($update_results, 200);
            }
        }


        $results['error'] = "Sucursal no asignada o inexistente";
        return $response->withJson($results, 200);
    }






}