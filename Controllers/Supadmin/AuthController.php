<?php
namespace Controllers\Supadmin;
//
use App\Administrators\Administrators;
use Controllers\BaseController;

//
use App\App;
//
use Helpers\ValidatorHelper;
use Helpers\EncryptHelper;



//
class AuthController extends BaseController
{



    //
    public function ViewLogin($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/login.phtml', [
            "App" => new App(null, null)
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
        $results = Administrators::Create([
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


        //
        if ( !$v->validateEmail([6, 256], $username) ){
            $results['error'] = "proporciona el correo"; return $response->withJson($results, 200);
        }

        //
        if ( !$password ){
            $results['error'] = "proporciona tu clave"; return $response->withJson($results, 200);
        }





        //
        $user_results = Administrators::GetValidAdmin($username, $password);
        //var_dump($user_results); exit;


        //
        if ( $user_results && $user_results['id']){

            //
            $user_data = array(
                "id" => $user_results['id'],
                "nombre" => $user_results['nombre']
            );
            //print_r($user_data); exit;
            App::setUserSession(APP_TYPE_SUPADMIN, $user_data);
            return $response->withJson($user_data, 200);
        }


        //
        $results['error'] = "Error, no se encontro el usuario";
        return $response->withJson($results, 200);

    }







    /*
     *
     * Logout de Administradores
     *
     * */
    public function Logout($request, $response, $args) {


        //
        if (App::getAdminSession(APP_TYPE_SUPADMIN)){
            unset($_SESSION[APP_TYPE_SUPADMIN]);
        }

        //
        $results = array();
        return $response->withJson($results, 200);

    }







}
