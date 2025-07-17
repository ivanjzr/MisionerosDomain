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
use App\Users\UsersSucursalesPermisos;


use Helpers\Helper;
use Helpers\Query;


//
class AuthSessionAdmin
{


    //
    public $allow_to = null;
    public $session_user_type = null;
    public $login_url = null;
    public $select_sucursal_url = null;



    //
    public function __construct($allow_to = "")
    {
        $this->allow_to = $allow_to;
        $this->session_user_type = APP_TYPE_ADMIN;
        $this->login_url = strtolower("/admin/login");
        $this->select_sucursal_url = strtolower("/admin/user/select-sucursal");
    }


    
    private function getModelNameFromPath($path, $position = 1)
    {
        // Remover barras al inicio y final
        $path = trim($path, '/');
        
        // Dividir el path en segmentos
        $segments = explode('/', $path);
        
        // Buscar "admin" y retornar el segmento en la posición solicitada
        $adminIndex = array_search('admin', $segments);
        
        if ($adminIndex !== false && isset($segments[$adminIndex + $position])) {
            return $segments[$adminIndex + $position];
        }
        
        return '';
    }



    public static function unauthorizedUser($response){
        /* UNAUTHORIZED USER */
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'text/html')
            ->write("unauthorized user, <a href='/admin/home'>Return to Home</a>");
    }


    public static function redirectUser($response, $redir_url){
        return $response->withRedirect($redir_url);
    }

    
    

    //
    private static $allowed_valid_paths = ['home', 'dashboard'];

    //
    public function __invoke($request, $response, $next)
    {

        //
        $user_info = App::getUserSession($this->session_user_type);
        //echo "***test: "; dd($user_info);



        // Obtener el path completo de la URL
        $uri = $request->getUri();
        $path = $uri->getPath();
        //echo "$uri $path"; exit;
        
        
        // Extraer el primer segmento después de "admin"
        $main_model_name = $this->getModelNameFromPath($path);
        $second_model_name = $this->getModelNameFromPath($path, 2);
        //echo "$main_model_name $second_model_name"; exit;



        //
        //dd($_SESSION);
        //unset($_SESSION['admin']); exit;

        // si no tenemos user activo
        if ( !(isset($user_info['id']) && $user_info['id']) ){
            return self::redirectUser($response, $this->login_url);
        }
        //echo "test aqui"; exit;

        // si tenemos user activo entonces obtenemos datos de la sesion
        $request = $request->withAttribute('ses_data', $user_info);

        // y sacamos variables a utilizar
        //dd($user_info);
        $GLOBALS['admin_title'] = $user_info['app_name'];
        $account_id = $user_info['account_id'];
        $user_id = $user_info['id'];
        $is_admin = $user_info['is_admin'];

        
        // si no tenemos sucursal activa y seleccionada
        if ( !(isset($user_info['sucursal_id']) && $user_info['sucursal_id']) ){
            return self::redirectUser($response, $this->select_sucursal_url);
        }
        // validamos que ya tiene sucursal selecionada por lo anto la establecemos
        $sucursal_id = $user_info['sucursal_id'];

        
        // navegaciones directas antes de continuar
        if (in_array($main_model_name, self::$allowed_valid_paths)){            
            $response = $next($request, $response);
            return $response;
        }

        //echo "Ok before continue"; exit;
        
        


        

        /**
         * 
         * ----------------- Inicia proceso de validacion -----------------
         */


        // buscamos el main_model en el catalogo de secciones de la cuenta
        //echo "$account_id, $main_model_name"; exit;
        $main_section_found = self::helperGetAdminSectionByModelName($account_id, $main_model_name);
        //dd($main_section_found);
        // Si no existe la seccion regresamos el error
        if ( !(isset($main_section_found['id']) && $main_section_found['id']) ){
            return self::unauthorizedUser($response);
        }
        $main_section_model_name = $main_section_found['model_name'];
        $section_id = $main_section_found['id'];
        //echo "$section_id $main_section_model_name"; exit;
        



        // buscamos el second_model_name en el catalogo de secciones de la cuenta
        //echo $section_id . " " . $second_model_name; exit;
        $sub_section_found = self::helperGetAdminSubSectionByModelName($account_id, $section_id, $second_model_name);
        //dd($sub_section_found);
        $sub_section_id = ( isset($sub_section_found['id']) && $sub_section_found['id'] ) ? $sub_section_found['id'] : null;
        //echo "sub_section id $sub_section_id"; exit;


        
        //
        if ($sub_section_id){
            //echo "tiene subseccion con "; dd($sub_section_found);
            $current_for_admin = $sub_section_found;
        } else {
            //echo "tiene seccion con "; dd($main_section_found);
            $current_for_admin = $main_section_found;
        }
        




        //
        $pass_ok = false;
        //$is_admin = false; // debug admin false
        

        // si es super admin pasamos los permisos directamente sin gestionar y nos traemos la seccion tambien directamente        
        //dd($current_for_admin);
        //
        if( $is_admin ){

            //echo "es Super admin"; exit;

            $pass_ok = true;
            $current_for_admin['allowed_perm'] = "all";
            $user_info['current'] = $current_for_admin;

            // Si no es la seccion principal nos traemos los submenus del admin
            if (!$sub_section_id){
                $user_info['sub_menus'] = UsersSucursalesPermisos::GetAdminSubMenus($account_id, $section_id, $main_section_model_name);
                //dd($user_info['sub_menus']);
            }
            //echo "done!"; exit;

        } else {

            //echo "No es Super admin"; exit;

            $user_sucursales = UsersSucursales::GetSucursalPermisos($account_id, $user_id, $sucursal_id);
            //dd($user_sucursales);
            // Debe de tener la sucursal asignada para poder continuar
            if ( !(isset($user_sucursales['sucursal_id']) && $user_sucursales['sucursal_id']) ){
                return self::unauthorizedUser($response);
            }
            //
            $tipo_permisos = $user_sucursales['tipos_permisos'];
            //echo $tipo_permisos; exit;

            // Si no tiene permisos para la sucursal, mandamos a usuario no authorizado (aunque no se llegara aqui por que manda a seleccionar sucursal si no tiene )
            if (!$tipo_permisos){
                return self::unauthorizedUser($response);
            }
            
            //
            if( $tipo_permisos == TIPO_PERMISO_ID_TODOS){
                //echo "permisos TODOS"; exit;
                //
                $pass_ok = true;
                $current_for_admin['allowed_perm'] = "all";
                $user_info['current'] = $current_for_admin;

                // Si no es la seccion principal nos traemos los submenus del admin
                if (!$sub_section_id){
                    $user_info['sub_menus'] = UsersSucursalesPermisos::GetAdminSubMenus($account_id, $section_id, $main_section_model_name);
                }

            }
            //
            else if( $tipo_permisos == TIPO_PERMISO_ID_ESPECIFICOS){                
                //echo "traerse permisos especifos del user"; exit;
                
                // si es una subseccion
                if ($sub_section_id){
                    $user_section = self::getUserSection($account_id, $sucursal_id, $user_id, $sub_section_id, $section_id);
                }  else {
                    $user_section = self::getUserSection($account_id, $sucursal_id, $user_id, $section_id);
                }
                //dd($user_section);
                // si por algun motivo no tiene seccion asignada regresamos usuario no autorizado
                if ( !(isset($user_section['id']) && $user_section['id']) ){
                    return self::unauthorizedUser($response);
                }
                
                //
                if ($this->allow_to === "all" && $user_section['todos']){ $allowed_perm = "all"; $pass_ok = true; }
                if ($this->allow_to === "c" && $user_section['agregar']){ $allowed_perm = "c"; $pass_ok = true; }
                if ($this->allow_to === "r" && $user_section['leer']){ $allowed_perm = "r"; $pass_ok = true; }
                if ($this->allow_to === "u" && $user_section['editar']){ $allowed_perm = "u"; $pass_ok = true; }
                if ($this->allow_to === "d" && $user_section['eliminar']){ $allowed_perm = "d"; $pass_ok = true; }

                // Si no es la seccion principal nos traemos los submenus especificos del usuario 
                if (!$sub_section_id){
                    $user_info['sub_menus'] = UsersSucursalesPermisos::GetUserSubMenus($account_id, $sucursal_id, $user_id, $section_id, $main_section_model_name);
                }

                //
                $user_section['allowed_perm'] = $allowed_perm;
                $user_info['current'] = $user_section;
            }
        }



        //echo $pass_ok; dd($user_info);
        //
        if ($pass_ok){
            $request = $request->withAttribute('ses_data', $user_info);
            //
            $response = $next($request, $response);
            return $response;
        }

        // por default devuelve no autorizado
        return self::unauthorizedUser($response);
    }




    /**
     * Se trae las secciones y de una vez se las trae como admin para 1 - buscarlas y traerlas y 2 - para usarlas si es admin
     * Nota: no usar "And t.parent_id Is Null" por que hay menus donde su parent es un menu desplegale (dropdown)
     */
    public static function helperGetAdminSectionByModelName($account_id, $model_name){        
        return Query::Single("select * from v_admin_permisos t Where t.account_id = ? And t.model_name = ?", [$account_id, $model_name]);
    }

    /**
     * 
     * Igual que la funcion anterior pero con parent_id
     */
    public static function helperGetAdminSubSectionByModelName($account_id, $parent_id, $model_name){
        return Query::Single("select * from v_admin_permisos t Where t.account_id = ? And t.parent_id = ? and t.model_name = ?", [$account_id, $parent_id, $model_name]);
    }
 

    //    
    public static function getUserSection($account_id, $sucursal_id, $user_id, $section_id, $parent_id = null) {
        if ($parent_id !== null) {
            // Es subsección - usar parent_id como filtro adicional
            $user_section = Query::Single(
                "SELECT * FROM v_user_sucursales_permisos t 
                WHERE t.account_id = ? AND t.sucursal_id = ? AND t.user_id = ? AND t.id = ? AND t.parent_id = ?", 
                [$account_id, $sucursal_id, $user_id, $section_id, $parent_id]
            );
        } else {
            // Es sección principal
            $user_section = Query::Single(
                "SELECT * FROM v_user_sucursales_permisos t 
                WHERE t.account_id = ? AND t.sucursal_id = ? AND t.user_id = ? AND t.id = ?", 
                [$account_id, $sucursal_id, $user_id, $section_id]
            );
        }
        
        return $user_section;
    }


}