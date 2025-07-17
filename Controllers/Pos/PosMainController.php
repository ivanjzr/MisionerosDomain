<?php
namespace Controllers\Pos;

use App\App;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;
use App\Users\Users;
use App\Users\UsersSucursalesPermisos;
use Controllers\Pos\PosHelper;


class PosMainController extends BaseController
{


    //
    public static $redir_pos_url = "/admin/pos/index";

    // 600 secs = 10 mins
    public static $expiry_seconds = 600;
    



    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/pos/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }

    //
    public static function getExpiresAtDateTime($addSeconds = 0){
        $dateTime = new \DateTime();
        if ($addSeconds > 0) {
            $dateTime->add(new \DateInterval("PT{$addSeconds}S"));
        }
        return $dateTime->format("Y-m-d H:i:s");
    }


    //
    public function ViewMain($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        

        /**
         * 
         * Verificamos que tenga mandatoriamente el id de la caja en sesion
         */
        if (isset($ses_data['ses']) && isset($ses_data['ses'][SES_POS_REGISTER_ID]) && $ses_data['ses'][SES_POS_REGISTER_ID] ){


            //
            $pos_register_id = $ses_data['ses'][SES_POS_REGISTER_ID];
            //echo $pos_register_id; exit;
            //self::$redir_pos_url

            //
            $caja_res = Query::Single("Select * From v_pos_registers where app_id = ? And id = ?", [$app_id, $pos_register_id]);
            //dd($caja_res);
            if ( !isset($caja_res['id']) ){
                //echo "Error, no se encontro la caja"; exit;
                return $response->withRedirect(self::$redir_pos_url);        
            }
            // si la caja esta ya cerrada
            if ($caja_res['closed_user_id']){
                //echo "Error, caja se encuentra en status de cerrada, no se pueden realizar ventas"; exit;
                return $response->withRedirect(self::$redir_pos_url);        
            }
            //echo "caja abierta Ok continue validar user si tiene sesion"; exit;
            $ses_data['caja_info'] = $caja_res;

            


            /**
             * 
             * Validamos si tiene user activo y si es el caso validarlo
             */
            if (isset($ses_data['ses'][SES_POS_USER_ID]) && $ses_data['ses'][SES_POS_USER_ID]){
                //
                $pos_user_id = $ses_data['ses'][SES_POS_USER_ID];
                //echo $pos_user_id; exit;
                
                //
                $user_res = Query::Single("Select * From v_pos_users where account_id = ? And id = ?", [$account_id, $pos_user_id]);
                //
                //dd($user_res);
                if ( !isset($user_res['id']) ){
                    //echo "Error, no se encontro el usuario o pin invalido"; exit;
                    return $response->withRedirect(self::$redir_pos_url);        
                }
                //
                if ( !UsersSucursalesPermisos::checkUserPerm($account_id, $sucursal_id, $user_res, "pos") ){
                    //echo "Error, permisos insuficientes"; exit;
                    return $response->withRedirect(self::$redir_pos_url);
                }
                //echo "user passed"; exit;
                //
                $ses_data['current_user'] = $user_res;



                /**
                 * 
                 * Validamos si ya expiro la sesion para volver a pedir el PIN
                 * No saca drasticamente y si permite continuar vendiendo, 
                 * pero cuando el user recarga la ventana es cuando se solicita el pin
                 */
                //
                $need_pin = 0;
                //
                if (isset($ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT]) && $ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT]){
                    
                    //
                    $ses_pos_login_dt_expire_at = $ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT];
                    //echo $pos_login_dt_expire_at; exit;
                    $pos_login_dt_expire_at = \DateTime::createFromFormat("Y-m-d H:i:s", $ses_pos_login_dt_expire_at);
                    //dd($pos_login_dt_expire_at);

                    $current_datetime = new \DateTime();
                    //dd($current_datetime);

                    // si actual es mayor a la limit (osea que ya sobre paso)
                    // 11:00 > 10:30
                    if ( $current_datetime > $pos_login_dt_expire_at ){
                        //echo "need pin"; exit;
                        $need_pin = 1;
                    }
                }
                //
                $ses_data['need_pin'] = $need_pin;

            }
            

            //
            return $this->container->php_view->render($response, 'admin/pos/main.phtml', [
                "App" => new App(null, $ses_data)
            ]);
        }
        //
        return $response->withRedirect(self::$redir_pos_url);        
    }



    






    //
    public function PostOpenRegister($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];

        

        //
        $results = array();



        //
        $pos_user_id = Helper::safeVar($request->getParsedBody(), 'user_id');
        $pos_id = Helper::safeVar($request->getParsedBody(), 'pos_id');
        $opening_balance = Helper::safeVar($request->getParsedBody(), 'opening_balance');
        $opening_balance_usd = Helper::safeVar($request->getParsedBody(), 'opening_balance_usd');
        $pos_notes = Helper::safeVar($request->getParsedBody(), 'pos_notes');
        //echo " $pos_user_id, $pos_id, $opening_balance, $opening_balance_usd, $pos_notes "; exit;

        

        //
        if (!is_numeric($opening_balance)){
            $results['error'] = "Se requiere el monto de apertura en pesos";
            return $response->withJson($results, 200);
        }
        if (!is_numeric($opening_balance_usd)){
            $results['error'] = "Se requiere el monto de apertura en dolares";
            return $response->withJson($results, 200);
        }



        //
        if ( !(isset($ses_data['ses'][SES_POS_USER_ID]) && $ses_data['ses'][SES_POS_USER_ID]) ){
            $results['error'] = "Se requiere sesion de usuario para apertura de caja";
            return $response->withJson($results, 200);
        }
        //echo "passed session Ok!"; exit;
        //
        $ses_pos_user_id = $ses_data['ses'][SES_POS_USER_ID];
        //echo $ses_pos_user_id; exit;

        //
        if ( $ses_pos_user_id !== $pos_user_id ){
            $results['error'] = "Usuario seleccionado no es el mismo de la sesion, ingrese Pin nuevamente";
            return $response->withJson($results, 200);
        }
        //echo "passed Ok!"; exit;




        //
        $user_res = Query::Single("Select * From v_pos_users where account_id = ? And id = ?", [$account_id, $pos_user_id]);
        //dd($user_res);
        if ( !isset($user_res['id']) ){
            $results['error'] = "no se encontro el usuario o pin invalido";
            return $response->withJson($results, 200);
        }
        //
        if ( !UsersSucursalesPermisos::checkUserPerm($account_id, $sucursal_id, $user_res, "pos") ){
            $results['error'] = "permisos insuficientes";
            return $response->withJson($results, 200);
        }
        //echo "passed"; exit;


        /**
         * 
         * Validamos status de la caja 
         */
        $caja_res = Query::Single("Select * From v_pos where app_id = ? And id = ?", [$app_id, $pos_id]);
        //dd($caja_res);
        if ( !isset($caja_res['id']) ){
            $results['error'] = "no se encontro el punto de venta";
            return $response->withJson($results, 200);
        }
        if ( !($caja_res['active']) ){
            $results['error'] = "no se pudo abrir caja, punto de venta inactivo";
            return $response->withJson($results, 200);
        }
        //echo "test"; exit;

        //
        $continue_open = false;

        /**
         * 
         * Si tiene caja verificamos el estado de esta
         */
        if ($caja_res['last_register_id']){

            // si esta cerrada continuamos
            if ($caja_res['last_closed_user_id']){
                //echo "continuar Ok"; exit;
                //
                $continue_open = true;
            }
            // si no esta cerrada mostramos el mensaje
            else {
                //
                $results['error'] = "caja ya ha sido abierta, no se puede abrir nuevamente";
                return $response->withJson($results, 200);
            }
        } else {
            $continue_open = true;
        }
        //echo $continue_open; exit;


        //
        if ($continue_open){
            //echo "continuar con apertura de caja"; exit;

            //
            $insert_register = Query::DoTask([
                "task" => "add",
                "debug" => true,
                "stmt" => "
                    Insert Into pos_registers
                    ( app_id, sucursal_id, pos_id, notes, 
                     opening_balance, opening_balance_usd, opened_user_id, opened_datetime )
                    Values
                    ( ?, ?, ?, ?,
                      ?, ?, ?, GETDATE() )
                    ;SELECT SCOPE_IDENTITY()
                    ",
                "params" => [
                    $app_id, $sucursal_id, $pos_id, $pos_notes,
                    $opening_balance, $opening_balance_usd, $pos_user_id
                ],
                "parse" => function($insert_id, &$query_results){
                    $query_results['id'] = (int)$insert_id;
                }
            ]);
            //dd($insert_register);
            if (isset($insert_register['id'])){


                //
                $pos_register_id = $insert_register['id'];

                //
                $insert_register_user = Query::DoTask([
                    "task" => "add",
                    "debug" => true,
                    "stmt" => "
                        Insert Into pos_registers_users
                        ( app_id, sucursal_id, pos_id, pos_register_id,
                          current_user_id, login_datetime )
                        Values
                        ( ?, ?, ?, ?,
                          ?, GETDATE() )
                        ;SELECT SCOPE_IDENTITY()
                        ",
                    "params" => [
                        $app_id, $sucursal_id, $pos_id, $pos_register_id, 
                        $pos_user_id
                    ],
                    "parse" => function($insert_id, &$query_results){
                        $query_results['id'] = (int)$insert_id;
                    }
                ]);
                //dd($insert_register_user);
                if (isset($insert_register_user['id'])){
                    //echo "register user ok"; exit;

                    
                    // crea sesiones
                    //$ses_data = App::getUserSession(APP_TYPE_ADMIN);
                    if (!isset($ses_data['ses'])){
                        $ses_data['ses'] = [];
                    }
                    //
                    $ses_data['ses'][SES_POS_USER_ID] = $pos_user_id;
                    $ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT] = self::getExpiresAtDateTime(self::$expiry_seconds);
                    $ses_data['ses'][SES_POS_REGISTER_ID] = $pos_register_id;
                    //
                    App::setUserSession(APP_TYPE_ADMIN, $ses_data);

                    //
                    return $response->withJson($insert_register, 200);
                }


            }
        }

        

        


        $results['error'] = "no se pudo abrir la caja, verifica con el administrador";
        return $response->withJson($results, 200);
    }








    

    
    


    //
    public function PostValidateUser($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];

        

        //
        $results = array();



        //
        $pos_pin = Helper::safeVar($request->getParsedBody(), 'user_pin');
        $pos_user_id = Helper::safeVar($request->getParsedBody(), 'user_id');
        //echo "$user_pin $pos_user_id"; exit;


        //
        $user_res = Query::Single("Select * From v_pos_users where account_id = ? And id = ? And pos_pin = ?", [$account_id, $pos_user_id, $pos_pin]);
        //dd($user_res);
        if ( !isset($user_res['id']) ){
            $results['error'] = "no se encontro el usuario o pin invalido";
            return $response->withJson($results, 200);
        }


        //
        if ( UsersSucursalesPermisos::checkUserPerm($account_id, $sucursal_id, $user_res, "pos") ){
            //echo "permissions passed Ok!"; exit;
            
            //
            if (!isset($ses_data['ses'])){
                $ses_data['ses'] = [];
            }
            //
            $ses_data['ses'][SES_POS_USER_ID] = $pos_user_id;
            $ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT] = self::getExpiresAtDateTime(self::$expiry_seconds);
            //
            App::setUserSession(APP_TYPE_ADMIN, $ses_data);
            //
            return $response->withJson([
                "id" => $ses_data['id'],
                "ses" => $ses_data['ses']
            ], 200);

        }
        
        

        //
        $results['error'] = "no se encontro el usuario o permisos insuficientes";
        return $response->withJson($results, 200);
    }











    


    /**
     * 
     * 
     * MUY SIMILAR A LA DE OPEN-REGISTER
     * Solo que aqui no se inserta la caja solo se inserta el usuario al validarlo
     * y solo se establece la sesion id
     * 
     */
    public function PostUpdatePosRegisterUser($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];

        

        //
        $results = array();



        //
        $pos_user_id = Helper::safeVar($request->getParsedBody(), 'user_id');
        $pos_pin = Helper::safeVar($request->getParsedBody(), 'user_pin');
        $pos_register_id = Helper::safeVar($request->getParsedBody(), 'pos_register_id');
        //echo "$pos_user_id, $pos_register_id"; exit;



        //
        $user_res = Query::Single("Select * From v_pos_users where account_id = ? And id = ? And pos_pin = ?", [$account_id, $pos_user_id, $pos_pin]);
        //dd($user_res);
        if ( !isset($user_res['id']) ){
            $results['error'] = "no se encontro el usuario";
            return $response->withJson($results, 200);
        }
        //
        if ( !UsersSucursalesPermisos::checkUserPerm($account_id, $sucursal_id, $user_res, "pos") ){
            $results['error'] = "permisos insuficientes";
            return $response->withJson($results, 200);
        }
        






        /**
         * 
         * Validamos status de la caja
         */
        $caja_res = Query::Single("Select * From v_pos_registers where app_id = ? And id = ?", [$app_id, $pos_register_id]);
        //dd($caja_res);
        if ( !isset($caja_res['id']) ){
            $results['error'] = "no se encontro la caja";
            return $response->withJson($results, 200);
        }

        // si la caja esta ya cerrada
        if ($caja_res['closed_user_id']){
            $results['error'] = "caja se encuentra en status de cerrada, no se pueden realizar ventas";
            return $response->withJson($results, 200);
        }

        // get caja data
        $pos_id = $caja_res['pos_id'];


        //
        $insert_register_user = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                Insert Into pos_registers_users
                ( app_id, sucursal_id, pos_id, pos_register_id,
                    current_user_id, login_datetime )
                Values
                ( ?, ?, ?, ?,
                    ?, GETDATE() )
                ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id, $sucursal_id, $pos_id, $pos_register_id, 
                $pos_user_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);
        //dd($insert_register_user);
        if (isset($insert_register_user['id'])){
            //echo "register user ok"; exit;

            
            // crea sesiones
            //$ses_data = App::getUserSession(APP_TYPE_ADMIN);
            if (!isset($ses_data['ses'])){
                $ses_data['ses'] = [];
            }

            
                

            //
            $ses_data['ses'][SES_POS_USER_ID] = $pos_user_id;
            $ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT] = self::getExpiresAtDateTime(self::$expiry_seconds);
            $ses_data['ses'][SES_POS_REGISTER_ID] = $pos_register_id;
            //
            App::setUserSession(APP_TYPE_ADMIN, $ses_data);

            //
            return $response->withJson($caja_res, 200);
        }


        

        


        $results['error'] = "no se pudo abrir la caja, verifica con el administrador";
        return $response->withJson($results, 200);
    }










    //
    public function PostContinueSale($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];

        

        //
        $results = array();



        //
        $pos_id = Helper::safeVar($request->getParsedBody(), 'pos_id');
        //echo "$pos_id"; exit;

        
        /**
         * 
         * Validamos status de la caja
         */
        $caja_res = Query::Single("Select * From v_pos where app_id = ? And id = ?", [$app_id, $pos_id]);
        //dd($caja_res);
        if ( !isset($caja_res['id']) ){
            $results['error'] = "no se encontro el punto de venta";
            return $response->withJson($results, 200);
        }
        if ( !($caja_res['active']) ){
            $results['error'] = "no se pudo abrir caja, punto de venta inactivo";
            return $response->withJson($results, 200);
        }


        //
        $continue_register = false;
        $pos_register_id = null;

        /**
         * 
         * Si tiene caja verificamos el estado de esta
         */
        if ($caja_res['last_register_id']){

            //
            $pos_register_id = $caja_res['last_register_id'];

            // si esta cerrada no se puede continuar
            if ($caja_res['last_closed_user_id']){
                //
                $results['error'] = "no se puede continuar, caja ya ha sido cerrada";
                return $response->withJson($results, 200);                
            } 
            // si no esta cerrada continuamos
            else {
                //echo "continuar Ok"; exit;
                $continue_register = true;
            }
            
        } else {
            //
            $results['error'] = "se requiere apertura de caja para continuar";
            return $response->withJson($results, 200);
        }
        //echo $continue_register; exit;


        //
        if ($continue_register){
            //echo "continuar con apertura de caja"; exit;

            // crea sesiones
            //$ses_data = App::getUserSession(APP_TYPE_ADMIN);
            if (!isset($ses_data['ses'])){
                $ses_data['ses'] = [];
            }

            //
            //dd($caja_res);
            
            /**
             * 
             * Para continuar con venta solo seteamos el pos_register_id
             * y quitamos los de usuario para obligar a que inicie sesion
             */
            $ses_data['ses'][SES_POS_REGISTER_ID] = $pos_register_id;

            // quitar sesion de user
            unset($ses_data['ses'][SES_POS_USER_ID]);
            unset($ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT]);
            
            //
            App::setUserSession(APP_TYPE_ADMIN, $ses_data);

            // debug new sesion data
            //$ses_data = App::getUserSession(APP_TYPE_ADMIN);dd($ses_data);

            return $response->withJson([
                "id" => $caja_res['id'],
                "pos_register_id" => $pos_register_id
            ], 200);
        }
        


        $results['error'] = "no se pudo abrir la caja, verifica con el administrador";
        return $response->withJson($results, 200);
    }





    



    //
    public function PostCloseRegister($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];

        //
        $results = array();

        //
        $pos_user_id = Helper::safeVar($request->getParsedBody(), 'pos_user_id');
        $pos_register_id = Helper::safeVar($request->getParsedBody(), 'pos_register_id');
        $closed_balance_mxn = Helper::safeVar($request->getParsedBody(), 'closed_balance');
        $closed_balance_usd = Helper::safeVar($request->getParsedBody(), 'closed_balance_usd');
        $closing_notes = Helper::safeVar($request->getParsedBody(), 'closing_notes');

        // Validaciones
        if (!is_numeric($closed_balance_mxn) || $closed_balance_mxn < 0) {
            $results['error'] = "Balance final MXN inválido";
            return $response->withJson($results, 200);
        }
        
        if (!is_numeric($closed_balance_usd) || $closed_balance_usd < 0) {
            $results['error'] = "Balance final USD inválido"; 
            return $response->withJson($results, 200);
        }

        // Convertir valores vacíos a 0
        $closed_balance_usd = $closed_balance_usd ?: 0;

        // Validar que la caja existe y está abierta
        $caja_res = Query::Single("Select * From v_pos_register_report where app_id = ? And id = ?", [$app_id, $pos_register_id]);
        //dd($caja_res);
        //
        if (!isset($caja_res['id'])) {
            $results['error'] = "No se encontró la caja";
            return $response->withJson($results, 200);
        }
        
        if ($caja_res['closed_user_id']) {
            $results['error'] = "La caja ya está cerrada";
            return $response->withJson($results, 200);
        }

        // Obtener balances esperados para incluir en respuesta
        $expected_mxn = floatval($caja_res['efectivo_final_esperado_mxn'] || 0);
        $expected_usd = floatval($caja_res['efectivo_final_esperado_usd'] || 0);
        $difference_mxn = floatval($closed_balance_mxn) - $expected_mxn;
        $difference_usd = floatval($closed_balance_usd) - $expected_usd;

        $params = [
            $pos_user_id, 
            floatval($closed_balance_mxn), 
            floatval($closed_balance_usd),
            $closing_notes,
            $app_id, 
            $sucursal_id, 
            $pos_register_id
        ];

        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                Update 
                    pos_registers
                Set 
                    closed_user_id = ?,
                    closed_balance = ?,
                    closed_balance_usd = ?,
                    closing_notes = ?,
                    closed_datetime = GETDATE()

                Where app_id = ?
                And sucursal_id = ?
                And id = ?
                ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($pos_register_id, $expected_mxn, $expected_usd, $closed_balance_mxn, $closed_balance_usd, $difference_mxn, $difference_usd){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$pos_register_id;
                
                // Información adicional del cierre
                $query_results['expected_mxn'] = $expected_mxn;
                $query_results['expected_usd'] = $expected_usd;
                $query_results['actual_mxn'] = floatval($closed_balance_mxn);
                $query_results['actual_usd'] = floatval($closed_balance_usd);
                $query_results['difference_mxn'] = $difference_mxn;
                $query_results['difference_usd'] = $difference_usd;
                $query_results['has_differences'] = (abs($difference_mxn) > 0.01 || abs($difference_usd) > 0.01);
            }
        ]);

        // Limpiar sesiones POS después del cierre exitoso
        if (isset($update_results['affected_rows']) && $update_results['affected_rows'] > 0) {
            if (isset($ses_data['ses'])) {
                unset($ses_data['ses'][SES_POS_USER_ID]);
                unset($ses_data['ses'][SES_POS_LOGIN_DT_EXPIRE_AT]);
                unset($ses_data['ses'][SES_POS_REGISTER_ID]);
                App::setUserSession(APP_TYPE_ADMIN, $ses_data);
            }
        }
        //
        return $response->withJson($update_results, 200);
    }







    
    //
    public function GetPosListAvailableItems($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $user_id = $ses_data['id'];

    

        // 
        $results = Query::Multiple("Select * from v_pos Where app_id = ? And sucursal_id = ? And active = 1", [$app_id, $sucursal_id]);
        //dd($results); exit;
        

        //
        return $response->withJson($results, 200);
    }


    //
    public function GetPosListAllItems($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $user_id = $ses_data['id'];

    

        //
        $results = Query::Multiple("Select * from v_pos Where app_id = ? And sucursal_id = ? Order By id Asc", [$app_id, $sucursal_id]);
        //dd($results); exit;
        

        //
        return $response->withJson($results, 200);
    }







    //
    public function GetPosConfig($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $user_id = $ses_data['id'];


        //
        $results = [];
        //
        $tipo_cambio = Query::Single("Select Top 1 * from v_tipos_cambio Where account_id = ? And sys_pais_id = ? And tipo_cambio > 0 Order by id Desc", [$account_id, ID_PAIS_EU]);
        //dd($tipo_cambio);
        if ( isset($tipo_cambio['error']) && $tipo_cambio['error'] ){
            $results['error'] = $tipo_cambio['error'];
            return $response->withJson($results, 200);            
        }
        if ( !(isset($tipo_cambio['id']) && $tipo_cambio['id']) ){
            $results['error'] = "No se encontro ningun tipo de cambio";
            return $response->withJson($results, 200);            
        }

        //
        $results['exchange_rate'] = $tipo_cambio['tipo_cambio'];


        //
        return $response->withJson($results, 200);
    }






    
    
    //
    public function GetSearchPosRegistersSalesUsers($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $user_id = $ses_data['id'];

        //
        $pos_register_id = $args['pos_register_id'];
        $str_pos_reg_id = (is_numeric($pos_register_id)) ? " And pos_register_id = {$pos_register_id}" : ""; 
        //echo $str_pos_reg_id; exit;

        //
        $query_text = $request->getQueryParam("q");
        //echo $query_text; exit;        
        //
        if ($query_text){
            //
            $results = Query::Multiple("
                Select 
                    created_user_id as id, 
                    created_user_name, 
                    created_user_email 
                from v_sales 
                    Where app_id = ? 
                    And sucursal_id = ? 
                    {$str_pos_reg_id}
                    And (
                        (created_user_name like '%{$query_text}%') Or (created_user_email like '%{$query_text}%')
                    )
                    Group by 
                        created_user_id, 
                        created_user_name, 
                        created_user_email 
                    ", [$app_id, $sucursal_id]);
            //dd($results); exit;
            return $response->withJson($results, 200);
        }
        //
        return $response->withJson([], 200);
    }




    //
    public function GetSearchPosRegistersUsers($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $user_id = $ses_data['id'];

        //
        $query_text = $request->getQueryParam("q");
        
        //
        if ($query_text){
            //
            $results = Query::Multiple("
                Select 
                    opened_user_id as id, 
                    opened_user_name, 
                    opened_user_email 
                from v_pos_registers 
                    Where app_id = ? 
                    And sucursal_id = ? 
                    And (
                        (opened_user_name like '%{$query_text}%') Or (opened_user_email like '%{$query_text}%')
                    )
                    Group by 
                        opened_user_id, 
                        opened_user_name, 
                        opened_user_email 

                    ", [$app_id, $sucursal_id]);
            //dd($results); exit;
            return $response->withJson($results, 200);
        }
        //
        return $response->withJson([], 200);
    }








    //
    public function GetPosAvailableUsers($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        

        

        //
        $arr_users = [];

        /**
         * 
         * Nos traemos los usuarios de la sucursal y que no sean admin
         */
        $stmt = "
            Select 
                t.account_id,
                t.user_id,
                te.name,
                t.sucursal_id,
                t.tipos_permisos
                from users_sucursales t

                    Left Join v_pos_users te On (te.account_id = t.account_id And te.id = t.user_id)

                Where 
                    t.account_id = ? And t.sucursal_id = ?
                And 
                    (te.is_admin Is Null or te.is_admin = 0)
        ";
        // 
        $users_sucursales = Query::Multiple($stmt, [$account_id, $sucursal_id]);
        //dd($users_sucursales);
        //
        if (is_array($users_sucursales)){
            //
            foreach($users_sucursales as $index => $item){           
                //dd($item);

                //
                $user_id = $item['user_id'];
                $tipos_permisos = $item['tipos_permisos'];

                //
                $user = Users::GetRecordById($account_id, $user_id);
                //dd($user);

                //
                if (isset($user['id']) && $user['id']){

                    //
                    $user['tipo_permiso'] = $tipos_permisos;

                    /**
                     * 
                     * Si es admin de sucursal lo agregamos directamente
                     */
                    if ( $tipos_permisos == TIPO_PERMISO_ID_TODOS){
                        //echo "todos los permisos"; exit;
                        array_push($arr_users, $user);
                    }

                    /**
                     * 
                     * Si tiene permisos especificos los buscamos y si tiene lo agregamos
                     */
                    else if ( $tipos_permisos == TIPO_PERMISO_ID_ESPECIFICOS){
                        //echo "es por permisos especificos"; exit;
                        //
                        $user_permission = UsersSucursalesPermisos::userHasPermission($account_id, $sucursal_id, $user_id, "pos");
                        if ($user_permission && isset($user_permission['id'])){
                            //echo "si tiene todos o agregar venta"; exit;
                            $user['perm_info'] = $user_permission;
                            array_push($arr_users, $user);
                        }
                        
                    } 
                    
                    
                }
            }
        }
        //dd($arr_users);

        //
        $arr_admins = Users::GetAllPosAdminUsers($account_id);
        //dd($arr_admins);
        
        //
        $arr_all = array_merge($arr_admins, $arr_users);
        //dd($arr_all);

        //
        return $response->withJson($arr_all, 200);
    }




    //
    public function PostAddSale($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];

        //
        $results = array();
        //        
        $pos_user_id = Helper::safeVar($request->getParsedBody(), 'pos_user_id');
        $pos_register_id = Helper::safeVar($request->getParsedBody(), 'pos_register_id');        
        $arr_products = Helper::safeVar($request->getParsedBody(), 'arr_products');
        $arr_payments = Helper::safeVar($request->getParsedBody(), 'arr_payments');
        $sale_notes = Helper::safeVar($request->getParsedBody(), 'sale_notes');
        $customer_id = Helper::safeVar($request->getParsedBody(), 'customer_id');
        $promo_id = Helper::safeVar($request->getParsedBody(), 'promo_id');
        //echo "$customer_id, $promo_id"; exit;





        

        
        // default totales
        $sub_total = 0;
        $grand_total = 0;
        $total_commissions = 0;
        //
        $total_paid = 0;
        $change_amount = 0;
        

        // default impuestos data
        $tax_id = null;
        $tax_percent = 0;
        $tax_amount = 0;

        
        // Totales por método de pago
        $total_paid_efectivo = 0;
        $total_paid_tarjeta = 0;
        $total_paid_usd_mxn = 0;
        $total_paid_usd_amount = 0;
        
        //
        $sale_code = null;

        //
        if ( !($arr_products && is_array($arr_products)) ){
            $results['error'] = "error, faltan productos o servicios";
            return $response->withJson($results, 200);            
        }
        //
        if ( !($arr_payments && is_array($arr_payments)) ){
            $results['error'] = "error, falta pago de la venta";
            return $response->withJson($results, 200);            
        }



        /**
         * 
         * 
         * Gestiona cliene
         */

        // default cust data
        $customer_name = null;
        $email = null;
        $phone_number = null;
        $address = null;

        //
        if (is_numeric($customer_id)){
            //
            $customer_data = Query::Single("Select * From v_customers where app_id = ? And id = ?", [$app_id, $customer_id]);
            //dd($customer_data);
            if ( isset($customer_data['error']) && $customer_data['error'] ){
                $results['error'] = $customer_data['error'];
                return $response->withJson($results, 200);            
            }
            if ( !(isset($customer_data['id']) && $customer_data['id']) ){
                $results['error'] = "No se encontro cliente con id $customer_id";
                return $response->withJson($results, 200);            
            }
            //
            $customer_name = $customer_data['name'];
            $email = $customer_data['email'];
            $phone_number = $customer_data['phone_cc'] . " " . $customer_data['phone_number'];
            $address = $customer_data['address'];
        }
        




        /**
         * 
         * Tiene Promo?
         * Primero validamos si tiene promo y obtenemos la informacion para usar posteriormente
         */
        //
        $valid_promo = null;

        // default promo data
        $promo_info = null;
        $discount_percent = 0;
        $discount_amount = 0;

        //
        if (is_numeric($promo_id)){
            //
            $promo_data = Query::Single("
                SELECT * 
                FROM v_promos_discounts 
                WHERE app_id = ? 
                AND id = ? 
                AND enabled_pdv = 1
                AND fecha_hora_inicio <= GETDATE() 
                AND fecha_hora_fin >= GETDATE()
                    ", [$app_id, $promo_id]);
            //dd($promo_data);
            if ( isset($promo_data['error']) && $promo_data['error'] ){
                $results['error'] = $promo_data['error'];
                return $response->withJson($results, 200);            
            }
            if ( !(isset($promo_data['id']) && $promo_data['id']) ){
                $results['error'] = "No se encontro promocion con id $promo_id, no esta activa o fecha expirada";
                return $response->withJson($results, 200);            
            }
            //
            $valid_promo = $promo_data;
            $promo_info = $promo_data['clave'] . " - " . $promo_data['descripcion'];
        }




        //
        $require_employee_commissions = true;




        //
        $arr_add_items = [];
        //
        $arr_appointments_to_update = [];

        /**
         * Procesar productos y calcular sub_total
         */
        //dd($arr_products);
        foreach($arr_products as $item){
            //dd($item);

            //
            $is_cita = $item['is_cita'];
            $employee = $item['employee'];
            $appointment_id = null;


            /**
             * 
             * Gestionamos informacion de productos
             */
            if (!$is_cita){

                //echo "--no es cita** ";

                //
                $product_id = $item['id'];
                $qty = $item['qty'];

                //
                $product_info = Query::Single("Select * From v_products where app_id = ? And id = ?", [$app_id, $product_id]);
                //dd($product_info);
                if ( isset($product_info['error']) && $product_info['error'] ){
                    $results['error'] = $product_info['error'];
                    return $response->withJson($results, 200);            
                }
                if ( !(isset($product_info['id']) && $product_info['id']) ){
                    $results['error'] = "Item con id $product_id no existe";
                    return $response->withJson($results, 200);            
                }

                //
                $tipo_producto_servicio = $product_info['tps'];
                $product_code = $product_info['prod_code'];
                $category = $product_info['category'];
                $nombre = $product_info['nombre'];
                $price = $product_info['precio'];
            } 
            
            /**
             * 
             * Gestionamos informacion de cita
             * A partir de aqui obtenemos el id del producto y de cita
             */
            else {

                //echo "--SI es cita** ";

                //
                $parts = explode('-', $item['id']);
                $appointment_id = end($parts);
                //echo $appointment_id; exit;

                // SI ES CITA SIEMPRE ES 1, NO PUEDEN SER 2 CITAS
                $qty = 1;
                
                //
                $apmtn_info = Query::Single("Select * From v_appointments where app_id = ? And id = ?", [$app_id, $appointment_id]);                
                //dd($apmtn_info);
                if ( isset($apmtn_info['error']) && $apmtn_info['error'] ){
                    $results['error'] = $apmtn_info['error'];
                    return $response->withJson($results, 200);            
                }
                if ( !(isset($apmtn_info['id']) && $apmtn_info['id']) ){
                    $results['error'] = "No se encontro cita con id $appointment_id";
                    return $response->withJson($results, 200);            
                }
                if ( $apmtn_info['sale_id'] && $apmtn_info['sale_item_id'] ){
                    $results['error'] = "Cita con id $appointment_id ya ha sido agregada a otra venta con folio #" . $apmtn_info['sale_id'];
                    return $response->withJson($results, 200);            
                }

                //
                $tipo_producto_servicio = "s";
                $product_id = $apmtn_info['service_id'];
                $product_code = $apmtn_info['prod_code'];
                $category = $apmtn_info['service_category'];
                $nombre = "Cita: " . $apmtn_info['title'];
                $price = $apmtn_info['precio'];
                //
                array_push($arr_appointments_to_update, $appointment_id);
            }

            
            
            //
            $discount_id = null;
            $discount_percent_item = 0;
            $discount_amount_item = 0;
            // 
            $item_final_price = floatval($qty) * floatval($price);
            $item_info = "$nombre ($category)";


            // Sumar al subtotal
            $sub_total += $item_final_price;

            // DEBUG TOTALES
            //echo "$item_info, $qty, $price, $item_final_price, $sub_total <br />";


            //
            $new_item_data = [
                "id" => $product_id,
                "appointment_id" => $appointment_id,
                "code" => $product_code,
                "item_info" => $item_info,
                "price" => $price,
                "qty" => $qty,
                "discount_id" => $discount_id,
                "discount_percent" => $discount_percent_item,
                "discount_amount" => $discount_amount_item,
                "final_price" => $item_final_price,
            ];

            
            //
            if ($require_employee_commissions){

                // si es tipo servicio
                if ($tipo_producto_servicio=="s"){

                    //
                    if (!($employee && isset($employee['id']) && $employee['id'])){
                        $results['error'] = "se requiere el empleado que genero el servicio";
                        return $response->withJson($results, 200);  
                    }
                    $employee_id = $employee['id'];
                    //echo $employee_id; exit;
                    //
                    $employee_info = Query::Single("Select * From v_employees where app_id = ? And id = ? And commission_rate > 0 ", [$app_id, $employee_id]);
                    //dd($employee_info);
                    if ( isset($employee_info['error']) && $employee_info['error'] ){
                        $results['error'] = $employee_info['error'];
                        return $response->withJson($results, 200);            
                    }
                    if ( !(isset($employee_info['id']) && $employee_info['id']) ){
                        $results['error'] = "Employee not found, inactive or without commission ";
                        return $response->withJson($results, 200);            
                    }

                    // 
                    $new_item_data['employee_id'] = $employee_id;
                    $new_item_data['employee_info'] = $employee_info['job_title'] . " - " . $employee_info['name'];
                    $new_item_data['employee_commission_rate'] = $employee_info['commission_rate'];
                    //
                    $new_item_data['employee_commission_amount'] = formatCurrency($item_final_price * $new_item_data['employee_commission_rate'] / 100);
                    // sumamos al total de comisions
                    $total_commissions += $new_item_data['employee_commission_amount'];
                }

            }
            



            // Agregar al array de items
            array_push($arr_add_items, $new_item_data);
        }



        //dd($arr_appointments_to_update);
        

        
        //
        $total_commissions = formatCurrency($total_commissions);
        $sub_total = formatCurrency($sub_total);
        //echo "$sub_total / $total_commissions "; dd($arr_add_items);



        /**
         * Procesar pagos, validar métodos y calcular totales por tipo
         */
        $arr_add_payments = [];

        foreach($arr_payments as $item){
            $payment_type_id = $item['payment_method_id'];
            
            // Validar método de pago existe
            $payment_info = Query::Single("Select * From sys_payment_types where id = ?", [$payment_type_id]);
            
            if ( isset($payment_info['error']) && $payment_info['error'] ){
                $results['error'] = $payment_info['error'];
                return $response->withJson($results, 200);            
            }
            if ( !(isset($payment_info['id']) && $payment_info['id']) ){
                $results['error'] = "metodo de pago con id $payment_type_id no existe";
                return $response->withJson($results, 200);            
            }

            $payment_type = $payment_info['payment_type'];

            //
            $amount_mxn = formatCurrency(floatval($item['amount_mxn']));
            $amount_usd = formatCurrency(floatval($item['amount_usd']) ?: 0);


            // Sumar al total pagado general
            $total_paid += $amount_mxn;

            // Categorizar por método de pago
            if ($payment_type_id == PAYMENT_METHOD_ID_EFECTIVO) {
                $total_paid_efectivo += $amount_mxn;
            } else if ($payment_type_id == PAYMENT_METHOD_ID_DOLARES) {
                $total_paid_usd_mxn += $amount_mxn;
                $total_paid_usd_amount += $amount_usd;
            } else {
                // Cualquier otro método (tarjetas, etc.)
                $total_paid_tarjeta += $amount_mxn;
            }

            // Agregar al array de pagos
            array_push($arr_add_payments, [
                "payment_type_id" => $payment_type_id,
                "payment_type" => $payment_type,
                "amount_mxn" => $amount_mxn,
                "amount_usd" => $amount_usd,
            ]);
        }




        // 5. ✅ Redondear totales de pagos después del loop
        $total_paid = formatCurrency($total_paid);
        $total_paid_efectivo = formatCurrency($total_paid_efectivo);
        $total_paid_tarjeta = formatCurrency($total_paid_tarjeta);
        $total_paid_usd_mxn = formatCurrency($total_paid_usd_mxn);
        $total_paid_usd_amount = formatCurrency($total_paid_usd_amount);




        /**
         * Si tenemos una promo valida (previamente validada) usamos su data
         * para calcular los totales
         */
        if ($valid_promo && isset($valid_promo['id'])){

            //
            $es_porcentaje = $valid_promo['es_porcentaje'];
            $promo_valor = floatval($valid_promo['valor']);
            
            // Crear promo_info con formato descriptivo
            $discount_display = $es_porcentaje ? $promo_valor . '%' : '-$' . $promo_valor;
            $promo_info = $valid_promo['clave'] . " - " . $valid_promo['descripcion'] . " (" . $discount_display . ")";
            
            if ($es_porcentaje) {
                // Porcentaje: calcular % del subtotal
                $discount_percent = $promo_valor;
                $discount_amount = $sub_total * ($promo_valor / 100);
            } else {
                // Monto fijo: usar valor directo
                $discount_percent = 0;
                $discount_amount = $promo_valor;
            }
            
            // Limitar descuento al subtotal (no puede ser mayor)
            $discount_amount = formatCurrency(min($discount_amount, $sub_total));
            
            /*
            // Debug de totales
            echo "=== DEBUG DESCUENTOS ===\n";
            echo "Promo info: $promo_info\n";
            echo "Subtotal: $sub_total\n";
            echo "Descuento $: $discount_amount\n";
            echo "========================\n";
            */
            
        } else {
            // Sin promo
            $promo_info = null;
            $discount_percent = 0;
            $discount_amount = 0;
        }

        
            


        /**
         * Calcular totales finales
         */
        $grand_total = formatCurrency($sub_total - $discount_amount + $tax_amount);
        //$change_amount = ($total_paid > $grand_total) ? ($total_paid - $grand_total) : 0;
        $change_amount = formatCurrency(max(0, $total_paid - $grand_total));
        

        /*
        echo "
            sub_total: $sub_total <br />
            discount_amount: $discount_amount <br />
            tax_amount: $tax_amount <br />
            total_paid: $total_paid <br />
            grand_total: $grand_total <br />
            change_amount: $change_amount <br />        
        "; 
        exit;
        */



        // Validar que el pago sea suficiente
        if ($total_paid < $grand_total) {
            $results['error'] = "El pago ($total_paid) es menor al total de la venta ($grand_total)";
            return $response->withJson($results, 200);
        }


        
        
        


        //
        $sale_params = [
            $app_id, $pos_register_id, $sucursal_id, $pos_user_id,
            $customer_id, $customer_name, $email, $phone_number,
            $address, $promo_id, $promo_info, $discount_percent, $discount_amount, 
            $sub_total, $tax_id, $tax_percent, $tax_amount,
            $grand_total, $total_commissions, $total_paid, $change_amount,
            $total_paid_efectivo, $total_paid_tarjeta, $total_paid_usd_mxn, $total_paid_usd_amount,
            $sale_code, $sale_notes
        ];

        /**
         * Insertar venta principal con todos los totales incluyendo totales por método
         */
        $insert_sale = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                Insert Into sales
                ( app_id, pos_register_id, sucursal_id, created_user_id, 
                customer_id, customer_name, email, phone_number, 
                address, promo_id, promo_info, discount_percent, discount_amount, 
                sub_total, tax_id, tax_percent, tax_amount,
                grand_total, total_comissions, total_paid, change_amount, 
                total_paid_efectivo, total_paid_tarjeta, total_paid_usd_mxn, total_paid_usd_amount,
                sale_code, notes, datetime_created )
                Values
                ( ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, GETDATE() )
                ;SELECT SCOPE_IDENTITY()
                ",
            "params" => $sale_params,
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);

        if ( isset($insert_sale['error']) && $insert_sale['error'] ){
            $results['error'] = $insert_sale['error'];
            return $response->withJson($results, 200);            
        }

        $sale_id = $insert_sale['id'];
        $api_res = [
            'id' => $sale_id,
            'sub_total' => $sub_total,
            'grand_total' => $grand_total,
            'total_commissions' => $total_commissions,
            'total_paid' => $total_paid,
            'change_amount' => $change_amount,
            'total_paid_efectivo' => $total_paid_efectivo,
            'total_paid_tarjeta' => $total_paid_tarjeta,
            'total_paid_usd_mxn' => $total_paid_usd_mxn,
            'total_paid_usd_amount' => $total_paid_usd_amount,
            'arr_prods' => [],
            'arr_pmts' => []
        ];

        /**
         * Insertar items de la venta 
         */
        foreach($arr_add_items as $item){

            //
            $final_price = formatCurrency($item['final_price']);

            // 
            $employee_id = null;
            $employee_info = null;
            $employee_commission_rate = null;
            $employee_commission_amount = 0;
            //
            if ( isset($item['employee_id']) && $item['employee_id'] && $item['employee_info'] && $item['employee_commission_rate'] && $item['employee_commission_amount'] ){
                $employee_id = $item['employee_id'];
                $employee_info = $item['employee_info'];
                $employee_commission_rate = $item['employee_commission_rate'];
                $employee_commission_amount = $item['employee_commission_amount'];
            }
            //echo "$employee_id, $employee_info, $employee_commission_rate, $final_price, $employee_commission_amount"; exit;

            //
            $insert_sale_item = Query::DoTask([
                "task" => "add",
                "debug" => true,
                "stmt" => "
                    Insert Into sales_items
                    ( app_id, pos_register_id, sale_id,
                    appointment_id, product_id, product_code, item_info, price, qty, 
                    discount_id, discount_percent, discount_amount, final_price, 
                    employee_id, employee_info, employee_commission_rate, employee_commission_amount,
                    datetime_created )
                    Values
                    ( ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    GETDATE() )
                    ;SELECT SCOPE_IDENTITY()
                    ",
                "params" => [
                    $app_id, $pos_register_id, $sale_id,
                    $item['appointment_id'], $item['id'], $item['code'], $item['item_info'], $item['price'], $item['qty'],
                    $item['discount_id'], $item['discount_percent'], $item['discount_amount'], $final_price,
                    $employee_id,$employee_info, $employee_commission_rate, $employee_commission_amount
                ],
                "parse" => function($insert_id, &$query_results){
                    $query_results['id'] = (int)$insert_id;
                }
            ]);

            if ( isset($insert_sale_item['error']) && $insert_sale_item['error'] ){
                $results['error'] = $insert_sale_item['error'];
                return $response->withJson($results, 200);            
            }

            array_push($api_res['arr_prods'], $insert_sale_item);
        }

        /**
         * Insertar pagos de la venta
         */
        foreach($arr_add_payments as $item){
            $insert_sale_payment = Query::DoTask([
                "task" => "add",
                "debug" => true,
                "stmt" => "
                    Insert Into sales_payments
                    ( app_id, pos_register_id, sale_id, payment_type_id, payment_type, 
                    amount_mxn, amount_usd, datetime_created )
                    Values
                    ( ?, ?, ?, ?, ?,
                      ?, ?, GETDATE() )
                    ;SELECT SCOPE_IDENTITY()
                    ",
                "params" => [
                    $app_id, $pos_register_id, $sale_id, $item['payment_type_id'], $item['payment_type'],
                    $item['amount_mxn'], $item['amount_usd']
                ],
                "parse" => function($insert_id, &$query_results){
                    $query_results['id'] = (int)$insert_id;
                }
            ]);

            if ( isset($insert_sale_payment['error']) && $insert_sale_payment['error'] ){
                $results['error'] = $insert_sale_payment['error'];
                return $response->withJson($results, 200);            
            }

            array_push($api_res['arr_pmts'], $insert_sale_payment);
        }


        
        //$api_res['sale_info'] = $sale_params;
        $api_res['arr_items'] = $arr_add_items;
        $api_res['arr_payments'] = $arr_add_payments;


        //
        return $response->withJson($api_res, 200);
    }






    
    //
    public function ViewTicketHtml($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];




        // ================================
        // CONFIGURACIÓN DEL TICKET
        // ================================
        
        // Variables mandatorias (siempre se muestran)
        // - Nombre de la empresa/sucursal
        // - Ticket de Venta
        // - Folio
        // - Fecha
        // - Items y totales
        // - Pagos
        
        // Variables opcionales de empresa 
        $show_company_rfc = true;
        $show_company_address = true;
        $show_company_phone = true;
        $show_company_email = true;
        
        // Variables opcionales de venta
        $show_num_caja = true;
        $show_cajero = true;
        
        // Variables de footer
        $footer_message = "¡Gracias por su preferencia!";
        $footer_submessage = "Esperamos verle pronto";
        $show_footer_message = true;
        $show_footer_submessage = true;
        $show_footer_system_info = true;
        $ticket_in_bold = true;
        
        
        //
        $sale_id = $args['sale_id'];

        //
        $saleData = Query::Single("Select * From v_sales where app_id = ? And id = ?", [$app_id, $sale_id]);
        //dd($saleData);
        if ( !isset($saleData['id']) ){
            echo "Error, no se encontro la venta"; exit;
        }
        
        $pos_register_id = $saleData['pos_register_id'];

        $sucursal_res = Query::Single("
            Select * From sucursales t
                where t.account_id = ?
                And t.id = (
                    Select Top 1 sucursal_id From pos_registers where app_id = ? And id = ?
                )
                ",
            [
                $account_id,
                $app_id, 
                $pos_register_id
            ]
        );
        //dd($sucursal_res);
        //
        if ( !isset($sucursal_res['id']) ){
            echo "Error, no se encontro la sucursal"; exit;
        }

        //
        $saleData['arr_items'] = Query::Multiple("Select * From sales_items where sale_id = ?", [$sale_id]);
        $saleData['arr_payments'] = Query::Multiple("Select * From sales_payments where sale_id = ?", [$sale_id]);
        //
        $company_name = $sucursal_res['nombre_razon_social'];
        $company_rfc = $sucursal_res['rfc'];
        $company_address = $sucursal_res['address'];
        $company_phone = $sucursal_res['phone_cc'] . ' ' . $sucursal_res['phone_number'];
        $company_email = $sucursal_res['email'];
        //    
        $sale_datetime = $saleData['datetime_created']->format('d/m/Y H:i:s');

        



        $str_bold = "";
        if ($ticket_in_bold){
            $str_bold = "font-weight:bold;";
        }

        //
        $css_styles = "
            @page {
                margin: 0;
                size: 68mm auto;
            }
            
            body { 
                font-family: 'Courier New', monospace; 
                font-size: 13px; /* +3 puntos total */
                margin: 0; 
                padding: 1mm 2mm 2mm 0.5mm; /* Agregar margen derecho */
                width: 68mm; /* Ajustar ancho para el margen derecho */
                line-height: 1.2;
                color: #000;
                background: white;
                {$str_bold}
            }
            
            .header { 
                text-align: center; 
                border-bottom: 1px dashed #000; 
                padding-bottom: 4px; 
                margin-bottom: 4px; 
            }
            
            .company-name {
                font-size: 15px; /* +3 puntos total */
                font-weight: bold;
                margin-bottom: 2px;
                text-transform: uppercase;
            }
            
            .ticket-title {
                font-size: 14px; /* +3 puntos total */
                font-weight: bold;
                margin: 2px 0;
            }
            
            .sucursal-name {
                font-size: 13px; /* +3 puntos total */
                margin: 2px 0;
            }
            
            .company-info {
                font-size: 11px; /* +3 puntos total */
                text-align: center;
                margin-bottom: 4px;
                line-height: 1.1;
            }
            
            .sale-info {
                font-size: 12px; /* +3 puntos total */
                text-align: center;
                margin: 4px 0;
                line-height: 1.1;
            }
            
            .item { 
                display: flex; 
                justify-content: space-between; 
                margin: 1px 0; 
                font-size: 12px; /* +3 puntos total */
                line-height: 1.1;
            }
            
            .item-desc {
                margin: 1px 0;
                font-size: 11px; /* +3 puntos total */
                word-wrap: break-word;
                line-height: 1.1;
                max-width: 100%;
            }
            
            .section {
                margin: 3px 0;
            }
            
            .total-line { 
                border-top: 1px dashed #000; 
                padding-top: 2px; 
                margin-top: 3px; 
            }
            
            .payments-section {
                border-top: 1px dashed #000;
                padding-top: 2px;
                margin-top: 3px;
            }
            
            .footer-section {
                border-top: 1px dashed #000;
                padding-top: 3px;
                margin-top: 4px;
                text-align: center;
                font-size: 11px; /* +3 puntos total */
            }
            
            .bold { font-weight: bold; }
            .center { text-align: center; }
            .small { font-size: 11px; } /* +3 puntos total */
            .no-print { display: none; }
            
            /* Específico para impresión térmica */
            @media print {
                body { 
                    width: 68mm !important; /* Ajustado para margen derecho */
                    margin: 0 !important; 
                    padding: 1mm 1mm 2mm 0.5mm !important; /* Margen derecho agregado */
                    font-size: 12px !important; /* +3 puntos total */
                }
                .no-print { display: none !important; }
                .header { page-break-inside: avoid; }
                .section { page-break-inside: avoid; }
                .total-line { page-break-inside: avoid; }
                .payments-section { page-break-inside: avoid; }
                .footer-section { page-break-inside: avoid; }
            }
        ";

        

        // HEADER SECTION
        $header_section = "
            <div class='header'>
                <div class='ticket-title'>TICKET DE VENTA</div>
                <div class='sucursal-name'>{$company_name}</div>
        ";
        
        // Información opcional de la empresa
        if ($show_company_rfc || $show_company_address || $show_company_phone || $show_company_email) {
            $header_section .= "<div class='company-info'>";
            
            if ($show_company_rfc) {
                $header_section .= "<div>RFC: {$company_rfc}</div>";
            }
            if ($show_company_address) {
                $header_section .= "<div>{$company_address}</div>";
            }
            if ($show_company_phone) {
                $header_section .= "<div>Tel: {$company_phone}</div>";
            }
            if ($show_company_email) {
                $header_section .= "<div>{$company_email}</div>";
            }
            
            $header_section .= "</div>";
        }
        
        // Información de la venta (mandatorio: Folio y Fecha)
        $header_section .= "
                <div class='sale-info'>
                    <div>Folio: {$saleData['id']}</div>
                    <div>Fecha: {$sale_datetime}</div>
        ";
        
        // Información opcional de la venta
        if ($show_num_caja) {
            $header_section .= "<div>Caja: {$saleData['pos_name']}</div>";
        }
        if ($show_cajero) {
            $header_section .= "<div>Cajero: {$saleData['created_user_name']}</div>";
        }
        
        $header_section .= "
                </div>
            </div>
        ";

        // ITEMS SECTION (mandatorio)
        $items_section = "<div class='section'>";
        foreach ($saleData['arr_items'] as $item) {
            $price_formatted = number_format(floatval($item['price']), 2);
            $final_price_formatted = number_format(floatval($item['final_price']), 2);
            $qty_formatted = number_format(floatval($item['qty']), 0);
            
            $items_section .= "
                <div class='item-desc'>{$item['product_code']} - {$item['item_info']}</div>
                <div class='item'>
                    <span>{$qty_formatted} x \${$price_formatted}</span>
                    <span>\${$final_price_formatted}</span>
                </div>
            ";
        }
        $items_section .= "</div>";

        // DISCOUNTS SECTION (si existe promo)
        $discounts_section = "";
        if ($saleData['promo_id'] && floatval($saleData['discount_amount']) > 0) {
            $sub_total_formatted = number_format(floatval($saleData['sub_total']), 2);
            $discount_amount_formatted = number_format(floatval($saleData['discount_amount']), 2);
            
            $discounts_section = "
                <div class='section' style='border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px;'>
                    <div class='item'>
                        <span>Subtotal:</span>
                        <span>\${$sub_total_formatted}</span>
                    </div>
                    <div class='item' style='color: #666;'>
                        <span>{$saleData['promo_info']}</span>
                        <span>-\${$discount_amount_formatted}</span>
                    </div>
                </div>
            ";
        }


        // TOTALS SECTION (mandatorio)
        $grand_total_formatted = number_format(floatval($saleData['grand_total']), 2);
        
        $totals_section = "
            <div class='total-line'>
                <div class='item bold' style='font-size: 13px;'>
                    <span>TOTAL:</span>
                    <span>\${$grand_total_formatted}</span>
                </div>
            </div>
        ";

        // PAYMENTS SECTION (mandatorio)
        $payments_section = "
            <div class='payments-section'>
                <div class='bold small center'>PAGOS RECIBIDOS</div>
        ";
        
        foreach ($saleData['arr_payments'] as $payment) {
            $amount_mxn_formatted = number_format(floatval($payment['amount_mxn']), 2);
            $usd_text = floatval($payment['amount_usd']) > 0 ? " (" . number_format(floatval($payment['amount_usd']), 2) . " USD)" : '';
            
            $payments_section .= "
                <div class='item'>
                    <span>{$payment['payment_type']}</span>
                    <span>\${$amount_mxn_formatted}{$usd_text}</span>
                </div>
            ";
        }
        
        $total_paid_formatted = number_format(floatval($saleData['total_paid']), 2);
        
        $payments_section .= "
            <div class='item bold'>
                <span>Total Pagado:</span>
                <span>\${$total_paid_formatted}</span>
            </div>
        ";

        // Mostrar cambio si existe
        if (floatval($saleData['change_amount']) > 0) {
            $change_amount_formatted = number_format(floatval($saleData['change_amount']), 2);
            $payments_section .= "
                <div class='item bold' style='font-size: 12px;'>
                    <span>CAMBIO:</span>
                    <span>\${$change_amount_formatted}</span>
                </div>
            ";
        }
        
        $payments_section .= "</div>";

        // FOOTER SECTION (opcional)
        $footer_section = "";
        if ($show_footer_message || $show_footer_submessage || $show_footer_system_info) {
            $footer_section = "<div class='footer-section' style='margin:10px 0;padding:20px 0'>";
            
            if ($show_footer_message) {
                $footer_section .= "<div class='bold'>{$footer_message}</div>";
            }
            if ($show_footer_submessage) {
                $footer_section .= "<div class='small'>{$footer_submessage}</div>";
            }
            if ($show_footer_system_info) {
                $footer_section .= "<div class='small' style='margin-top: 8px;'>www.BarberDesk.com - {$sale_datetime}</div>";
            }
            
            $footer_section .= "</div>";
        }


        // COMPLETE HTML
        $ticketHtml = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket - Venta #{$saleData['id']}</title>
            <style>{$css_styles}</style>
        </head>
        <body>
            {$header_section}
            {$items_section}
            {$discounts_section}
            {$totals_section}
            {$payments_section}
            {$footer_section}
        </body>
        </html>
        ";
        //
        //echo $ticketHtml; exit;

        //
        //dd($saleData);
        $customer_name = $saleData['customer_name'];
        $customer_email = $saleData['email'];
        //$customer_name = "Ivan Juarez";
        //$customer_email = "ivanjzr@gmail.com";

        //
        if ($customer_name && $customer_email){
            PosHelper::sendTicketEmail($account_id, $app_id, $saleData['id'], $company_name, $customer_name, $customer_email, $ticketHtml);
        }

        // Devolver HTML directamente
        return $response->write($ticketHtml);
    }




}

