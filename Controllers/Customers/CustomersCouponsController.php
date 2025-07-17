<?php
namespace Controllers\Customers;


use App\Coupons\Coupons;
use App\Customers\Customers;
use App\Paths;
use App\Stores\Stores;
use Controllers\BaseController;
//
use App\Coupons\CouponsCoupons;
use App\Customers\CustomersCoupons;
use Helpers\CodigoQR;
use Helpers\Helper;
use Helpers\Query;
use Stripe\Coupon;

class CustomersCouponsController extends BaseController
{




    //
    public static function configSearchClause($store_id){
        //
        $search_clause = "";

        //
        if ( $store_id ){
            $search_clause .= " And t.store_id = {$store_id}";
        }
        //
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "ViewCustomerCoupons";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));

        //
        $search_clause = self::configSearchClause( trim($request->getQueryParam("sid")));
        //echo $search_clause; exit;
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
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

                    //
                    self::getQrUrl($row);

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }




    public static function getQrUrl(&$row){
        //
        $row['qr_url'] = null;


        //
        $customer_id = $row['customer_id'];
        $customer_coupon_id = $row['id'];
        $coupon_code = $row['coupon_code'];

        //
        $coupon_year = $row['datetime_created']->format("y");
        $coupon_month = $row['datetime_created']->format("m");
        //echo " $coupon_month-$coupon_year "; exit;

        //
        $customer_dir = Paths::$path_customers.DS.$customer_id;
        $customer_coupons_dir = $customer_dir.DS."coupons";
        $customer_coupon_month_year_dir = $customer_coupons_dir.DS.$coupon_month."-".$coupon_year;
        //
        $coupon_qr_name = $customer_coupon_id."-".$coupon_code;


        //
        $qr_code_path = $customer_coupon_month_year_dir.DS.$coupon_qr_name.'.png';
        //
        if ( file_exists($qr_code_path) ){


            //
            $customer_coupon_month_year_url = FULL_DOMAIN."/files/customers/".$customer_id."/coupons/".$coupon_month."-".$coupon_year;
            $qr_code_path = $customer_coupon_month_year_url."/".$coupon_qr_name.'.png';
            //echo $qr_code_path; exit;

            //
            $reedem_url = FULL_DOMAIN."/store/account/reedem/" . $coupon_qr_name;

            //
            $row['reedem_url'] = $reedem_url;
            $row['qr_url'] = $qr_code_path;
        }
    }







    //
    public function PaginateScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewCustomerCoupons";

        //
        $order_field = "id";
        $order_direction = "Desc";



        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        $store_id = (int)$request->getQueryParam("sid");



        //
        $search_clause = "";
        //
        if ($store_id){
            $search_clause = " And t.store_id = {$store_id} ";
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
                    self::getQrUrl($row);
                    //
                    //
                    if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['img_ext'])){
                        $row['biz_logo'] = $biz_logo;
                        unset($row['img_ext']);
                    }


                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }





    //
    public function PaginateScrollRecordsPrimeReact($request, $response, $args) {

        //
        $table_name = "ViewCustomerCoupons";

        //
        $order_field = "id";
        $order_direction = "Desc";



        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");

        // ESTE ES CUANDO EL CUSTOMER FILTRA POR STORE
        $store_id = (int)$request->getQueryParam("sid");



        //
        $search_clause = "";
        //
        if ($store_id){
            $search_clause = " And t.store_id = {$store_id} ";
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
                    self::getQrUrl($row);
                    //
                    //
                    if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['img_ext'])){
                        $row['biz_logo'] = $biz_logo;
                        unset($row['img_ext']);
                    }


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
            from customers_coupons t 
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
    public function PostCutCoupon($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];


        //
        $results = array();
        $coupon_id = $args['id'];


        //
        $coupon_info = Coupons::GetCouponById($coupon_id, $customer_id);
        //dd($coupon_info); exit;
        if (isset($coupon_info['error']) && $coupon_info['error']){
            return $response->withJson($coupon_info, 200);
        }
        if ( !(isset($coupon_info['id']) && $coupon_info['id']) ){
            $results['error'] = "Cupon no existe";
            return $response->withJson($results, 200);
        }
        else if ( $coupon_info['datetime_reedemed'] ){
            $results['error'] = "Ya has canjeado este cupon";
            return $response->withJson($results, 200);
        }
        else if ( $coupon_info['datetime_cut'] ){
            $results['error'] = "Ya has recortado este cupon";
            return $response->withJson($results, 200);
        }
        else if ( $coupon_info['is_finalized'] ){
            $results['error'] = "Cupon ya ha finalizado";
            return $response->withJson($results, 200);
        }
        else if ( !$coupon_info['active'] ){
            $results['error'] = "Cupon no esta activo";
            return $response->withJson($results, 200);
        }
        $store_id = $coupon_info['store_id'];

        

        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into customers_coupons
                  ( customer_id, store_id, coupon_id, datetime_created )
                  Values
                  ( ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $customer_id,
                $store_id,
                $coupon_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);

        //
        if (isset($insert_results['error']) && $insert_results['error']){
            return $response->withJson($insert_results, 200);
        }
        //
        $new_id = $insert_results['id'];



        //
        $customer_coupon_results = CustomersCoupons::GetCustomerCouponById($customer_id, $new_id);
        //dd($customer_coupon_results); exit;
        if (isset($customer_coupon_results['error']) && $customer_coupon_results['error']){
            return $response->withJson($customer_coupon_results, 200);
        }


        //
        $coupon_year = $customer_coupon_results['datetime_created']->format("y");
        $coupon_month = $customer_coupon_results['datetime_created']->format("m");
        //echo " $coupon_month-$coupon_year "; exit;
        //
        $customer_dir = Paths::$path_customers.DS.$customer_id;
        $customer_coupons_dir = $customer_dir.DS."coupons";
        $customer_coupon_month_year_dir = $customer_coupons_dir.DS.$coupon_month."-".$coupon_year;
        // 
        if (!is_dir($customer_dir)){
            mkdir($customer_dir);
        }
        if (!is_dir($customer_coupons_dir)){
            mkdir($customer_coupons_dir);
        }
        if (!is_dir($customer_coupon_month_year_dir)){
            mkdir($customer_coupon_month_year_dir);
        }
        //
        $coupon_qr_name_path = $customer_coupon_results['id']."-".$customer_coupon_results['coupon_code'];
        //
        $qr_code_path = $customer_coupon_month_year_dir.DS.$coupon_qr_name_path.'.png';
        $qr_query_string = "http://qponea.test/store/account/coupons/".$coupon_qr_name_path;
        //echo $qr_query_string; exit;
        CodigoQR::Generar($qr_code_path, $qr_query_string);


        //
        if (!file_exists($qr_code_path)){
            $results['error'] = "Error al Generar Codigo QR, intenta de nuevo";
            return $response->withJson($results, 200);
        }


        //
        return $response->withJson($insert_results, 200);
    }







    //
    public function PostDeleteRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];





        //
        $results = array();


        //
        $customer_coupon_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$customer_coupon_id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }
        //echo $id; exit;




        //
        $coupon_info = Query::Single("select * from customers_coupons where customer_id = ? And id = ? And datetime_reedemed Is Not Null", [
            $customer_id,
            $customer_coupon_id
        ]);
        //dd($coupon_info); exit;
        if (isset($coupon_info['error']) && $coupon_info['error']){
            return $response->withJson($coupon_info, 200);
        }
        if ( isset($coupon_info['id']) && $coupon_info['id'] ){
            $results['error'] = "No se pueden  eliminar cupones redimidos";
            return $response->withJson($results, 200);
        }




        //
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM customers_coupons Where customer_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $customer_id,
                $customer_coupon_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_coupon_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_coupon_id;
            }
        ]);
        //var_dump($results); exit;



        //
        return $response->withJson($results, 200);
    }













}