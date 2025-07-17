<?php
namespace Controllers\Admin;
//
use App\Apps\Apps;
use App\Users\Users;
use App\Users\UsersSucursalesPermisos;
use App\Paths;
use App\Sucursales\Sucursales;
use Controllers\BaseController;
use Helpers\Query;


//
use App\App;
//
use Helpers\Helper;
use Helpers\ValidatorHelper;
use Helpers\EncryptHelper;



//
class AuthController extends BaseController
{



    //
    public function ViewLogin($request, $response, $args) {
        //
        $app = $request->getAttribute("app");
        //
        return $this->container->php_view->render($response, 'admin/login.phtml', [
            "App" => new App(null, null),
            "app" => $app
        ]);
    }



    /*
     *
     * Registro de Administradores
     *
     * */
    public function Register($request, $response, $args) {

        //
        $v = new ValidatorHelper();

        //
        $nombre = $v->safeVar($request->getParsedBody(), 'nombre');
        $email = $v->safeVar($request->getParsedBody(), 'email');
        $password = $v->safeVar($request->getParsedBody(), 'password');


        //
        if ( !$v->validateString([2, 256], $nombre) ){
            $results['error'] = "proporciona el nombre con una longitud maxima de 256 caracteres"; return $response->withJson($results, 200);
        }

        //
        if ( !$v->validateEmail([6, 256], $email) ){
            $results['error'] = "proporciona el correo con una longitud maxima de 256 caracteres"; return $response->withJson($results, 200);
        }

        //
        if ( !$v->validatePasswordLevel2([6, 12], $password) ){
            $results['error'] = "proporciona una clave que contenga letras y numeros con una longitud de 6 a 12 caracteres"; return $response->withJson($results, 200);
        }



        /*
         * Create new admin
         * */
        $results = Users::Create([
            "nombre" => $nombre,
            "email" => $email,
            "password" => $password
        ]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);

    }











    /*
     *
     * Login de Administradores
     *
     * */
    public function Login($request, $response, $args) {

        //
        $v = new ValidatorHelper();

        //
        $username = $v->safeVar($request->getParsedBody(), 'username');
        $password = $v->safeVar($request->getParsedBody(), 'password');
        //echo " $username $password "; exit;


        //
        if ( !$v->validateEmail([6, 256], $username) ){
            $results['error'] = "proporciona el correo"; return $response->withJson($results, 200);
        }

        //
        if ( !$password ){
            $results['error'] = "proporciona tu clave"; return $response->withJson($results, 200);
        }



        //
        $app_info = Apps::GetApp();
        //dd($app_info);


        // 
        if ($app_info && isset($app_info['account_id']) && $app_info['account_id']){
            
            //
            $account_id = $app_info['account_id'];
            $user_results = Users::GetValidUserAccount($account_id, $username, $password);
            //dd($user_results);

            /**
             * 
             * Si existe el user continuamos
             */
            if ($user_results && isset($user_results['id']) && $user_results['id']){

                //
                $sucursal_id = ($user_results['selected_sucursal_id']) ? $user_results['selected_sucursal_id'] : null;
                //echo $sucursal_id; exit;
                
                /**
                 * 
                 * Si tiene el login_to_pos para redireccionar al punto de venta 
                 * y ademas tiene los permisos para ir a esa seccio, continuamos
                 */
                $login_to_pos = 0;
                if ( $user_results['login_to_pos'] && UsersSucursalesPermisos::checkUserPerm($account_id, $sucursal_id, $user_results, "pos") ){
                    //echo "si tiene permissions para pos"; exit;
                    $login_to_pos = 1;
                }
                //echo "done $login_to_pos"; exit;

                //
                if ( $user_results && $user_results['id']){
                    //
                    $user_data = array(
                        "id" => $user_results['id'],
                        //
                        "account_id" => $account_id,
                        "app_id" => $app_info['id'],
                        "host" => $app_info['current_host'],
                        //
                        "login_to_pos" => $login_to_pos,
                        "nombre" => $user_results['name'],
                        "t" => "b" // usuario tipo backend
                    );
                    //dd($user_data); exit;
                    App::setUserSession(APP_TYPE_ADMIN, $user_data);
                    return $response->withJson($user_data, 200);
                }
            }

            //
            $results['error'] = "Error, no se encontro el usuario";
            return $response->withJson($results, 200);
        }

        //
        $results['error'] = "Domain or invalid account";
        return $response->withJson($results, 200);
    }










    /*
     *
     * Logout de Administradores
     *
     * */
    public function Logout($request, $response, $args) {
        //
        if ($usr_ses = App::getUserSession(APP_TYPE_ADMIN)){
            //dd($usr_ses); exit;
            /*
            //
            $account_id = $usr_ses['account_id'];
            $user_id = $usr_ses['id'];
            //
            $update_results = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                    Update 
                        empleados
                    Set 
                        selected_sucursal_id = null
                        
                    Where account_id = ?
                    And id = ?
                    ;SELECT @@ROWCOUNT
                    ",
                "params" => [
                    $account_id, $user_id
                ],
                "parse" => function($updated_rows, &$query_results) use($user_id){
                    $query_results['affected_rows'] = $updated_rows;
                    $query_results['id'] = (int)$user_id;
                }
            ]);
            */
            //dd($update_results); exit;
            unset($_SESSION[APP_TYPE_ADMIN]);
        }

        //
        $results = array();
        return $response->withJson($results, 200);

    }







}
