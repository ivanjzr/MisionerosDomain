<?php
namespace Controllers\Stores;



use App\Stores\Stores;
use Controllers\BaseController;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\Query;


//
class StoresCouponsCustomersController extends BaseController
{









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
        $table_name = "customers_coupons";
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
        $coupon_id = $args['coupon_id'];


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
                    return "Select COUNT(*) total From {$table_name} t Where t.store_id = ? And t.coupon_id = ?{$search_clause}";
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
                                        (tc.name + ' ' + tc.last_name) as customer_name 
                                           
                                        
                                        From {$table_name} t
                                      
                                            Left Join customers tc On tc.id = t.customer_id
                                                 
                                            Where t.store_id = ?
                                            And t.coupon_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $store_id,
                    $coupon_id
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
    public function PaginateRecordsPrimeReact($request, $response, $args) {

        //
        $table_name = "customers_coupons";

        //
        $order_field = "id";
        $order_direction = "Desc";


        //
        $coupon_id = $args['coupon_id'];


        //
        $search_clause = "";

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $store_id = $ses_data['id'];
        //echo " $app_id $store_id "; exit;

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        //echo " $start_record $num_records "; exit;


        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.store_id = ? And t.coupon_id = ?{$search_clause}";
                },
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
                                        (tc.name + ' ' + tc.last_name) as customer_name 
                                           
                                        
                                        From {$table_name} t
                                      
                                            Left Join customers tc On tc.id = t.customer_id
                                                 
                                            Where t.store_id = ?
                                            And t.coupon_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $store_id,
                    $coupon_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
                }
            ]
        );


        //dd($results); exit;
        return $response->withJson($results, 200);
    }





}
