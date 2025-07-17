<?php
namespace Controllers\Stores;


use App\Stores\Stores;
use App\Ventas\VentasItems;
use App\Ventas\VentasPayments;
use Controllers\BaseController;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\Query;


//
class StoresSubscriptionsController extends BaseController
{







    //
    public function getLastActiveSubscription($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];

        //sleep(2);

        //
        $results = Query::Single("Select Top 1 * from v_sales Where store_id = ? And app_id = ? And payment_status_id = 1 Order By id Desc", [$store_id, $app_id]);

        //
        return $response->withJson($results, 200);
    }







    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";

        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.clave like '%$search_value%' ) Or 
                        ( t.descripcion like '%$search_value%' )
                    )";
        }
        //
        return $search_clause;
    }


    //
    public function PaginateRecords($request, $response, $args) {
        //
        $table_name = "v_sales";
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
        //
        $app_id = $ses_data['app_id'];
        $store_id = $ses_data['id'];



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
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.store_id = ? {$search_clause}";
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
                                      
                                        t.*,
                                        (Select Count(*) From sales_msgs Where app_id = t.app_id And sale_id = t.id ) as cant_msgs
                                        
                                        From {$table_name} t
                                      
                                                 
                                            Where t.app_id = ?
                                            And t.store_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $store_id
                ),

                //
                "parseRows" => function(&$row){
                    //
                    $row['sale_items'] = VentasItems::GetAll($row['id']);
                    //
                    $row['sale_payments'] = VentasPayments::GetAll($row['id']);
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function PaginateScrollRecords($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $app_id = $ses_data['app_id'];
        $store_id = $ses_data['id'];



        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";


        // test
        //$search_clause .= " and 1=2 ";
        //sleep(1);


        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");


        //echo " $order_field $order_direction "; exit;


        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($search_clause){
                    //
                    return "Select COUNT(*) total From v_sales t
                        Where t.app_id = ?
                        And t.store_id = ? 
                        {$search_clause}
                        ";
                },
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      ( 
                                          Select 
                                            t.*,
                                            (Select Count(*) From sales_msgs Where app_id = t.app_id And sale_id = t.id ) as cant_msgs 
                                          
                                                From v_sales t 
                                                
                                                Where t.app_id = ?
                                                And t.store_id = ?
                                                {$search_clause} 
                                          ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $app_id,
                    $store_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;

                    //
                    $row['sale_items'] = VentasItems::GetAll($row['id']);
                    $row['sale_payments'] = VentasPayments::GetAll($row['id']);

                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








}
