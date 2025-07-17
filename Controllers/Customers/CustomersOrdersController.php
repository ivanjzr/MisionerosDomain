<?php
namespace Controllers\Customers;


//
use App\Orders\Orders;
use App\Paths;
use App\Stores\Stores;
use Controllers\BaseController;
use Google\Service\Compute\Help;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class CustomersOrdersController extends BaseController
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
        $table_name = "ViewOrders";
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
        $customer_id = $ses_data['id'];



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
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
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
                                                 
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $customer_id
                ),

                //
                "parseRows" => function(&$row){
                    //dd($row); exit;
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }





    //
    public function PaginateScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewOrders";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";


        // test
        //$search_clause .= " and 1=2 ";
        //sleep(1);


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");

        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
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
                                      
                                        t.*
                                           
                                        
                                        From {$table_name} t
                                                 
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }





    //
    public function PaginateRecordsPrimeReact($request, $response, $args) {

        //
        $table_name = "ViewOrders";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];
        //echo " $customer_id "; exit;

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        //echo " $start_record $num_records "; exit;


        //
        $selected_store_id = (int)$request->getQueryParam("sid");
        if ( is_numeric($selected_store_id) && $selected_store_id ){
            $search_clause .= " And t.store_id = {$selected_store_id}";
        }
        //echo $search_clause; exit;



        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
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
                                      
                                        t.*
                                      
                                        From {$table_name} t
                                                
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;

                    //
                    $row['arr_items'] = Query::Multiple("Select * from orders_items where order_id = ?", [
                        $row['id']
                    ], function(&$row2){
                        /**/
                    });

                    /*
                    $prod_name = "p-".$row['id'].".".$row['img_ext'];
                    //
                    $product_path = Stores::getStoreSectionPath($row['customer_id'], "products").DS.$prod_name;
                    $product_url = FULL_DOMAIN."/files/stores/".$row['customer_id']."/products/".$prod_name;
                    //
                    if (is_file($product_path)){
                        $row['prod_img'] = $product_url;
                    }
                    */

                }
            ]
        );


        //dd($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function PaginatePublicScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewOrders";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $customer_id = $args['customer_id'];

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");

        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
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
                                      
                                        t.*
                                           
                                        
                                        From {$table_name} t
                                                 
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }






    //
    public function GetListStores($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $customer_id = $ses_data['id'];
        //echo $customer_id; exit;


        //
        $stores_results = Query::Multiple("
            select 
                t.store_id as id, 
                ts.company_name,
                ts.store_title
            from orders t 
                Left Join ViewStores ts ON ts.id = t.store_id 
                    Where t.customer_id = ? 
            Group By t.store_id, ts.company_name, ts.store_title
            ",
            [$customer_id]
        );
        //dd($results); exit;


        //
        return $response->withJson($stores_results, 200);
    }






    //
    public function GetRecordById($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];


        //
        $record_id = $args['id'];
        //
        $results = Query::Single("Select * from ViewOrders Where customer_id = ? And id = ?", [$customer_id, $record_id]);


        //
        if ($results && isset($results['id'])){

            //
            $results['arr_items'] = Query::Multiple("Select * from orders_items where order_id = ?", [
                $results['id']
            ], function(&$row2){
                /**/
            });

        }





        //
        return $response->withJson($results, 200);
    }





    //
    public function PostRequestOrder($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $customer_id = $ses_data['id'];







        //
        $results = array();

        //
        $sucursal_id = null;





        //
        $arr_order = Helper::safeVar($request->getParsedBody(), 'arr_order');
        $store_id = Helper::safeVar($request->getParsedBody(), 'store_id');
        $order_notes = Helper::safeVar($request->getParsedBody(), 'order_notes');
        //dd($arr_order); exit;
        //echo " $store_id $order_notes "; exit;



        //
        if ( !(is_array($arr_order) && count($arr_order) > 0) ){
            $results['error'] = "se requiren los productos de la orden";
            return $response->withJson($results, 200);
        }

        //
        if ( !is_numeric($store_id) ){
            $results['error'] = "se require la tienda de la orden";
            return $response->withJson($results, 200);
        }




        /*
         * Create Store Plan
         * */

        //
        $sale_results = Orders::RequestOrder(
            $customer_id,
            $store_id,
            $order_notes,
            $arr_order
        );
        //dd($sale_results); exit;
        if ( isset($sale_results['error']) && $sale_results['error'] ){
            $results['error'] = $sale_results['error'];
            return $response->withJson($results, 200);
        }
        //
        $new_order_id = (isset($sale_results['id']) && $sale_results['id']) ? $sale_results['id'] : null;







        /*
         * Get Order Info
         * */
        $order_info = Query::Single("Select * from ViewOrders where id = ?", [$new_order_id]);
        //dd($order_info); exit;
        $store_name = $order_info['store_name'];
        $order_email = $order_info['email'];
        $order_phone_number = $order_info['phone_number'];

        //
        $str_order_details = Orders::getMailProductsInfo($new_order_id);
        //dd($str_order_details); exit;
        //echo $str_order_details; exit;

        //
        $template_info = [];
        $template_info['store_name'] = $store_name;
        $template_info['customer_name'] = $order_info['customer_name'];
        //
        $template_info['order_details'] = $str_order_details;
        $template_info['email'] = $order_email;
        $template_info['phone_number'] = $order_phone_number;
        $template_info['order_id'] = $new_order_id;
        //
        $template_info['grand_total'] = $order_info['grand_total'];
        $template_info['notes'] = $order_info['notes'];
        //dd($template_info); exit;



        /**
         *
         *  SOLO LOS PEDIDOS SE HACEN DE PEDIDERO X ESO PROPORCIONAMOS EL "APP_ID_PEDIDERO"
         *
         */
        //
        $notify_results = null;

        /*
        //
        $notify_results['sms'] = Helper::SendSMS(ACCT_ID_PEDIDERO, MAQUETA_ID_NEW_SALE, $order_phone_number, function($maqueta_sms_msg) use($template_info){
            return Orders::ParseCustomerMessages($template_info, $maqueta_sms_msg);
        });
        //
        $notify_results['email'] = Helper::SendEmail(ACCT_ID_PEDIDERO, MAQUETA_ID_NEW_SALE, $store_name, $order_email, true, function($maqueta_email_msg) use($template_info){
            return Orders::ParseCustomerMessages($template_info, $maqueta_email_msg);
        });
        */



        //
        return $response->withJson(array(
            "notify_results" => $notify_results,
            "id" => $new_order_id
        ), 200);
    }






}
