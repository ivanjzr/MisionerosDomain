<?php
namespace Controllers\Site;

//
use App\Site\SiteLayouts;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class SiteLayoutsController extends BaseController
{













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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "site_layouts",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
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
                                      
                                        t.id,
                                        t.app_id,
                                        t.layout_name
                                      
                                        From {$table_name} t
                                      
                                            Where t.app_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id
                ),

                //
                "parseRows" => function(&$row) use($app_id){

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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = SiteLayouts::GetAll($app_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }















    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = SiteLayouts::GetRecordById( $app_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function UpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $layout_name = Helper::safeVar($request->getParsedBody(), 'layout_name');
        $layout_content = Helper::safeVar($request->getParsedBody(), 'layout_content');


        //
        $record_id = (isset($args['id']) && $args['id']) ? (int)$args['id'] : null;
        //echo $record_id; exit;


        //
        if ( !$layout_name ){
            $results['error'] = "proporciona el nombre del layout";
            return $response->withJson($results, 200);
        }




        //
        $param_new_id = 0;
        $param_mode = "this-is-the-length";

        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertSiteLayouts(?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($layout_name, $layout_content, $app_id, $record_id, &$param_new_id, &$param_mode){
                return [
                    //
                    array($layout_name, SQLSRV_PARAM_IN),
                    array($layout_content, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    //
                    array($record_id, SQLSRV_PARAM_IN),
                    array(&$param_new_id, SQLSRV_PARAM_OUT),
                    array(&$param_mode, SQLSRV_PARAM_OUT)
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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


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
            "stmt" => "Delete FROM site_layouts Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
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