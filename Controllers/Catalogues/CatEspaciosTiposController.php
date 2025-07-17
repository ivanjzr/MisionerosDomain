<?php
namespace Controllers\Catalogues;

//
use App\Catalogues\CatCategories;
use App\Catalogues\CatTags;
use App\Paths;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class CatEspaciosTiposController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/catalogues/cat_espacios_tipos.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }






    //
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];


        //
        $str_where = "";
        $asiento_only = $request->getQueryParam("ao");
        if ($asiento_only){
            $str_where = "Where es_asiento = 1";
        }

        //
        $results = Query::Multiple("select t.* from cat_tipos_espacios t {$str_where}");
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }









    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or
                        ( t.descripcion like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);



        //
        $table_name = "cat_tipos_espacios";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];




        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                      
                                                 
                                            WHERE t.app_id = ?
                                            
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
                "parseRows" => function(&$row){

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }


















    //
    public function GetRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);

        //
        $record_id = $args['id'];


        //
        $results = Query::Single("select * from cat_tipos_espacios Where app_id = ? And id = ?", [$app_id, $record_id]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);



        //
        $results = array();


        //
        $espacio_tipo = Helper::safeVar($request->getParsedBody(), 'espacio_tipo');
        $seat_hex_color = Helper::safeVar($request->getParsedBody(), 'seat_hex_color');
        $descripcion = Helper::safeVar($request->getParsedBody(), 'descripcion');
        $es_asiento = Helper::safeVar($request->getParsedBody(), 'es_asiento') ? 1 : 0;

        //
        if ( !$espacio_tipo ){
            $results['error'] = "proporciona el espacio_tipo";
            return $response->withJson($results, 200);
        }
        if ( !$seat_hex_color ){
            $results['error'] = "proporciona el seat_hex_color";
            return $response->withJson($results, 200);
        }



        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into cat_tipos_espacios
                  ( espacio_tipo, seat_hex_color, descripcion, es_asiento, app_id )
                  Values
                  ( ?, ?, ?, ?, ? )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $espacio_tipo,
                $seat_hex_color,
                $descripcion,
                $es_asiento,
                $app_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        //
        return $response->withJson($insert_results, 200);
    }

















    //
    public function UpdateRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);




        //
        $results = array();



        //
        $espacio_tipo = Helper::safeVar($request->getParsedBody(), 'espacio_tipo');
        $seat_hex_color = Helper::safeVar($request->getParsedBody(), 'seat_hex_color');
        $descripcion = Helper::safeVar($request->getParsedBody(), 'descripcion');
        $es_asiento = Helper::safeVar($request->getParsedBody(), 'es_asiento') ? 1 : 0;

        //
        if ( !$espacio_tipo ){
            $results['error'] = "proporciona el espacio_tipo";
            return $response->withJson($results, 200);
        }
        if ( !$seat_hex_color ){
            $results['error'] = "proporciona el seat_hex_color";
            return $response->withJson($results, 200);
        }


        //
        $record_id = $args['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    cat_tipos_espacios
                  
                  Set
                    espacio_tipo = ?,
                    seat_hex_color = ?,
                    descripcion = ?,
                    es_asiento = ?
                   
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $espacio_tipo,
                $seat_hex_color,
                $descripcion,
                $es_asiento,
                //
                $app_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }












    //
    public function DeleteRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);


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
            "stmt" => "Delete FROM cat_tipos_espacios Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
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