<?php
namespace Controllers\Stores;


//
use App\Stores\Stores;
use Controllers\BaseController;
use Google\Service\Compute\Help;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class StoresCouponsController extends BaseController
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
        $table_name = "ViewCoupons";
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
                    return "Select COUNT(*) total From {$table_name} t Where t.store_id = ? {$search_clause}";
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
                                                 
                                            Where t.store_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $store_id
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
        $table_name = "ViewCoupons";

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
        $store_id = $ses_data['id'];

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
                    return "Select COUNT(*) total From {$table_name} t Where t.store_id = ? {$search_clause}";
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
                                                 
                                            Where t.store_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $store_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
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
    public function PaginateRecordsPrimeReact($request, $response, $args) {

        //
        $table_name = "ViewCoupons";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];
        //echo " $store_id "; exit;

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
                    return "Select COUNT(*) total From {$table_name} t Where t.store_id = ? {$search_clause}";
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
                                                 
                                            Where t.store_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $store_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
                    //
                    if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['img_ext'])){
                        $row['biz_logo'] = $biz_logo;
                        unset($row['img_ext']);
                    }

                }
            ]
        );


        //dd($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function PaginatePublicScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewCoupons";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $store_id = $args['store_id'];

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
                    return "Select COUNT(*) total From {$table_name} t Where t.store_id = ? {$search_clause}";
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
                                                 
                                            Where t.store_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $store_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
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
    public function GetListActive($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $store_id = $ses_data['id'];

        //
        $results = Query::Multiple("Select Top 10 t.* From ViewCoupons t Where t.store_id = ? And t.active = 1 /*And t.fecha_hora_fin >= GETDATE()*/ ", [
            $store_id
        ], function(&$row){
            //
            if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['img_ext'])){
                $row['biz_logo'] = $biz_logo;
                unset($row['img_ext']);
            }
        });
        //dd($results); exit;

        //
        return $response->withJson($results, 200);
    }




    //
    public function GetPublicListActive($request, $response, $args) {


        //
        $store_id = $args['store_id'];

        //
        $results = Query::Multiple("Select Top 10 t.* From ViewCoupons t Where t.store_id = ? And t.active = 1 /*And t.fecha_hora_fin >= GETDATE()*/ ", [
            $store_id
        ], function(&$row){
            //
            if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['img_ext'])){
                $row['biz_logo'] = $biz_logo;
                unset($row['img_ext']);
            }
        });
        //dd($results); exit;

        //
        return $response->withJson($results, 200);
    }







    //
    public function GetAllAvailable($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];


        //
        $results = Coupons::GetAllAvailable($store_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }










    //
    public function GetRecordById($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];


        //
        $coupon_id = $args['id'];
        //
        $results = Query::Single("Select * from coupons Where store_id = ? And id = ?", [$store_id, $coupon_id]);

        //
        return $response->withJson($results, 200);
    }





    //
    public function GetRecordByCode($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];


        //
        $results = [];


        //
        $coupon_res = self::getCouponIdAndCode($args['coupon_code']);
        $customer_coupon_id = $coupon_res['id'];
        $coupon_code = $coupon_res['coupon_code'];
        //echo "$store_id $customer_coupon_id $coupon_code"; exit;


        //
        $customer_coupon_res = Query::Single("
                Select 
                       
                    t.*,
                    ts.coupon_code,
                    ts.description,
                    ( tc.name + ' ' + tc.last_name ) as customer_name
                
                    From customers_coupons t
                        
                        Left Join coupons ts On ts.id = t.coupon_id
                        Left Join customers tc On tc.id = t.customer_id
                                               
                            Where t.store_id = ? 
                            And t.id = ?
                            And ts.coupon_code = ?
        ", [
            $store_id,
            $customer_coupon_id,
            $coupon_code
        ]);
        //dd($customer_coupon_res); exit;
        if ( isset($customer_coupon_res['error']) && $customer_coupon_res['error'] ){
            $results['error'] = $customer_coupon_res['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($customer_coupon_res['id']) && $customer_coupon_res['id']) ){
            $results['error'] = "No se encontro el cupon del cliente";
            return $response->withJson($results, 200);
        }

        //
        return $response->withJson($customer_coupon_res, 200);
    }


    //
    public function PostRedimirByCode($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];



        //
        $coupon_code = Helper::safeVar($request->getParsedBody(), 'coupon_code');


        //
        $coupon_res = self::getCouponIdAndCode($coupon_code);
        $customer_coupon_id = $coupon_res['id'];
        $coupon_code = $coupon_res['coupon_code'];
        //echo "$store_id $customer_coupon_id $coupon_code"; exit;



        //
        $results = array();




        //
        $store_res = Query::Single("Select t.* From ViewStores t
                Where t.id = ?
        ", [
            $store_id
        ]);
        //dd($store_res); exit;
        if ( isset($store_res['error']) && $store_res['error'] ){
            $results['error'] = $store_res['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($store_res['id']) && $store_res['id']) ){
            $results['error'] = "No se encontro la tienda";
            return $response->withJson($results, 200);
        }
        if ( !(isset($store_res['has_valid_subs']) && $store_res['has_valid_subs']) ){
            $results['error'] = "No cuentas con una suscripcion valida, actualizala para continuar";
            return $response->withJson($results, 200);
        }


        //
        $customer_coupon_res = Query::Single("Select t.* From ViewCustomerCoupons t
            Where t.store_id = ? 
            And t.id = ?
            And t.coupon_code = ?
        ", [
            $store_id,
            $customer_coupon_id,
            $coupon_code
        ]);
        //dd($customer_coupon_res); exit;
        if ( isset($customer_coupon_res['error']) && $customer_coupon_res['error'] ){
            $results['error'] = $customer_coupon_res['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($customer_coupon_res['id']) && $customer_coupon_res['id']) ){
            $results['error'] = "No se encontro el cupon";
            return $response->withJson($results, 200);
        }
        if ( isset($customer_coupon_res['datetime_reedemed']) && $customer_coupon_res['datetime_reedemed'] ){
            $results['error'] = "Cupon ya ha sido canjeado anteriormente";
            return $response->withJson($results, 200);
        }
        if ( isset($customer_coupon_res['is_finalized']) && $customer_coupon_res['is_finalized'] ){
            $results['error'] = "No se pudo redimir el cupon, los canjes han finalizado";
            return $response->withJson($results, 200);
        }




        /*
         * SI AUN NO HA SIDO REDIMIDO LO HACEMOS REEDEM
         * */
        if ( !(isset($customer_coupon_res['datetime_reedemed']) && $customer_coupon_res['datetime_reedemed']) ){
            //
            $update_results = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                   Update 
                    customers_coupons
                  
                  Set 
                    --
                    datetime_reedemed = GETDATE()
                    
                    
                    Where store_id = ?
                    And id = ?

                  ;SELECT @@ROWCOUNT
                ",
                "params" => [
                    $store_id,
                    $customer_coupon_id
                ],
                "parse" => function($updated_rows, &$query_results){
                    $query_results['affected_rows'] = $updated_rows;
                }
            ]);
            //var_dump($update_results); exit;
        }



        //
        $customer_coupon_res = Query::Single("
                Select 
                    t.*
                    From ViewCustomerCoupons t 
                        Where t.store_id = ? 
                        And t.id = ?
        ", [
            $store_id, $customer_coupon_id
        ]);
        //
        if ($customer_coupon_res && $customer_coupon_res['img_ext']){
            //
            $biz_logo = Stores::getStoreLogo($customer_coupon_res['store_id'], $customer_coupon_res['img_ext']);
            if ( $biz_logo ){
                $customer_coupon_res['biz_logo'] = $biz_logo;
            }
            unset($customer_coupon_res['img_ext']);
        }



        //
        return $response->withJson($customer_coupon_res, 200);
    }




    //
    public function PostRedimirByCustomerCouponId($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];






        //
        $coupon_id = Helper::safeVar($request->getParsedBody(), 'coupon_id');
        $customer_coupon_id = Helper::safeVar($request->getParsedBody(), 'id');
        //echo "$coupon_id $customer_coupon_id"; exit;



        //
        $results = array();




        //
        $store_res = Query::Single("Select t.* From ViewStores t
                Where t.id = ?
        ", [
            $store_id
        ]);
        //dd($store_res); exit;
        if ( isset($store_res['error']) && $store_res['error'] ){
            $results['error'] = $store_res['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($store_res['id']) && $store_res['id']) ){
            $results['error'] = "No se encontro la tienda";
            return $response->withJson($results, 200);
        }
        if ( !(isset($store_res['has_valid_subs']) && $store_res['has_valid_subs']) ){
            $results['error'] = "No cuentas con una suscripcion valida, actualizala para continuar";
            return $response->withJson($results, 200);
        }


        //
        $customer_coupon_res = Query::Single("Select t.* From ViewCustomerCoupons t
            Where t.store_id = ? 
            And t.coupon_id = ?
            And t.id = ?
        ", [
            $store_id,
            $coupon_id,
            $customer_coupon_id
        ]);
        //dd($customer_coupon_res); exit;
        if ( isset($customer_coupon_res['error']) && $customer_coupon_res['error'] ){
            $results['error'] = $customer_coupon_res['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($customer_coupon_res['id']) && $customer_coupon_res['id']) ){
            $results['error'] = "No se encontro el cupon";
            return $response->withJson($results, 200);
        }
        if ( isset($customer_coupon_res['datetime_reedemed']) && $customer_coupon_res['datetime_reedemed'] ){
            $results['error'] = "Cupon ya ha sido canjeado anteriormente";
            return $response->withJson($results, 200);
        }
        if ( isset($customer_coupon_res['is_finalized']) && $customer_coupon_res['is_finalized'] ){
            $results['error'] = "No se pudo redimir el cupon, los canjes han finalizado";
            return $response->withJson($results, 200);
        }




        /*
         * SI AUN NO HA SIDO REDIMIDO LO HACEMOS REEDEM
         * */
        if ( !(isset($customer_coupon_res['datetime_reedemed']) && $customer_coupon_res['datetime_reedemed']) ){
            //
            $update_results = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                   Update 
                    customers_coupons
                  
                  Set 
                    --
                    datetime_reedemed = GETDATE()
                    
                    
                    Where store_id = ?
                    And id = ?

                  ;SELECT @@ROWCOUNT
                ",
                "params" => [
                    $store_id,
                    $customer_coupon_id
                ],
                "parse" => function($updated_rows, &$query_results){
                    $query_results['affected_rows'] = $updated_rows;
                }
            ]);
            //var_dump($update_results); exit;
        }



        //
        $customer_coupon_res = Query::Single("
                Select 
                    t.*
                    From ViewCustomerCoupons t 
                        Where t.store_id = ? 
                        And t.id = ?
        ", [
            $store_id, $customer_coupon_id
        ]);



        //
        if ($customer_coupon_res && $customer_coupon_res['img_ext']){
            //
            $biz_logo = Stores::getStoreLogo($customer_coupon_res['store_id'], $customer_coupon_res['img_ext']);
            if ( $biz_logo ){
                $customer_coupon_res['biz_logo'] = $biz_logo;
            }
            unset($customer_coupon_res['img_ext']);
        }


        //
        return $response->withJson($customer_coupon_res, 200);
    }





    //
    public static function getCouponIdAndCode($coupon_code_full){
        //
        $arr_coupon_code = explode("-", $coupon_code_full);
        //
        $results = [];
        $results['id'] = null;
        $results['coupon_code'] = null;
        //
        if (isset($arr_coupon_code[0]) && $arr_coupon_code[0]){
            $results['id'] = $arr_coupon_code[0];
        }
        if (isset($arr_coupon_code[1]) && $arr_coupon_code[1]){
            $results['coupon_code'] = $arr_coupon_code[1];
        }
        return $results;
    }




    //
    public function PostAddCoupon($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];
        //
        $results = array();




        //
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $discount_type = Helper::safeVar($request->getParsedBody(), 'discount_type');
        $valor = Helper::safeVar($request->getParsedBody(), 'valor');
        $tipo_moneda = strtolower(Helper::safeVar($request->getParsedBody(), 'tipo_moneda'));
        //
        $coupon_type = Helper::safeVar($request->getParsedBody(), 'coupon_type');
        $fecha_hora_inicio = Helper::safeVar($request->getParsedBody(), 'fecha_hora_inicio');
        $fecha_hora_fin = Helper::safeVar($request->getParsedBody(), 'fecha_hora_fin');
        //
        $qty = Helper::safeVar($request->getParsedBody(), 'qty');
        $active = Helper::safeVar($request->getParsedBody(), 'active');
        $active = ( $active && ($active === "true" || $active === "1" )) ? 1 : 0;


        // mejorar el active


        //
        $v = new ValidatorHelper();

        //
        if ( !$v->validateString([10, 80], $description) ){
            $results['error'] = "proporciona la description"; return $response->withJson($results, 200);
        }

        //
        if ( $discount_type == "porcentaje" ){
            //
            if ( !($valor > 0 && $valor <= 100) ){
                $results['error'] = "proporciona un descuento valido del 1% al 100%"; return $response->withJson($results, 200);
            }
            $valor = (int)$valor;
            $tipo_moneda = null;
        }
        //
        else if ( $discount_type == "precio" ){
            //
            if ( !($valor > 0 && $valor <= 999999) ){
                $results['error'] = "proporciona un descuento valido del 1% al 100%"; return $response->withJson($results, 200);
            }
            //
            if ( !($tipo_moneda === "mxn" || $tipo_moneda === "usd") ){
                $results['error'] = "proporciona el tipo de moneda"; return $response->withJson($results, 200);
            }
        }
        //
        else {
            $results['error'] = "proporciona un tipo de descuento valido"; return $response->withJson($results, 200);
        }



        //
        $is_percentage = ($discount_type == "porcentaje") ? 1 : 0;
        //
        if ( !( $valor && $valor > 0 ) ){
            $results['error'] = "proporciona el valor";
            return $response->withJson($results, 200);
        }




        //
        if ( $coupon_type && (int)$coupon_type === 1 ){

            // "Y-m-d H:i"
            if (!$fecha_hora_inicio = Helper::is_valid_date($fecha_hora_inicio, "Y-m-d H:i")){
                $results['error'] = "proporciona la fecha de inicio";
                return $response->withJson($results, 200);
            }
            //
            if (!$fecha_hora_fin = Helper::is_valid_date($fecha_hora_fin, "Y-m-d H:i")){
                $results['error'] = "proporciona la fecha de finalizacion";
                return $response->withJson($results, 200);
            }
            if ( $fecha_hora_fin <= $fecha_hora_inicio ){
                $results['error'] = "la feche de finalizacion no puede ser menor o igual a la de inicio";
                return $response->withJson($results, 200);
            }
            //dd($fecha_hora_inicio); dd($fecha_hora_fin); exit;
        }
        //
        else {

            //
            if ( !(is_numeric($qty) && $qty > 0) ) {
                $results['error'] = "proporciona una cantidad valida";
                return $response->withJson($results, 200);
            }
        }



        //
        $new_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_AddCoupon(?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => true,
            "params" => function() use($store_id, $description, $is_percentage, $valor, $tipo_moneda, $qty, $fecha_hora_inicio, $fecha_hora_fin, $active, &$new_record_id){
                return [
                    //
                    array($store_id, SQLSRV_PARAM_IN),
                    array($description, SQLSRV_PARAM_IN),
                    array($is_percentage, SQLSRV_PARAM_IN),
                    array($valor, SQLSRV_PARAM_IN),
                    array($tipo_moneda, SQLSRV_PARAM_IN),
                    //
                    array($qty, SQLSRV_PARAM_IN),
                    array($fecha_hora_inicio, SQLSRV_PARAM_IN),
                    array($fecha_hora_fin, SQLSRV_PARAM_IN),
                    //
                    array($active, SQLSRV_PARAM_IN),
                    array(&$new_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);

        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }
        //
        $results['id'] = $new_record_id;


        //
        return $response->withJson($results, 200);
    }










    //
    public function PostEditCoupon($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];


        //
        $coupon_id = $args['id'];
        //
        $results = array();



        //
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active');
        $active = ( $active && ($active === "true" || $active === "1" )) ? 1 : 0;
        //echo $active; exit;




        //
        $v = new ValidatorHelper();

        //
        if ( !$v->validateString([12, 90], $description) ){
            $results['error'] = "proporciona la description"; return $response->withJson($results, 200);
        }
        //echo " $store_id, $coupon_id "; exit;



        //
        $cant_rows_updated = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_EditCoupon(?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => false,
            "params" => function() use($store_id, $coupon_id, $description, $active, &$cant_rows_updated){
                return [
                    //
                    array($store_id, SQLSRV_PARAM_IN),
                    array($coupon_id, SQLSRV_PARAM_IN),
                    //
                    array($description, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array(&$cant_rows_updated, SQLSRV_PARAM_OUT),
                ];
            }
        ]);

        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }
        //
        $results['id'] = $coupon_id;
        $results['rows_updated'] = $cant_rows_updated;


        //
        return $response->withJson($results, 200);
    }











    //
    public function DeleteRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
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
            "stmt" => "Delete FROM coupons Where id = ?;SELECT @@ROWCOUNT",
            "debug" => false,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "FK_customers_coupons_coupons" => "No se pueden eliminar cupones que ya han sido canjeados"
            ],
            "params" => [
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
