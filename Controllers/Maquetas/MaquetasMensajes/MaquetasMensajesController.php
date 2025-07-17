<?php
namespace Controllers\Maquetas\MaquetasMensajes;

//
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;
use App\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmails;
use App\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhones;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class MaquetasMensajesController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        
        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;


        //
        return $this->container->php_view->render($response, 'admin/maquetas/maquetas_mensajes/index.phtml', [
            "App" => new App(null, $ses_data)
        ]);
    }











    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.sms_msg like '%$search_value%' ) Or 
                        ( t.email_subject like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;


        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);
        //
        $filter_maqueta_id = $request->getQueryParam("filter_maqueta_id");
        //echo $filter_maqueta_id; exit;




        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "maquetas_mensajes",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t 
                                    Where t.account_id = ? And t.maqueta_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*,
                                        acc.company_name
                                      
                                        From {$table_name} t
                                      
                                            Left Join accounts acc On acc.id = t.account_id
                                      
                                            Where t.account_id = ?
                                            And t.maqueta_id = ?
                                                
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $account_id,
                    $filter_maqueta_id
                ),

                //
                "parseRows" => function(&$row) use($account_id){

                    // avoid displaying email msg
                    $row['email_msg'] = null;

                    //
                    $row['copias_emails'] = MaquetasMensajesCopiasEmails::GetAllByMensajeId($row['account_id'], $row['id']);
                    $row['copias_phones'] = MaquetasMensajesCopiasPhones::GetAllByMensajeId($row['account_id'], $row['id']);
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }










    //
    public function GetAll($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        //
        $results = MaquetasMensajes::GetAll($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





    //
    public function GetAllByType($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        $maqueta_id = $args['maqueta_id'];

        //
        $results = MaquetasMensajes::GetAllByType($account_id, $maqueta_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }














    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = MaquetasMensajes::GetRecordById( $account_id, $args['id'] );
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }






    //
    public function GetMaquetaCount($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        //
        $results = MaquetasMensajes::GetMaquetaCount( $args['maqueta_id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }











    //
    public function UpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;




        //
        $results = array();



        //
        $maqueta_id = Helper::safeVar($request->getParsedBody(), 'maqueta_id');
        //
        $sms_msg = Helper::safeVar($request->getParsedBody(), 'sms_msg');
        $sms_active = Helper::safeVar($request->getParsedBody(), 'sms_active') ? 1 : 0;
        //
        $email_subject = Helper::safeVar($request->getParsedBody(), 'email_subject');
        $email_msg = Helper::safeVar($request->getParsedBody(), 'email_msg');
        $email_active = Helper::safeVar($request->getParsedBody(), 'email_active') ? 1 : 0;
        //
        $in_use = Helper::safeVar($request->getParsedBody(), 'in_use') ? 1 : 0;



        //
        $record_id = (isset($args['id']) && $args['id']) ? (int)$args['id'] : null;
        //echo $record_id; exit;


        //
        if ($record_id){
            //
            if ( !is_numeric($maqueta_id) ){
                $results['error'] = "proporciona el tipo de producto";
                return $response->withJson($results, 200);
            }
            $maqueta_id = (int)$maqueta_id;
        }
        //
        if ( !$sms_msg ){
            $results['error'] = "proporciona el mensaje de texto";
            return $response->withJson($results, 200);
        }
        //
        if ( !$email_subject ){
            $results['error'] = "proporciona el asunto del correo";
            return $response->withJson($results, 200);
        }
        //
        if ( !$email_msg ){
            $results['error'] = "proporciona el mensaje del correo";
            return $response->withJson($results, 200);
        }





        //
        $param_new_id = 0;
        $param_mode = "tan_grande_puede_ser_la_respuesta";

        //
        $sp_res = Query::StoredProcedure([
            "debug" => true,
            "stmt" => function(){
                return "{call usp_UpsertMaquetaMensaje(?,?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($maqueta_id, $sms_msg, $sms_active, $email_subject, $email_msg, $email_active, $in_use, $account_id, $record_id, &$param_new_id, &$param_mode){
                return [
                    //
                    array($record_id, SQLSRV_PARAM_IN),
                    array($maqueta_id, SQLSRV_PARAM_IN),
                    //
                    array($sms_msg, SQLSRV_PARAM_IN),
                    array($sms_active, SQLSRV_PARAM_IN),
                    //
                    array($email_subject, SQLSRV_PARAM_IN),
                    array($email_msg, SQLSRV_PARAM_IN),
                    array($email_active, SQLSRV_PARAM_IN),
                    //
                    array($in_use, SQLSRV_PARAM_IN),
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array(&$param_new_id, SQLSRV_PARAM_OUT),
                    array(&$param_mode, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        $results['id'] = $param_new_id;
        $results['mode'] = $param_mode;

        //
        return $response->withJson($results, 200);
    }











    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        
        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;



        
        //
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }




        //
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM maquetas_mensajes Where account_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $account_id,
                $id
            ],
            "parse" => function($updated_rows, &$query_results) use($id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$id;
            }
        ]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }




}