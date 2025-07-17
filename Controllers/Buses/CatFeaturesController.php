<?php
namespace Controllers\Buses;

//
use App\Catalogues\CatFeatures;
use App\Paths;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class CatFeaturesController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/buses/cat_features.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
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
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.description like '%$search_value%' )
                    )";
        }
        //
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "cat_features";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];





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
                    return "Select COUNT(*) total From {$table_name} t Where t.account_id = ? {$search_clause}";
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
                  
                                            Where t.account_id = ?
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $account_id
                ),

                //
                "parseRows" => function(&$row){
                    //
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
        //dd($ses_data);
        $account_id = $ses_data['account_id'];

        //
        $results = CatFeatures::GetAll($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }
















    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = CatFeatures::GetRecordById( $account_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = array();


        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $color = Helper::safeVar($request->getParsedBody(), 'color');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        //
        $color = ($color) ? $color : null;
        $description = ($description) ? $description : null;







        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into cat_features
                  ( account_id, nombre, color, description, active, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $account_id,
                $nombre,
                $color,
                $description,
                $active
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
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = array();



        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $color = Helper::safeVar($request->getParsedBody(), 'color');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        //
        $color = ($color) ? $color : null;
        $description = ($description) ? $description : null;





        //
        $record_id = $args['id'];







        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    cat_features
                  
                  Set
                    nombre = ?,
                    color = ?,                      
                    description = ?,
                    active = ?
                  
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $nombre,
                $color,
                $description,
                $active,
                //
                $account_id,
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
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];


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
            "stmt" => "Delete FROM cat_features Where account_id = ? And id = ?;SELECT @@ROWCOUNT",
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