<?php
namespace Controllers\Maquetas;

//
use App\Maquetas\MaquetasDocumentos;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class MaquetasDocumentosController extends BaseController
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
        return $this->container->php_view->render($response, 'admin/maquetas/maquetas_documentos/index.phtml', [
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
                        ( t.maqueta_name like '%$search_value%' )
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
        $filter_tipo_documento_id = $request->getQueryParam("filter_tipo_documento_id");
        //echo $filter_tipo_documento_id; exit;




        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "maquetas_documentos",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t 
                                    Where t.account_id = ? And t.tipo_documento_id = ? {$search_clause}";
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
                                            And t.tipo_documento_id = ?
                                                
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $account_id,
                    $filter_tipo_documento_id
                ),

                //
                "parseRows" => function(&$row) use($account_id){

                    
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
        $results = MaquetasDocumentos::GetAll($account_id);
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
        $tipo_documento_id = $args['tipo_documento_id'];

        //
        $results = MaquetasDocumentos::GetAllByType($account_id, $tipo_documento_id);
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
        $results = MaquetasDocumentos::GetRecordById( $account_id, $args['id'] );
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
        $results = MaquetasDocumentos::GetMaquetaCount( $args['tipo_documento_id'] );
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
        $tipo_documento_id = Helper::safeVar($request->getParsedBody(), 'tipo_documento_id');
        //
        $maqueta_name = Helper::safeVar($request->getParsedBody(), 'maqueta_name');
        $maqueta_content = Helper::safeVar($request->getParsedBody(), 'maqueta_content');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        $in_use = Helper::safeVar($request->getParsedBody(), 'in_use') ? 1 : 0;



        //
        $record_id = (isset($args['id']) && $args['id']) ? (int)$args['id'] : null;
        //echo $record_id; exit;

        //
        $upsert_mode = ($record_id) ? "update" : "add";

        //
        if ($record_id){
            //
            if ( !is_numeric($tipo_documento_id) ){
                $results['error'] = "proporciona el tipo de maqueta";
                return $response->withJson($results, 200);
            }
            $tipo_documento_id = (int)$tipo_documento_id;
        }
        //
        //
        if ( !$maqueta_name ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        //
        if ( !$maqueta_content ){
            $results['error'] = "proporciona el contenido";
            return $response->withJson($results, 200);
        }





        //
        $param_new_id = 0;
        

        //
        $sp_res = Query::StoredProcedure([
            "debug" => true,
            "stmt" => function(){
                return "{call usp_UpsertMaquetaDocumentos(?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($tipo_documento_id, $maqueta_name, $maqueta_content, $active, $in_use, $account_id, $record_id, &$param_new_id){
                return [
                    //
                    array($record_id, SQLSRV_PARAM_IN),
                    array($tipo_documento_id, SQLSRV_PARAM_IN),
                    //
                    array($maqueta_name, SQLSRV_PARAM_IN),
                    array($maqueta_content, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($in_use, SQLSRV_PARAM_IN),
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array(&$param_new_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        $results['id'] = $param_new_id;
        $results['mode'] = $upsert_mode;

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
            "stmt" => "Delete FROM maquetas_documentos Where account_id = ? And id = ?;SELECT @@ROWCOUNT",
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