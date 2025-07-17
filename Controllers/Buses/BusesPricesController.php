<?php
namespace Controllers\Buses;

//
use App\Paths;
use App\Buses\Buses;
use App\Buses\BusesFeatures;
use App\Buses\BusesPrices;

use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;

//
class BusesPricesController extends BaseController
{






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
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $bus_id = $args['id'];

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "buses_precios",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.bus_id = ? {$search_clause}";
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
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                                    
                                            Where t.app_id = ?
                                            And t.bus_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $bus_id,
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
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Query::All([
            //
            "stmt" => function(){
                //
                return "
                        SELECT 
                            t.* 
                        FROM buses_precios t 
                            Where t.app_id = ? 
                            And t.bus_id = ?
                            Order By t.id Desc
                              ";
            },
            //
            "params" => [
                $app_id,
                $args['id']
            ],
            //
            "parse" => function(&$row){
                //$row['demo'] = "test value";
            },
        ]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }













    //
    public function UpdatePrecio($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();

        //
        $bus_precio = Helper::safeVar($request->getParsedBody(), 'bus_precio');

        if ( !($bus_precio > 0) ){
            $results['error'] = "proporciona el precio";
            return $response->withJson($results, 200);
        }


        //
        $bus_id = $args['id'];


        //
        $update_results = BusesPrices::Create(array(
            "app_id" => $app_id,
            "bus_id" => $bus_id,
            "precio" => $bus_precio,
            "user_id" => $user_id
        ));


        //
        return $response->withJson($update_results, 200);
    }





}