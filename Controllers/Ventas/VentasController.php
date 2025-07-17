<?php
namespace Controllers\Ventas;

//
use App\Accounts\Accounts;
use Helpers\CodigoQR;
use App\App;
use App\Apps\Apps;
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;
use App\Maquetas\MaquetasDocumentos;
use App\Salidas\Salidas;
use App\Stores\Stores;
use App\Invoices\Invoices;
use App\Config\ConfigSquare\ConfigSquare;
use App\Coupons\CouponsCoupons;
use App\Customers\Customers;
use App\Locations\CatPaises;
use App\Products\Products;
use App\Products\ProductsConfig;
use App\Products\Subscriptions;
use App\Tasks\Tasks;
use App\Utils\Utils;
use App\Ventas\Ventas;
use App\Ventas\VentasItems;
use App\Ventas\VentasPayments;
use App\Ventas\VentasStatus;
use Google\Service\Compute\Help;
use Helpers\Helper;
use Helpers\BuildPdf;
use Helpers\SendMail;
use Controllers\BaseController;
use Helpers\PHPMicroParser;
use Helpers\Query;
use Helpers\TwigParser;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Slim\Container;
use Slim\Views\Twig;
use Square\Models\Subscription;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Util\Util;
use Twig\Environment;
use Twig\Loader\ArrayLoader;


//
class VentasController extends BaseController
{







    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id =  $ses_data['account_id'];
        $app_id = Helper::getAppByAccountId($account_id);
        //echo $app_id; exit;

        //
        $square_config = ConfigSquare::getConfig($account_id);
        //dd($square_config); exit;

        //
        return $this->container->php_view->render($response, 'admin/sales/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "square_config" => $square_config,
            "app_id" => $app_id,
        ]);
    }





    // 
    public function ViewT4BIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id =  $ses_data['account_id'];
        $app_id = Helper::getAppByAccountId($account_id);
        //echo $app_id; exit;

        //
        $config = ConfigSquare::getConfig($account_id);
        //dd($config); exit;

        //
        return $this->container->php_view->render($response, 'admin/sales/t4b-index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "config" => $config,
            "app_id" => $app_id,
        ]);
    }




    // 
    public function ViewPlabuzIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id =  $ses_data['account_id'];
        $app_id = Helper::getAppByAccountId($account_id);
        //echo $app_id; exit;

        //
        $config = ConfigSquare::getConfig($account_id);
        //dd($config); exit;

        //
        return $this->container->php_view->render($response, 'admin/sales/t4b-index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "config" => $config,
            "app_id" => $app_id,
        ]);
    }







    //
    public static function strWhereVentasFilter($search_value, $status_id, $start_date){
        //echo "$search_value, $status_id, $filter_start_date, $filter_end_date, $filter_sale_type_id"; exit;
        //
        $search_clause = "";
        //
        if ( $status_id ){
            $search_clause .= " And t.status_id = $status_id ";
        }

        
        //
        if ( $start_date ){

            //
            $start_date_obj = \DateTime::createFromFormat("Y-m-d" ,$start_date);
            //
            $end_date_obj = clone $start_date_obj; // Clonamos la fecha de inicio
            $end_date_obj->modify('last day of this month');

            // Formateamos las fechas según tus preferencias
            $formatted_start_date = $start_date_obj->format('Y-m-d');
            $formatted_end_date = $end_date_obj->format('Y-m-d');

            //
            $search_clause .= " And (
                    (  Convert(date, t.datetime_created) >= '$formatted_start_date' And Convert(date, t.datetime_created) <= '$formatted_end_date' )    
                )";
        }
        //echo $search_clause; exit;




        //
        if ( $search_value ){
            $search_clause = " And (
                        ( t.customer_name like '%$search_value%' ) Or 
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' )  
                    )";
        }



        //echo $search_clause; exit;
        return $search_clause;
    }



    //
    public function PaginateRecords($request, $response, $args) {
        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);



        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = (int)$ses_data['account_id'];
        $app_id = (int)$ses_data['app_id'];
        $user_id = (int)$ses_data['id'];
        //echo $user_id; exit;
        //
        $tipo_producto_servicio_id = $request->getQueryParam("filter_tipo_producto_servicio_id");


        

        // Si ya transcurrieron 5 minutos libera asientos Ocupados
        $sp_res = Salidas::liberaAsientosOcupadosTemporalmente($app_id);
        //dd($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }



        function thisFilter(
            $start_date,
            $customer_id,
            $aplica_comision,
            $filter_comision_venta,
            $filter_venta,
            $aplica_comision_cbx,
            $filter_comision_cbx,
            $filter_venta_sin_cbx,
            $filter_dominio_id,
            $filter_status_venta
        ){
            //echo "$start_date, $filter_comision_venta, $filter_venta, $filter_comision_cbx, $filter_venta_sin_cbx, $filter_dominio_id, $filter_status_venta"; exit;

            //
            $search_clause = "";

            
            //
            if ( is_numeric($customer_id) ){
                $search_clause .= " And t.customer_id = {$customer_id} ";
            }
            //echo $search_clause; exit;

            //
            if ( $aplica_comision ){
                //
                if ( $filter_comision_venta == 1 ){
                    $search_clause .= " And t.aplica_comision = 1 And t.is_comision_pagada_a_cte = 1 ";
                } else if ( $filter_comision_venta == 2 ){
                    $search_clause .= " And t.aplica_comision = 1 And (t.is_comision_pagada_a_cte Is Null Or t.is_comision_pagada_a_cte = 0) ";                    
                }
                //
                if ( $filter_venta == 1 ){
                    $search_clause .= " And t.aplica_comision = 1 And t.is_venta_cobrada_a_cte = 1 ";
                } else if ( $filter_venta == 2 ){
                    $search_clause .= " And t.aplica_comision = 1 And (t.is_venta_cobrada_a_cte Is Null Or t.is_venta_cobrada_a_cte = 0) ";                    
                }
                //
                if ( !$filter_comision_venta && !$filter_venta ){
                    $search_clause .= " And t.aplica_comision = 1 ";
                }
            }
                     
            //echo $search_clause; exit;



            //
            if ( $aplica_comision_cbx ){
                //
                if ( $filter_comision_cbx == 1 ){
                    $search_clause .= " And t.aplica_comision_cbx = 1 And t.is_comision_cbx_pagada = 1 ";
                } else if ( $filter_comision_cbx == 2 ){
                    $search_clause .= " And t.aplica_comision_cbx = 1 And (t.is_comision_cbx_pagada Is Null Or t.is_comision_cbx_pagada = 0) ";
                }
                //
                if ( $filter_venta_sin_cbx == 1 ){
                    $search_clause .= " And t.aplica_comision_cbx = 1 And t.is_venta_cbx_cobrada = 1 ";
                } else if ( $filter_venta_sin_cbx == 2 ){
                    $search_clause .= " And t.aplica_comision_cbx = 1 And (t.is_venta_cbx_cobrada Is Null Or t.is_venta_cbx_cobrada = 0) ";
                }
                //
                if ( !$filter_comision_cbx && !$filter_venta_sin_cbx ){
                    $search_clause .= " And t.aplica_comision_cbx = 1 ";
                }
            }
            //echo $search_clause; exit;



            //
            if ( $filter_dominio_id ){
                $search_clause .= " And t.app_id = {$filter_dominio_id} ";
            }

            //
            if ( $filter_status_venta == 1 ){
                $search_clause .= " And t.sale_paid = 1 ";                
            } else if ( $filter_status_venta == 2 ){
                $search_clause .= " And t.a_credito = 1 ";                
            }
    
            
            //
            if ( $start_date ){    
                //
                $start_date_obj = \DateTime::createFromFormat("Y-m-d" ,$start_date);
                
                // clonamos la fecha y obtenemos la ultima del mes
                $end_date_obj = clone $start_date_obj;
                $end_date_obj->modify('last day of this month');    
                
                // Formateamos las fechas según tus preferencias
                $formatted_start_date = $start_date_obj->format('Y-m-d');
                $formatted_end_date = $end_date_obj->format('Y-m-d');
                //
                $search_clause .= " And (
                        (  Convert(date, t.datetime_created) >= '$formatted_start_date' And Convert(date, t.datetime_created) <= '$formatted_end_date' )    
                    )";
            }
            //echo $search_clause; exit;
    
    
            
            return $search_clause;
        }


        //
        $customer_id = trim($request->getQueryParam("cid"));
        $aplica_comision = trim($request->getQueryParam("acom"));
        $aplica_comision_cbx = trim($request->getQueryParam("acomcbx"));
        //
        $start_date = trim($request->getQueryParam("sd"));
        $filter_comision_venta = trim($request->getQueryParam("a"));
        $filter_venta = trim($request->getQueryParam("b"));
        $filter_comision_cbx = trim($request->getQueryParam("c"));
        $filter_venta_sin_cbx = trim($request->getQueryParam("d"));
        $filter_dominio_id = trim($request->getQueryParam("e"));
        $filter_status_venta = trim($request->getQueryParam("f"));
        //echo "$start_date, $filter_comision_venta, $filter_venta, $filter_comision_cbx, $filter_venta_sin_cbx, $filter_dominio_id, $filter_status_venta"; exit;


        /*
            SI ES MISSIONEXPRESS PERMITIMOS FILTRAR DE TODOS 
        */
        //$filter_dominio_id = ( $app_id === APP_ID_MISSIONEXPRESS ) ? $filter_dominio_id : $app_id;
        

        //
        $search_filter = thisFilter(
            $start_date,
            $customer_id,
            $aplica_comision,
            $filter_comision_venta,
            $filter_venta,
            $aplica_comision_cbx,
            $filter_comision_cbx,
            $filter_venta_sin_cbx,
            $filter_dominio_id,
            $filter_status_venta,
        );
        //echo $search_filter; exit;

        

        //
        $totals_res = Query::single("
            --
            Select 
                --
                COUNT(*) count_records,
                SUM(COALESCE(grand_total, 0)) sum_grand_total,
                SUM(COALESCE(comisiones, 0)) sum_comisiones,
                SUM(COALESCE(new_total, 0)) sum_new_total,
                 SUM(COALESCE(comisiones_cbx, 0)) sum_comisiones_cbx,
                 SUM(COALESCE(new_total, 0)) sum_new_total2
            --
            From v_sales t Where 1 = 1 {$search_filter}", []);
        //dd($totals_res); exit;

        $count_records = 0;
        $sum_grand_total = 0;
        $sum_comision_monto = 0;        
        $sum_comision_cbx = 0;
        $sum_new_total = 0;
        //
        $count_records = 0;
        if ($totals_res && isset($totals_res['count_records'])){
            $count_records = $totals_res['count_records'];
            $sum_grand_total = $totals_res['sum_grand_total'];
            $sum_comisiones = $totals_res['sum_comisiones'];            
            $sum_new_total = $totals_res['sum_new_total'];
            $sum_comisiones_cbx = $totals_res['sum_comisiones_cbx'];
            $sum_new_total2 = $totals_res['sum_new_total2'];
        }
        //echo " $count_records, $sum_grand_total, $sum_comision_monto, $sum_comision_cbx, $sum_new_total"; exit;


        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            $search_filter,
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "v_sales",

                "count_total" => $count_records,

                // AQUI NO SE UTILIZA EL "count_stmt" SINO QUE SE PASA DIRECTAMENTE EL TOTAL VALUE EN "count_total"
                /*
                "count_stmt" => function($table_name, $search_clause){
                    //echo $search_clause; exit;
                    return "Select COUNT(*) total From {$table_name} t Where 1 = 1 {$search_clause}";
                },
                */

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
                    //echo $search_clause; exit;
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
                                        (Select Count(*) From salidas_ocupacion Where sale_id = t.id ) as cant_salidas_ocupacion,
                                        (Select Count(*) From temp_salidas_ocupacion Where sale_id = t.id ) as cant_temp_salidas_ocupacion
                                        
                                        From {$table_name} t
                                                
                                            Where 1 = 1
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(

                ),

                //
                "parseRows" => function(&$row) use($tipo_producto_servicio_id){
                    //dd($row); exit;

                    

                    // SI HA SIDO ACEPTADA (CUANDO ES CREDITO) O HA SIDO PAGADA (CUANDO ES PAGO)
                    if ( $row['seller_accepted'] || $row['sale_paid'] ){                        
                        $row['salidas_ocupacion'] = Ventas::GetSalidasOcupacion($row['id'], $row['sale_code']);
                    } else {
                        $row['salidas_ocupacion'] = Ventas::GetTempSalidasOcupacion($row['id']);
                    }
                    

                    //
                    $row['sale_payments'] = VentasPayments::GetAll($row['id']);

                }

            ]
        );

        
        //
        $results['sum_grand_total'] = $sum_grand_total;
        $results['sum_comisiones'] = $sum_comisiones;        
        $results['sum_new_total'] = $sum_new_total;
        $results['sum_comisiones_cbx'] = $sum_comisiones_cbx;
        $results['sum_new_total2'] = $sum_new_total2;
        //
        //dd($results); exit;
        return $response->withJson($results, 200);
    }




    


    


    //
    public function PaginatePublicSaleOcupacion($request, $response, $args) {
        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);
        //var_dump ($order); exit;



        // 
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['id'];;
        
        


        //
        $sale_code = $args['sale_code'];
        //echo $sale_code; exit;
        $sale_res = Query::single("select * from sales Where sale_code = ?", [$sale_code]);
        
        //
        $sale_id = $sale_res['id'];


        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            null,
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "temp_salidas_ocupacion",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //echo $search_clause; exit;
                    return "Select COUNT(*) total From {$table_name} t Where t.sale_id = ? ";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
                    //echo $search_clause; exit;
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
                                                
                                             Where t.sale_id = ?
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $sale_id
                ),

                //
                "parseRows" => function(&$row) {
                    //dd($row); exit;
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








    



    public function GetTempOcupacionBySaleId($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        //$app_id = $ses_data['id'];
        //$account_id = $ses_data['account_id'];
        //$user_id = null;



        $app_id = APP_ID_MISSIONEXPRESS;

        
 
         // Si ya transcurrieron 5 minutos libera asientos Ocupados
         $sp_res = Salidas::liberaAsientosOcupadosTemporalmente($app_id);
         //dd($sp_res); exit;
         if (isset($sp_res['error']) && $sp_res['error']){
             return $response->withJson($sp_res, 200);
         }


        $temp_sale_id = $args['temp_sale_id'];
        //echo $temp_sale_id; exit;


        $results = Query::Multiple("SELECT t.*, FORMAT(t.datetime_created, 'yyyy-MM-dd HH:mm') datetime_created FROM temp_salidas_ocupacion t Where t.temp_sale_id = ?", [$temp_sale_id]);
        

        return $response->withJson($results, 200);
    }

    



    //
    public function PaginateRecordsForCustomer($request, $response, $args) {

         //
         $ses_data = $request->getAttribute("ses_data");
         //dd($ses_data); exit;

         //
         $customer_id = $ses_data['id'];
         $account_id = $ses_data['account_id'];
         $app_id = $ses_data['app_id'];
         //echo "$customer_id, $account_id, $app_id"; exit;


        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);


        $search_value = null;
        $filter_status_id = null;
        $filter_week_date = null;
        
        
        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::strWhereVentasFilter(null, null, null),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "v_sales",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //echo $search_clause; exit;
                    return "
                            Select 
                                COUNT(*) total 
                                    From {$table_name} t 
                                        WHERE customer_id = ?
                                        AND (
                                            (SELECT COUNT(*) FROM salidas_ocupacion WHERE sale_id = t.id) > 0
                                            OR
                                            (SELECT COUNT(*) FROM temp_salidas_ocupacion WHERE sale_id = t.id) > 0
                                        )
                                    {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
                    //echo $search_clause; exit;
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
                                                
                                            WHERE customer_id = ?
                                            AND (
                                                (SELECT COUNT(*) FROM salidas_ocupacion WHERE sale_id = t.id) > 0
                                                OR
                                                (SELECT COUNT(*) FROM temp_salidas_ocupacion WHERE sale_id = t.id) > 0
                                            )
                                            
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
                    

                   // SI HA SIDO ACEPTADA (CUANDO ES CREDITO) O HA SIDO PAGADA (CUANDO ES PAGO)
                   if ( $row['seller_accepted'] || $row['sale_paid'] ){                        
                        $row['salidas_ocupacion'] = Ventas::GetSalidasOcupacion($row['id'], $row['sale_code']);
                    } else {
                        $row['salidas_ocupacion'] = Ventas::GetTempSalidasOcupacion($row['id']);
                    }
                    

                    //
                    $row['sale_payments'] = VentasPayments::GetAll($row['id']);

                    

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }






    //
    public function GetCustomerSale($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
    
        //
        $customer_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //echo "$customer_id, $account_id, $app_id"; exit;


        $sale_id = $args['id'];
        //echo $sale_id; exit;

        //
        $results = Ventas::GetRecord($sale_id);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }



    
    //
    public function GetPublicCustomerSaleByCode($request, $response, $args) {

        //
        $app_data = $request->getAttribute("app");
        //dd($app_data); exit;
    
        //
        $app_id = $app_data['id'];
        $account_id = $app_data['account_id']; 
        //echo "$account_id, $app_id"; exit;

        //
        $results = [];

        //
        $sale_code = trim($args['sale_code']);
        //echo $sale_code; exit;

        $sale_res = Query::single("select * from v_sales Where sale_code = ?", [$sale_code], 
            function(&$row){
                

                 // SI HA SIDO ACEPTADA (CUANDO ES CREDITO) O HA SIDO PAGADA (CUANDO ES PAGO)
                 if ( $row['seller_accepted'] || $row['sale_paid'] ){                        
                    $row['salidas_ocupacion'] = Ventas::GetSalidasOcupacion($row['id']);
                } else {
                    $row['salidas_ocupacion'] = Ventas::GetTempSalidasOcupacion($row['id']);
                }
                

                //
                $row['sale_payments'] = VentasPayments::GetAll($row['id']);

        });
        //dd($sale_res); exit; 
        //
        if (isset($sale_res['error']) && $sale_res['error']){
            $results['error'] = $sale_res['error'];
            return $response->withJson($results, 200);
        } 
        if ( !(isset($sale_res['id']) && $sale_res['id']) ){
            $results['error'] = "venta inexistente";
            return $response->withJson($results, 200);
        }
        
        //
        return $response->withJson($sale_res, 200);
    }




    
    
    //
    public function GetSaleInfo($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("app");
        //var_dump($ses_data); exit;


        //
        $results = Ventas::GetRecord($args['id']);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }




    //
    public function GetPreviewPhitPhuel($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $results = Ventas::GetRecord($args['id']);
        //var_dump($results); exit;


        //$html_str = file_get_contents(PATH_VIEWS.DS.'twig_docs/invoice-print.html');
        //echo $html_str; exit;
        //return TwigParser::render($html_str, $results);
        return $this->container->twig_view->render($response, 'twig_docs/invoice-phitphuel.html', $results);
    }





    //
    public function GetPreviewConexTubi($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $results = Ventas::GetRecord($args['id']);
        //var_dump($results); exit;


        //$html_str = file_get_contents(PATH_VIEWS.DS.'twig_docs/invoice-print.html');
        //echo $html_str; exit;
        //return TwigParser::render($html_str, $results);
        return $this->container->twig_view->render($response, 'twig_docs/invoice-conextubi.html', $results);
    }














    //
    public static function AddPayPalOrder(){
        /*
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "reference_id" => "test_ref_id1",
                    "amount" => [
                        "value" => "100.00",
                        "currency_code" => "USD"
                    ]
                ]],
                "application_context" => [
                    "cancel_url" => "https://example.com/cancel",
                    "return_url" => "https://example.com/return"
                ]
            ];

            try {
                // Call API with your client and get a response for your call
                $response = $client->execute($request);

                // If call returns body in response, you can get the deserialized version from the result attribute of the response
                var_dump($response);
            }catch (HttpException $ex) {
                echo $ex->statusCode;
                var_dump($ex->getMessage());
            }
            exit;
            */
    }






    public static function getMiles($el_val){
        return $el_val*0.000621371192;
    }






    //
    public function GetDownloadReport($request, $response, $args){

        //
        $results = [];



        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];
        $sucursal_city_id = $ses_data['sucursal_city_id'];




        //
        $str_start_date = $request->getQueryParam("filter_week_date");
        $start_date_obj = \DateTime::createFromFormat("Y-m-d" ,$str_start_date);
        //
        $end_date_obj = clone $start_date_obj;
        $end_date_obj = $end_date_obj->modify("+6 days");
        //var_dump($start_date_obj->format("Y-m-d")); var_dump($end_date_obj->format("Y-m-d")); exit;




        //
        $filename = "PhitPhuel-Orders-Report-From-" . $start_date_obj->format("M-d-Y") . "-To-" . $end_date_obj->format("M-d-Y").".xlsx";
        //echo $filename; exit;
        $xlsx_file_path = PATH_PUBLIC.DS."files".DS."reports".DS.$filename;
        $download_file_path = "/files/reports/".$filename;
        //echo $xlsx_file_path; exit;
        //echo $download_file_path; exit;
        //
        $current_datetime = new \DateTime();



        //echo ini_get('upload_tmp_dir'); exit;
        //echo sys_get_temp_dir(); exit;







        /*------------------------------------------*/

        //
        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->setActiveSheetIndex(0);


        //
        $sheet = $objPHPExcel->getActiveSheet();


        ini_set("memory_limit","512M");
        ini_set("max_execution_time","300");
        //echo ini_get('max_execution_time').PHP_EOL; exit;




        // Start the clock time in seconds
        $start_time = microtime(true);



        //
        $str_where_filter = self::strWhereVentasFilter(
            trim($request->getQueryParam("search")),
            $request->getQueryParam("filter_status_id"),
            $str_start_date,
            $request->getQueryParam("filter_pickup_delivery"),
            $request->getQueryParam("filter_product_type")
        );
        //echo $str_where_filter; exit;



        //
        $arr_sales = Query::Multiple("
				SELECT
                    
                    t.*,
                    t.*,
                    (Select Count(*) From sales_status Where sale_id = t.id ) as cant_status,
                    (Select Count(*) From sales_msgs Where sale_id = t.id ) as cant_msgs
                    
                        FROM v_sales t
                
                           Where 1 = 1
                           
                           {$str_where_filter}
				
                        order by t.id Desc
			", [], function(&$row){

            //
            $row['items'] = VentasItems::GetAllPhitPhuel($row['id']);
            //dd($row['items']); exit;

        });
        //dd($arr_sales); exit;







        $sheet->SetCellValue('B2', "Orders Report");
        $sheet->SetCellValue('B3', "Week Date: " . $start_date_obj->format("M-d-Y") . " TO " . $end_date_obj->format("M-d-Y"));
        //
        $sheet->getStyle('B2:B3')->getFont()->setBold(true);
        $sheet->getStyle('B2:B3')->getFont()->setSize(16);



        $sheet->SetCellValue("B4", "Id");
        $sheet->SetCellValue("C4", "Customer Name");
        $sheet->SetCellValue("D4", "Email");
        //
        $sheet->SetCellValue("E4", "Type");
        $sheet->SetCellValue("F4", "Deliver At");
        $sheet->SetCellValue("G4", "Pickup At");
        $sheet->SetCellValue("H4", "Kitchen");
        $sheet->SetCellValue("I4", "DateTime Created");
        $sheet->SetCellValue("J4", "Order Status");


        //
        $sheet->SetCellValue("K4", "Follow Up Id");
        $sheet->SetCellValue("L4", "Code");
        $sheet->SetCellValue("M4", "Product");
        $sheet->SetCellValue("N4", "Size");
        $sheet->SetCellValue("O4", "Type");
        $sheet->SetCellValue("P4", "Delivery/Pickup Date");
        $sheet->SetCellValue("Q4", "Subscription End Date");
        $sheet->SetCellValue("R4", "Item Amt");
        $sheet->SetCellValue("S4", "Item Qty");
        $sheet->SetCellValue("T4", "Item Disc %");
        $sheet->SetCellValue("U4", "Item Disc Amt");
        $sheet->SetCellValue("V4", "Item Total");


        //
        $sheet->SetCellValue("W4", "Order Sub-Total");
        $sheet->SetCellValue("X4", "Order Disc %");
        $sheet->SetCellValue("Y4", "Order Disc Amt");
        $sheet->SetCellValue("Z4", "Tax %");
        $sheet->SetCellValue("AA4", "Tax Amount");
        $sheet->SetCellValue("AB4", "Order Total");


        //
        $sheet->getStyle("B4:AB4")->getFont()->setBold(true);

        //
        $sheet->getStyle("B4:AB4")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);
        //
        $sheet->getStyle("B4:AB4")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => "fffed0",
                ]
            ]
        ]);


        //
        $row_number = 5;


        //
        foreach($arr_sales as $idx => $item ){
            //dd($item); exit;

            //
            $sheet->SetCellValue("B".$row_number, $item['id']);
            $sheet->SetCellValue("C".$row_number, $item['customer_name']);
            $sheet->SetCellValue("D".$row_number, $item['email']);
            //
            $sheet->SetCellValue("F".$row_number, $item['address']);
            $sheet->SetCellValue("I".$row_number, $item['datetime_created']->format("M d Y"));



            //
            $str_status_additional_notes = "";
            /*
             * IF CANCELLED
             * */
            $status_id_cancelled = 5;
            //
            if ( $item['status_id'] === $status_id_cancelled ){
                //
                $str_status_additional_notes = " - " . $item['status_notes'];
                //
                $sheet->getStyle("B".$row_number.":AB".$row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "ffcbcb",
                        ]
                    ]
                ]);
            }
            $sheet->SetCellValue("J".$row_number, $item['status_title'] . $str_status_additional_notes);



            //
            // AQUI VAN LOS DE LA ITERACION
            //
            $sheet->SetCellValue("W".$row_number, $item['sub_total']);
            $sheet->SetCellValue("X".$row_number, $item['discount_percent']);
            $sheet->SetCellValue("Y".$row_number, $item['discount_amount']);
            $sheet->SetCellValue("Z".$row_number, $item['tax_percent']);
            $sheet->SetCellValue("AA".$row_number, $item['tax_amount']);
            $sheet->SetCellValue("AB".$row_number, $item['grand_total']);

            //
            $row_number++;



            //
            foreach($item['items'] as $idx2 => $sale_item ){
                //dd($sale_item); exit;


                //
                $follow_up_id = $sale_item['id'];

                //
                $str_end_datetime = "";
                $str_qty_meals = "";
                //
                if ( $sale_item['tipo']==="subscriptions"){
                    $str_end_datetime = $sale_item['end_datetime']->format("M d Y");
                }
                //
                else if ( $sale_item['tipo']==="meal_plans" ){
                    $str_qty_meals = " - " . $sale_item['meals_qty'] . " Meals";
                }

                //
                $sheet->SetCellValue("K".$row_number, $follow_up_id);
                $sheet->SetCellValue("L".$row_number, $sale_item['product_code']);
                $sheet->SetCellValue("M".$row_number, $sale_item['item_info'] . $str_qty_meals);
                $sheet->SetCellValue("N".$row_number, $sale_item['meal_size']);
                $sheet->SetCellValue("O".$row_number, $sale_item['plan_type']);
                $sheet->SetCellValue("P".$row_number, $sale_item['ready_o_start_datetime']->format("M d Y"));
                $sheet->SetCellValue("Q".$row_number, $str_end_datetime);
                $sheet->SetCellValue("R".$row_number, $sale_item['price']);
                $sheet->SetCellValue("S".$row_number, $sale_item['qty']);
                $sheet->SetCellValue("T".$row_number, $sale_item['discount_percent']);
                $sheet->SetCellValue("U".$row_number, $sale_item['discount_amount']);
                $sheet->SetCellValue("V".$row_number, $sale_item['final_price']);

                //
                $sheet->getStyle("K".$row_number.":V".$row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "EBF1DE",
                        ]
                    ]
                ]);
                //
                $row_number++;




                //
                if ($sale_item['tipo']==="subscriptions"){
                    //
                    foreach($sale_item['weeks_plans'] as $idx3 => $week_plans ){
                        //dd($week_plans); exit;

                        //
                        $week_plan_qty_meals = " - " . $week_plans['meals_qty'] . " Meals";
                        //var_dump($item); exit;
                        $sheet->SetCellValue("K".$row_number, $follow_up_id);
                        $sheet->SetCellValue("L".$row_number, $week_plans['product_code']);
                        $sheet->SetCellValue("M".$row_number, "Week#: " . $week_plans['week_number'] . " - " . $week_plans['item_info'] . $week_plan_qty_meals);
                        $sheet->SetCellValue("N".$row_number, $week_plans['meal_size']);
                        $sheet->SetCellValue("O".$row_number, $week_plans['plan_type']);
                        $sheet->SetCellValue("P".$row_number, $week_plans['ready_o_start_datetime']->format("M d Y"));
                        $sheet->SetCellValue("S".$row_number, $week_plans['qty']);
                        //
                        $sheet->getStyle("K".$row_number.":S".$row_number)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => "DAEEF3",
                                ]
                            ]
                        ]);
                        //
                        $row_number++;


                        //
                        foreach($week_plans['meal_plans'] as $idx4 => $meal_plans ){
                            //dd($meal_plans); exit;
                            $sheet->SetCellValue("K".$row_number, $follow_up_id);
                            $sheet->SetCellValue("L".$row_number, $meal_plans['product_code']);
                            $sheet->SetCellValue("M".$row_number, $meal_plans['item_info']);
                            $sheet->SetCellValue("N".$row_number, $meal_plans['meal_size']);
                            //$sheet->SetCellValue("N".$row_number, $meal_plans['plan_type']);
                            $sheet->SetCellValue("P".$row_number, $meal_plans['ready_o_start_datetime']->format("M d Y"));
                            $sheet->SetCellValue("S".$row_number, $meal_plans['qty']);
                            //
                            $row_number++;
                        }
                    }
                }

                //
                else if ($sale_item['tipo']==="meal_plans"){
                    //
                    //dd($sale_item['meal_plans']); exit;
                    foreach($sale_item['meal_plans'] as $idx3 => $meal_plans ){
                        //dd($meal_plans); exit;
                        //var_dump($item); exit;
                        $sheet->SetCellValue("K".$row_number, $follow_up_id);
                        $sheet->SetCellValue("L".$row_number, $meal_plans['product_code']);
                        $sheet->SetCellValue("M".$row_number, $meal_plans['item_info']);
                        $sheet->SetCellValue("N".$row_number, $meal_plans['meal_size']);
                        //$sheet->SetCellValue("N".$row_number, $meal_plans['plan_type']);
                        $sheet->SetCellValue("P".$row_number, $meal_plans['ready_o_start_datetime']->format("M d Y"));
                        $sheet->SetCellValue("S".$row_number, $meal_plans['qty']);

                        //
                        $row_number++;
                    }
                }



            }

        }



        /*
         * AUTO WIDTH FOR ALL COLUMNS
         * */
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        //
        $sheet->getColumnDimension("A")->setAutoSize(false);
        $sheet->getColumnDimension("B")->setAutoSize(false);
        $sheet->getColumnDimension("A")->setWidth(2);
        $sheet->getColumnDimension("B")->setWidth(10);




        //$end_time = microtime(true);
        //$execution_time = ($end_time - $start_time);
        //echo count($arr_invoices) . " registros";
        //echo " It takes ".$execution_time." seconds to execute the script <br /> ";




        //$objPHPExcel->setActiveSheetIndexByName('MySheet2');
        $writer = new Xlsx($objPHPExcel);

        //echo $xlsx_file_path; exit;
        $writer->save($xlsx_file_path);







        //
        return $response->withJson([
            "download_file_path" => $download_file_path
        ], 200);
    }















    //
    public function PostUpdateBulkStatus($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];

        //
        $results = array();




        //
        $status_id = Helper::safeVar($request->getParsedBody(), 'status_id');
        $status_notes = Helper::safeVar($request->getParsedBody(), 'status_notes');
        $notify_customer = Helper::safeVar($request->getParsedBody(), 'notify_customer');
        $msg_id = Helper::safeVar($request->getParsedBody(), 'msg_id');
        $msg = Helper::safeVar($request->getParsedBody(), 'msg');
        //
        $str_start_date = Helper::safeVar($request->getParsedBody(), 'start_date');
        //echo $start_date; exit;
        //
        $send_to = Helper::safeVar($request->getParsedBody(), 'send_to');
        $selected_ids = Helper::safeVar($request->getParsedBody(), 'arr_selected_ids');


        //
        if ( !is_numeric($status_id) ){
            $results['error'] = "proporciona el status";
            return $response->withJson($results, 200);
        }
        //
        $status_notes = ($status_notes) ? $status_notes : null;



        //
        if ( $send_to === "selected" && !$selected_ids ){
            $results['error'] = "Selected items to update";
            return $response->withJson($results, 200);
        }


        //
        $start_date_obj = \DateTime::createFromFormat("Y-m-d" ,$str_start_date);
        //
        $end_date_obj = clone $start_date_obj;
        $end_date_obj = $end_date_obj->modify("+6 days");
        //var_dump($start_date_obj->format("Y-m-d")); var_dump($end_date_obj->format("Y-m-d")); exit;





        //
        $arr_sales = Ventas::GetAllPhitPhuel($start_date_obj->format("Y-m-d"), $end_date_obj->format("Y-m-d"), null, null, null, null, $selected_ids);
        //dd($arr_sales); exit;




        //
        $arr_results = [];

        //
        foreach($arr_sales as $sale_info){
            //dd($sale_info); exit;



            //
            $arr_sale_info = array();
            $arr_sale_info['sale_id'] = $sale_info['id'];





            /*--------------------------- SEND TWILIO MSG -------------------------*/
            //
            $status_res = Query::Single("SELECT Top 1 t.*, t2.status_title FROM sales_status t Left Join cat_sale_status t2 On t2.id = t.status_id Where t.sale_id = ? Order By t.id Desc", [$sale_info['id'], $status_id]);
            //dd($status_res); exit;

            //
            if ( isset($status_res['id']) && (int)$status_res['status_id'] === (int)$status_id ){
                //
                $arr_sale_info['update_results'] = "Cannot update to same status '" . $status_res['status_title'] . "'";
            }
            //
            else {

                //
                $insert_results = Query::DoTask([
                    "task" => "add",
                    "stmt" => "
                   Insert Into sales_status
                  ( sale_id, status_id, status_notes, user_id, datetime_created )
                  Values
                  ( ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
                    "params" => [
                        $sale_info['id'],
                        $status_id,
                        $status_notes,
                        $user_id
                    ],
                    "parse" => function($insert_id, &$query_results){
                        $query_results['id'] = (int)$insert_id;
                    }
                ]);
                //
                $arr_sale_info['update_results'] = $insert_results;






                /*--------------------------- SEND TWILIO MSG -------------------------*/
                // ONLY IF NOTIFY IS SET
                //
                if ($notify_customer){
                    //
                    $phone_number = $sale_info['phone_number'];
                    //
                    $parsed_msg = null;
                    //
                    $send_sms = Helper::SendSMS($app_id, MAQUETA_ID_SALE_UPDTD, $phone_number, function($maqueta_sms_msg) use(&$parsed_msg, $sale_info){
                        // set parsed msg as referenced var
                        $parsed_msg = VentaNotificationsController::ParseSaleMsg1($sale_info, $maqueta_sms_msg);
                        //echo $parsed_msg; exit;
                        return $parsed_msg;
                    });
                    $arr_sale_info['send_msg_results'] = $send_sms;

                    //
                    $msg_twilio_id = null;
                    if ( $send_sms && isset($send_sms['main_msg']) && isset($send_sms['main_msg']['id']) ){
                        $msg_twilio_id = $send_sms['main_msg']['id'];
                    }
                    //echo $msg_twilio_id; exit;
                    //
                    $insert_send_msg_results = Query::DoTask([
                        "task" => "add",
                        "debug" => true,
                        "stmt" => "
                   Insert Into sales_msgs
                  ( app_id, sale_id, msg_id, msg, msg_twilio_id, user_id, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
                        "params" => [
                            $app_id,
                            $sale_info['id'],
                            $msg_id,
                            $parsed_msg,
                            $msg_twilio_id,
                            $user_id
                        ],
                        "parse" => function($insert_id, &$query_results){
                            $query_results['id'] = (int)$insert_id;
                        }
                    ]);
                    $arr_sale_info['update_send_msg_results'] = $insert_send_msg_results;
                }

            }

            //
            array_push($arr_results, $arr_sale_info);
        }




        //
        return $response->withJson($arr_results, 200);
    }








    //
    public function ViewConfirmSale($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;

        //
        $app_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //echo "$app_id, $account_id"; exit;

        //
        $sale_code = $args['sale_code'];
        //echo $sale_code; exit;

        $sale_res = Query::single("select * from sales Where sale_code = ?", [$sale_code]);
        //dd($sale_res); exit; 
        //
        if (isset($sale_res['error']) && $sale_res['error']){
            echo $sale_res['error']; exit;
        } 
        if ( !(isset($sale_res['id']) && $sale_res['id']) ){
            echo "Venta inexistente"; exit;
        } 

        if ( $sale_res['sale_paid'] ){
            echo "Venta Pagada"; exit;
        }
        if ( $sale_res['seller_accepted'] ){
            echo "Venta Aceptada"; exit;
        } 

        //
        $config = ConfigSquare::getConfig($account_id);
        //dd($config); exit;

        //
        return $this->container->php_view->render($response, 'sites/plabuz/confirm-sale.phtml', [
            "App" => new App(null, $ses_data),
            "data" => $sale_res
        ]);
        
    }




    //
    public function PostConfirmSaleByCode($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;

        //
        $app_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //echo "$app_id, $account_id"; exit;

        //
        $results = [];

        //
        $sale_code = $args['sale_code'];
        //echo $sale_code; exit;
        $sale_res = Query::single("select * from sales Where sale_code = ?", [$sale_code]);
        //dd($sale_res); exit; 
        $sale_id = $sale_res['id'];
        $user_id = $sale_res['user_id'];
        //echo " $sale_id $user_id "; exit;
        
        //
        if (isset($sale_res['error']) && $sale_res['error']){
            //
            $results['error'] = $sale_res['error'];
            return $response->withJson($results, 200);
        } 
        if ( !(isset($sale_res['id']) && $sale_res['id']) ){
            //
            $results['error'] = "Venta Inexistente";
            return $response->withJson($results, 200);
        }
        
        //
        if ( $sale_res['sale_paid'] ){
            $results['error'] = "Venta ya ha sido pagada";
            return $response->withJson($results, 200);
        }
        if ( $sale_res['seller_accepted'] ){
            $results['error'] = "Venta ya ha sido aceptada";
            return $response->withJson($results, 200);
        }


        //
        $cant_ocupaciones = Query::single("select count(*) cant from temp_salidas_ocupacion Where sale_id = ?", [$sale_id]);
        //dd($cant_ocupaciones); exit;
        if ( !($cant_ocupaciones && isset($cant_ocupaciones['cant']) && $cant_ocupaciones['cant'] > 0) ){
            $results['error'] = "Venta sin ocupaciones o venta expirada, vuelva a realizar la solicitud";
            return $response->withJson($results, 200);
        }


       
        // accept | pay
        $sp_res = Ventas::AsignarOcupacion($sale_id, $user_id, "accept");



        //
        return $response->withJson($sp_res, 200);
    }





    //
    public function PostConfirmSaleById($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $customer_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];
        //echo "$customer_id, $app_id, $account_id"; exit;

        //
        $results = [];

        //
        $sale_id = $args['sale_id'];
        //echo $sale_code; exit;
        $sale_res = Query::single("select * from sales Where id = ?", [$sale_id]);
        //dd($sale_res); exit; 
        //
        if (isset($sale_res['error']) && $sale_res['error']){
            //
            $results['error'] = $sale_res['error'];
            return $response->withJson($results, 200);
        } 
        if ( !(isset($sale_res['id']) && $sale_res['id']) ){
            //
            $results['error'] = "Venta Inexistente";
            return $response->withJson($results, 200);
        }
        
        //
        if ( $sale_res['sale_paid'] ){
            $results['error'] = "Venta ya ha sido pagada";
            return $response->withJson($results, 200);
        }
        if ( $sale_res['seller_accepted'] ){
            $results['error'] = "Venta ya ha sido aceptada";
            return $response->withJson($results, 200);
        }

        //
        $user_id = $sale_res['user_id'];
        //echo "$user_id "; exit;


        //
        $cant_ocupaciones = Query::single("select count(*) cant from temp_salidas_ocupacion Where sale_id = ?", [$sale_id]);
        //dd($cant_ocupaciones); exit;
        if ( !($cant_ocupaciones && isset($cant_ocupaciones['cant']) && $cant_ocupaciones['cant'] > 0) ){
            $results['error'] = "Venta sin ocupaciones o venta expirada, vuelva a realizar la solicitud";
            return $response->withJson($results, 200);
        }


        // accept | pay
        $sp_res = Ventas::AsignarOcupacion($sale_id, $user_id, "accept");
        


        //
        return $response->withJson($sp_res, 200);
    }





    //
    public function PostGetPaymentIntent($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $app_id = $ses_data['app_id'];
        $store_id = $ses_data['id'];


        //
        Stripe::setApiKey(getStripeSecretKey());

        //
        $paymentIntent = PaymentIntent::create([
            "amount" => 1099,
            "currency" => "mxn",
            "payment_method_types" => ["oxxo"]
        ]);
        //dd($paymentIntent); exit;


        //
        return $response->withJson([
            "client_secret" => $paymentIntent->client_secret,
            "id" => $paymentIntent->id,
            "status" => $paymentIntent->status
        ], 200);
    }


    //
    public function PostPaySaleSquare($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        echo " $account_id, $app_id, $user_id "; exit;



        //
        $body = $request->getParsedBody();


        //
        $nonce = Helper::safeVar($body, 'nonce');
        $idempotency_key = Helper::safeVar($body, 'idempotency_key');
        $location_id = Helper::safeVar($body, 'location_id');
        //echo "$nonce, $idempotency_key, $location_id"; exit;

        //
        if ( !$nonce ){
            $results['error'] = "proporciona el $nonce";
            return $response->withJson($results, 200);
        }

        //
        $sale_id = $args['sale_id'];
        //
        $sale_res = Query::single("select * from sales Where id = ?", [$sale_id]);
        //dd($sale_res); exit;
        if (isset($sale_res['error']) && $sale_res['error']){
            return $response->withJson($sale_res, 200);
        }
        //
        $customer_name = $sale_res['customer_name'];
        $email = $sale_res['email'];
        $phone_number = $sale_res['phone_number'];
        $total_amount = $sale_res['grand_total'];


        //
        $forced_decimals_grand_total = number_format((float)$total_amount, 2, '.', '');
        //echo " $total_amount $forced_decimals_grand_total "; exit;
        

        //
        $is_test = true;
        $notify_results = null;


        
        //
        $payment_results = PaymentsGateways::pushPaymentSquare($account_id, "CAT-".$new_sale_id, $arr_payments, $forced_decimals_grand_total, $str_products, $token_id, $idempotency_key, $location_id, $is_test);
        //dd($payment_results); exit;
        //
        if (isset($payment_results['error']) && $payment_results['error']) {
            $results['error'] = $payment_results['error'];
            return $response->withJson($results, 200);
        }
        //
        else if (isset($payment_results['success']) && $payment_results['success']) {
        
            // 
            $sale_payments = Ventas::AddSalePayments($new_sale_id, $arr_payments);
            //dd($sale_payments); exit;
            if ( isset($sale_payments['error']) && $sale_payments['error'] ){
                $results['error'] = $sale_payments['error'];
                return $response->withJson($results, 200);
            }
            // se establece venta como pagada Ok
            $sale_payments['ocupacion_infos'] = Ventas::AsignarOcupacion($new_sale_id, $user_id, "pay");
            
            //
            $notify_results['email'] = Helper::SendEmail($account_id, $app_id, MAQUETA_ID_NEW_SALE, $customer_name, $customer_name, true, function($maqueta_email_msg) use($customer_info){
                return Ventas::ParseCustomerMessages($customer_info, $maqueta_email_msg);
            });

        }


        //
        return $response->withJson([
            "id" => $sale_id,
            "notify_results" => $notify_results,
        ], 200);         
    }





    //
    public function PostAddSale($request, $response, $args) {
       //
       $ses_data = $request->getAttribute("ses_data");
       //dd($ses_data); exit;


       //
       $user_id = $ses_data['id'];
       $app_id = $ses_data['app_id'];
       $account_id = $ses_data['account_id'];


        //
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();


        //
        $customer_id = Helper::safeVar($body, 'customer_id');
        $customer_name = Helper::safeVar($body, 'customer_name');
        $arr_items = Helper::safeVar($body, 'items');
        $customer_email = Helper::safeVar($body, 'email');
        $metodo_pago = Helper::safeVar($body, 'metodo_pago');
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_number = Helper::safeVar($body, 'phone_number');
        $payment_type = Helper::safeVar($body, 'type');
        //echo "$customer_id, $customer_name, $customer_email, $metodo_pago, $phone_country_id, $phone_number, $payment_type";exit;




        
        
         // datos mandatorios para pago square
         $token_id = Helper::safeVar($body, 'token_id');
         $idempotency_key = Helper::safeVar($body, 'idempotency_key');
         $location_id = Helper::safeVar($body, 'location_id');
         //echo "$token_id, $idempotency_key, $location_id"; exit;
 

    

         //
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }

         //
         $countries_results = CatPaises::GetById($phone_country_id);
         //dd($countries_results); exit;
         if ( isset($countries_results['error']) && $countries_results['error'] ){
             $results['error'] = $countries_results["error"];
             return $response->withJson($results, 200);
         }
         //
         if ( !(isset($countries_results['id']) && $countries_results['id']) ){
             $results['error'] = "Country does not exists";
             return $response->withJson($results, 200);
         }
 
         //
         $phone_cc = $countries_results['phone_cc'];
         //
         if ( !$v->validateString([10, 10], $phone_number) ){
             $results['error'] = "Provide a valid phone number";
             return $response->withJson($results, 200);
         }
         $full_phone_number = "+".$phone_cc . $phone_number;
         // DEBUG PHONES
         //echo $full_phone_number; exit;



         //
        if ( !($arr_items && is_array($arr_items) && count($arr_items) > 0) ){
            $results['error'] = "se requiren los pasajes";
            return $response->withJson($results, 200);
        }




        
         //
         if ( $payment_type === SQR_CARD ){
            //
            if ( !$token_id ){
                $results['error'] = "provide token_id";
                return $response->withJson($results, 200);
            }
            //
            if ( !$idempotency_key ){
                $results['error'] = "provide idempotency_key";
                return $response->withJson($results, 200);
            }
            //
            if ( !$location_id ){
                $results['error'] = "provide location_id";
                return $response->withJson($results, 200);
            }
        }
        //
        else if ( $payment_type === CASH ){
            /**/
        }
        //
        else if ( $payment_type === CREDIT ){
            /**/
        }
        //
        else {
            $results['error'] = "se requiere un metodo de pago valido";
            return $response->withJson($results, 200);
        }



        


        // Si ya transcurrieron 5 minutos libera asientos Ocupados
        $sp_res = Salidas::liberaAsientosOcupadosTemporalmente($app_id);
        //dd($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }




        




        //
        $a_credito = ($payment_type===CREDIT) ? 1 : 0;




        /**
         * 
         * Recopilamos informacion del cliente
         * 
         */
        $customer_type_id = null;
        $company_name = null;
        //
        if ( $customer_id > 0 ){
            $customer_info = Customers::GetRecordById($customer_id);
            //dd($customer_info); exit;
            $customer_type_id = ($customer_info && isset($customer_info['customer_type_id'])) ? (int)$customer_info['customer_type_id'] : null;
            $company_name = ($customer_info && isset($customer_info['company_name']) && $customer_type_id === 2 ) ? $customer_info['company_name'] : null;
        }
        $customer_info = [
            "customer_id" => $customer_id,
            "company_name" => $company_name,
            "customer_name" => $customer_name,
            "email" => $customer_email,
            "phone_number" => $full_phone_number,
            "customer_type_id" => $customer_type_id,
        ];
        //dd($customer_info); exit;
        



    

        //  
        $sale_results = Ventas::CreateSaleAndValidateItems(
            $a_credito,
            $customer_id,
            $company_name,
            $customer_name,
            $customer_email,
            $metodo_pago,
            $full_phone_number,
            $arr_items,
            $app_id,
            $user_id
        );
        //dd($sale_results); exit;
        if ( isset($sale_results['error']) && $sale_results['error'] ){
            $results['error'] = $sale_results['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($sale_results['id']) && $sale_results['id']) ){
            $results['error'] = "No se encontro la venta";
            return $response->withJson($results, 200);
        }
        //
        $new_sale_id = $sale_results['id'];
        $sale_code = $sale_results['sale_code'];
        


        //
        $sale_res = Query::single("select * from sales Where id = ?", [$new_sale_id]);
        //dd($sale_res); exit;
        if (isset($sale_res['error']) && $sale_res['error']){
            return $response->withJson($sale_res, 200);
        }
        //
        $total_amount = $sale_res['grand_total'];






        //
        $str_products = "Sale Id: $new_sale_id ...";
        //echo $str_products; exit;
        $customer_info['order_details'] = $str_products;
        $customer_info['id'] = $new_sale_id;
        $arr_payments = array();
        $payment_success_type = false;






        /**
         * Si no es pago a credito y no tiene monto mostramos la validacion
         * esto por que los metodos de pago con tarjeta no admiten pagos en zero
         */
        if ( $payment_type !== CREDIT && $total_amount <= 0 ){
            $res['error'] = "no se pueden pagar ventas en zero";
            return $res;
        }



        
        /**
         * 
         * Pago a Credito
         * Se envia link de pago para que cliente acepte
         * 
         */
        if ( $payment_type === CREDIT ){
            
            // 
            $send_email_results = Ventas::SendConfirmationEmail($account_id, $app_id, $new_sale_id);
            //
            return $response->withJson([
                "id" => $new_sale_id,
                "end_email_results" => $send_email_results,
            ], 200);

        }

        
        /**
         * 
         * Pago Efectivo
         * Se agrega pago y se asigna la ocupacion directamente
         * 
         */
        else if ( $payment_type === CASH ){

            //
            PaymentsGateways::pushPaymentCash($account_id, $app_id, $customer_name, $customer_email, $full_phone_number, $arr_payments, $total_amount);
            //dd($arr_payments); dd($payment_results); exit;

            $payment_success_type = SUCCESS.CASH;
        }


        /**
         * 
         * Pago Tarjeta
         * Se paga con square ya que es para MissionExpress
         * 
         */
        else if ( $payment_type === SQR_CARD ){


            //
            $forced_decimals_grand_total = number_format((float)$total_amount, 2, '.', '');
            //echo " $total_amount $forced_decimals_grand_total "; exit;
            //
            $is_test = true;

            //
            $payment_results = PaymentsGateways::pushPaymentSquare($account_id, "CAT-".$new_sale_id, $arr_payments, $forced_decimals_grand_total, $str_products, $token_id, $idempotency_key, $location_id, $is_test);
            //dd($payment_results); exit;
            if (isset($payment_results['error']) && $payment_results['error']) {
                $results['error'] = $payment_results['error'];
                return $response->withJson($results, 200);
            }
            //
            else if (isset($payment_results['success']) && $payment_results['success']) {
                $payment_success_type = SUCCESS.SQR_CARD;
            }
            
        }




        

        /* 
        * Pago Ok
        * */
        $notify_results = [];
        $sale_payments = null;
        //
        $array_success_words = [SUCCESS.CASH, SUCCESS.SQR_CARD];

        
        if ( in_array($payment_success_type, $array_success_words) ){


            // 
            $sale_payments = Ventas::AddSalePayments($new_sale_id, $arr_payments);
            //dd($sale_payments); exit;
            if ( isset($sale_payments['error']) && $sale_payments['error'] ){
                $results['error'] = $sale_payments['error'];
                return $response->withJson($results, 200);
            }


            // se establece venta como pagada Ok
            $sale_payments['ocupacion_info'] = Ventas::AsignarOcupacion($new_sale_id, $user_id, "pay");


           /*
           //
           $notify_results['sms'] = Helper::SendSMS($account_id, $app_id, MAQUETA_ID_NEW_SALE, $full_phone_number, function($maqueta_sms_msg) use($customer_info){
               return Ventas::ParseCustomerMessages($customer_info, $maqueta_sms_msg);
           });
           */

           //
           $notify_results['send_email'] = Helper::SendEmail($account_id, $app_id, MAQUETA_ID_NEW_SALE, $customer_name, $customer_email, true, function($maqueta_email_msg) use($customer_info){
               return Ventas::ParseTemplateNewSale($customer_info, $maqueta_email_msg);
           });
       }




       
       
        //
        return $response->withJson([
            "id" => $new_sale_id,
            "sale_payments" => $sale_payments,
            "notify_results" => $notify_results,
        ], 200);
    } 



    


    //
    public function PostPayPublicOrRegistered($request, $response, $args) {
        //
        $token_data = $request->getAttribute("token_data");
        $app_data = $request->getAttribute("app");
        //dd($token_data); exit;
        //
        $is_customer_auth = false;
        $allow_credit = 0;
        $customer_id = null;
        $user_id = null;

        //
        $app_id = $app_data['id'];
        $account_id = $app_data['account_id'];
        //
        $customer_app_id = null;
        $customer_app_name = null;
        $customer_account_id = null;
        //
        $results = array();

        //
        if ($token_data && isset($token_data['id'])){
            //
            $is_customer_auth = true;
            $customer_id = $token_data['id'];
            $customer_app_id = $token_data['app_id'];
            $customer_app_name = $token_data['acct_app_name'];
            $customer_account_id = $token_data['account_id'];
            //
            $allow_credit = ( (int)$token_data['customer_type_id'] === 2 && isset($token_data['allow_credit']) && $token_data['allow_credit']) ? 1 : 0;
        }        
        //echo "is_customer_auth: $is_customer_auth, allow_credit: $allow_credit, customer: $customer_id, user: $user_id, account: $account_id, customer_app_id: $customer_app_id, app: $app_id"; exit;




        /**
         * Validacion Adicional ( aunque el cliente que intenta pagar desde el login debe de validar que intenta ingresar desde donde creo su cuenta )
         * Si el customer esta logeado y no es la misma app en la que intenta pagar 
         * muestra el mensaje de error
         */
        if ( $is_customer_auth && ((int) $customer_app_id !== (int) $app_id) ){
            $results['error'] = "Unable to pay, not same App origin, Customer created account on: {$customer_app_name} ({$customer_app_id}) and tried to pay on app_id {$app_id} ";
             return $response->withJson($results, 200);            
        }
        
        
        
        


        //
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();




        // 
        $payment_type = Helper::safeVar($body, 'type');
        //echo "$payment_type"; exit;
        $arr_items = Helper::safeVar($body, 'arr_items');
        //dd($arr_items); exit;


        // Datos del Cliente
        $customer_name = Helper::safeVar($body, 'cardholderName');
        $customer_email = Helper::safeVar($body, 'email');
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_number = Helper::safeVar($body, 'phone_number');
        //echo "$customer_name $customer_email $phone_country_id $phone_number"; exit;




        

        //
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }

         //
         $countries_results = CatPaises::GetById($phone_country_id);
         //dd($countries_results); exit;
         if ( isset($countries_results['error']) && $countries_results['error'] ){
             $results['error'] = $countries_results["error"];
             return $response->withJson($results, 200);
         }
         //
         if ( !(isset($countries_results['id']) && $countries_results['id']) ){
             $results['error'] = "Country does not exists";
             return $response->withJson($results, 200);
         }
 
         //
         $phone_cc = $countries_results['phone_cc'];
         //
         if ( !$v->validateString([10, 10], $phone_number) ){
             $results['error'] = "Provide a valid phone number";
             return $response->withJson($results, 200);
         }
         $full_phone_number = "+".$phone_cc . $phone_number;
         // DEBUG PHONES
         //echo $full_phone_number; exit;




        //
        if ( !($arr_items && is_array($arr_items) && count($arr_items) > 0) ){
            $results['error'] = "se requiren los pasajes";
            return $response->withJson($results, 200);
        }






        // Datos requeridos para Stripe
        $payment_method_id = Helper::safeVar($body, 'payment_method_id');
        //echo $payment_method_id; exit;


        // Datos requeridos para Square
        $token_id = Helper::safeVar($body, 'token_id');
        $idempotency_key = Helper::safeVar($body, 'idempotency_key');
        $location_id = Helper::safeVar($body, 'location_id');
        //echo "$token_id, $idempotency_key, $location_id"; exit;
        


        // Datos requeridos para ANet
        $card_number = Helper::safeVar($body, 'card_number');
        $inpt_exp_date = Helper::safeVar($body, 'exp_date');
        $cvv = Helper::safeVar($body, 'cvv');
        //echo "$payment_type $card_number $inpt_exp_date $cvv "; exit;


       

        //
        $a_credito = 0;


        

        /**
         * 
         * SI EL PAGO ES DESDE EL HOST DE MISION EXPRESS
         * 
         */
        if ($app_id === APP_ID_MISSIONEXPRESS){
        
            //
            if ( $payment_type === SQR_CARD ){
                
                //
                if ( !$token_id ){
                    $results['error'] = "provide token_id";
                    return $response->withJson($results, 200);
                }
                //
                if ( !$idempotency_key ){
                    $results['error'] = "provide idempotency_key";
                    return $response->withJson($results, 200);
                }
                //
                if ( !$location_id ){
                    $results['error'] = "provide location_id";
                    return $response->withJson($results, 200);
                }
                
            }
            //
            else if ( $payment_type === CREDIT ){

                //
                if ( $is_customer_auth ){
                    
                    //
                    if ( !$allow_credit ){
                        $results['error'] = "credit not allowed to this customer"; return $response->withJson($results, 200);
                    }

                    // si es tipo credito y se premite credito lo ponemos a true
                    $a_credito = 1;

                }
                //
                else {
                    $results['error'] = "only authorized customers can credit"; return $response->withJson($results, 200);
                }
                
            } 
            //
            else {
                $results['error'] = "Se requiere un metodo de pago valido para MissionExpress"; return $response->withJson($results, 200);
            }

        }


        /**
         * 
         * SI EL PAGO ES DESDE EL HOST DE PLABUZ
         * 
         */
        else if ($app_id === APP_ID_PLABUZ){
        
            //
            if ( $payment_type === STRP_CARD ){
                
                //
                if ( !$payment_method_id ){
                    $results['error'] = "provide payment method id";
                    return $response->withJson($results, 200);
                }

            } 
            //
            else {
                $results['error'] = "Se requiere un metodo de pago valido para Plabuz"; return $response->withJson($results, 200);
            }

        }

        /**
         * 
         * SI EL PAGO ES DESDE EL HOST DE TICKETS4BUSES
         * 
         */
        else if ($app_id === APP_ID_T4B){
        
            //
            if ( $payment_type === ANET_CARD ){

                //
                if ( !$card_number ){
                    $results['error'] = "provide card_number";
                    return $response->withJson($results, 200);
                }
                $exp_date_obj = \Datetime::CreateFromFormat("d/m/Y", "01/".$inpt_exp_date);
                //dd($exp_date_obj); exit;
                if ( !$exp_date_obj ){
                    $results['error'] = "provide exp_date";
                    return $response->withJson($results, 200);
                }
                $exp_date = $exp_date_obj->format("m") . "/" . $exp_date_obj->format("Y");
                //echo $exp_date; exit;
                //            
                if ( !$cvv ){
                    $results['error'] = "provide cvv";
                    return $response->withJson($results, 200);
                }
                
                
            } 
            //
            else if ( $payment_type === CREDIT ){

                //
                if ( $is_customer_auth ){
                    
                    //
                    if ( !$allow_credit ){
                        $results['error'] = "credit not allowed to this customer"; return $response->withJson($results, 200);
                    }

                    // si es tipo credito y se premite credito lo ponemos a true
                    $a_credito = 1;

                }
                //
                else {
                    $results['error'] = "only authorized customers can credit"; return $response->withJson($results, 200);
                }
                
            } 
            //
            else {
                $results['error'] = "Se requiere un metodo de pago valido para Tickets4Buses"; return $response->withJson($results, 200);
            }

            
        } 
        
        /**
         * 
         * Si el pago es desde otro host
         * 
         */
        else {
            $results['error'] = "Se requiere host valido para pago";
            return $response->withJson($results, 200);
        }


        //echo "Ok AppId #{$app_id} with payment type: {$payment_type}"; exit;





        




        

        
        
        /**
         * 
         * Recopilamos informacion del cliente
         * 
         */
        $customer_type_id = null;
        $company_name = null;
        //
        if ( $customer_id > 0 ){
            $customer_info = Customers::GetRecordById($customer_id);
            //dd($customer_info); exit;
            $customer_type_id = ($customer_info && isset($customer_info['customer_type_id'])) ? (int)$customer_info['customer_type_id'] : null;
            $company_name = ($customer_info && isset($customer_info['company_name']) && $customer_type_id === 2 ) ? $customer_info['company_name'] : null;
        }
        $customer_info = [
            "customer_id" => $customer_id,
            "company_name" => $company_name,
            "customer_name" => $customer_name,
            "email" => $customer_email,
            "phone_number" => $full_phone_number,
            "customer_type_id" => $customer_type_id,
        ];
        //dd($customer_info); exit;





        /*
         * Create Sale
         * */
        //
        $sale_notes = null;

        // Si ya transcurrieron 5 minutos libera asientos Ocupados
        $sp_res = Salidas::liberaAsientosOcupadosTemporalmente(APP_ID_MISSIONEXPRESS);
        //dd($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }
        //
        $sale_results = Ventas::CreateSaleAndValidateItems(            
            $a_credito,
            $customer_id,
            $company_name,
            $customer_name,
            $customer_email,
            $payment_type,
            $full_phone_number,
            $arr_items,
            $app_id,
            $user_id
        );
        //dd($sale_results); exit;
        if ( isset($sale_results['error']) && $sale_results['error'] ){
            $results['error'] = $sale_results['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($sale_results['id']) && $sale_results['id']) ){
            $results['error'] = "No se genero la venta";
            return $response->withJson($results, 200);
        }
        //
        $new_sale_id = $sale_results['id'];
        $sale_code = $sale_results['sale_code'];

        


        //
        $sale_res = Query::single("select * from sales Where id = ?", [$new_sale_id]);
        //dd($sale_res); exit;
        if (isset($sale_res['error']) && $sale_res['error']){
            return $response->withJson($sale_res, 200);
        }
        //
        $total_amount = $sale_res['grand_total'];








        // 
        $str_products = "Sale ID: $new_sale_id - $customer_name / $customer_email ";
        $customer_info['order_details'] = $str_products;
        $customer_info['id'] = $new_sale_id;
        $payment_success_type = false;
        $arr_payments = array(); 

        




        if ( $payment_type === STRP_CARD || $payment_type === STRP_OXXO ){
            


            //
            $payment_results = PaymentsGateways::pushPaymentStripe($account_id, $app_id, $payment_method_id,$arr_payments, $total_amount, $str_products);
            //dd($arr_payments); dd($payment_results); exit;
            if (isset($payment_results['error']) && $payment_results['error']) {
                $results['error'] = $payment_results['error'];
                return $response->withJson($results, 200);
            }
            else if (isset($payment_results['success']) && $payment_results['success']) {
                $payment_success_type = SUCCESS.STRP_CARD;
            }
            else if (isset($payment_results['requiresAction']) && $payment_results['requiresAction']) {
                $payment_success_type = PENDING.STRP_OXXO;
            }
            

        }
        //
        else if ( $payment_type === ANET_CARD ){
            

            //$refId = uniqid("anet_id_");
            $refId = $new_sale_id;
            //echo "test 4"; exit;
            $payment_results = PaymentsGateways::pushPaymentAuthorizeNet($account_id, $app_id, $card_number, $exp_date, $cvv, $refId,$arr_payments, $total_amount);
            //dd($payment_results); exit;
            if (isset($payment_results['error']) && $payment_results['error']) {
                $results['error'] = $payment_results['error'];
                return $response->withJson($results, 200);
            }
            else if (isset($payment_results['success']) && $payment_results['success']) {
                $payment_success_type = SUCCESS.ANET_CARD;
            }

        }

        //
        else if ( $payment_type === SQR_CARD ){
        

    
            //
            $forced_decimals_grand_total = number_format((float)$total_amount, 2, '.', '');
            //echo " $total_amount $forced_decimals_grand_total "; exit;
            
            //
            $is_test = true;

            //
            $payment_results = PaymentsGateways::pushPaymentSquare($account_id, "CAT-".$new_sale_id, $arr_payments, $forced_decimals_grand_total, $str_products, $token_id, $idempotency_key, $location_id, $is_test);
            //dd($payment_results); exit;
            if (isset($payment_results['error']) && $payment_results['error']) {
                $results['error'] = $payment_results['error'];
                return $response->withJson($results, 200);
            }
            else if (isset($payment_results['success']) && $payment_results['success']) {
                $payment_success_type = SUCCESS.SQR_CARD;
            }

        }  

        /**
         * 
         * Si es a credito asigna la ocupacion directamente
         * 
         * 
         */
        else if ( $payment_type === CREDIT ){

            //
            $sp_res = Ventas::AsignarOcupacion($new_sale_id, $user_id, "accept");
            //dd($sp_res); exit;            
            //
            return $response->withJson($sp_res, 200);
        }
        

        
        
        


        /**
         * 
         * Debug Payments
         * 
         */
        //dd($arr_payments); exit;



        




        /*
         *
         * SI PAGO SALIO OK
         * ENTONCES PROCEDEMOS A ENVIAR EL SMS
         *
         * */
        //
        $notify_results = [];
        //
        $sale_payments = null;
        //
        $array_success_words = [SUCCESS.STRP_CARD, SUCCESS.SQR_CARD, SUCCESS.ANET_CARD];

        
        if ( in_array($payment_success_type, $array_success_words) ){


            // 
            $sale_payments = Ventas::AddSalePayments($new_sale_id, $arr_payments);
            //dd($sale_payments); exit;
            if ( isset($sale_payments['error']) && $sale_payments['error'] ){
                $results['error'] = $sale_payments['error'];
                return $response->withJson($results, 200);
            }


            // asignamos la ocupacion y marcamos la venta como pagada
            $sale_payments['ocupacion_info'] = Ventas::AsignarOcupacion($new_sale_id, $user_id, "pay");

            //
            if ( $sale_payments['ocupacion_info'] && isset($sale_payments['ocupacion_info']['error']) ){
                $results['error'] = $sale_payments['ocupacion_info']['error'];
                return $response->withJson($results, 200);
            }


            //
            $notify_results['send_email'] = Helper::SendEmail($account_id, $app_id, MAQUETA_ID_NEW_SALE, $customer_name, $customer_name, true, function($maqueta_email_msg) use($customer_info){
                return Ventas::ParseTemplateNewSale($customer_info, $maqueta_email_msg);

            });
        }

        // SI EL PAGO FUE PENDIENTE PASAMOS A REALIZAR SEGUN SEA EL CASO
        else if ($payment_success_type===PENDING.STRP_OXXO){
            
            /*
            //
            $customer_info['url_oxxo_voucher'] = ($arr_payments && isset($arr_payments['0']) && isset($arr_payments['0']['payer_name'])) ? $arr_payments['0']['payer_name'] : null;

            //
            $sale_payments = Ventas::AddSalePayments($new_sale_id, $arr_payments);
            //dd($sale_payments); exit;
            if ( isset($sale_payments['error']) && $sale_payments['error'] ){
                $results['error'] = $sale_payments['error'];
                return $response->withJson($results, 200);
            }
            //
            $notify_results['email'] = Helper::SendEmail($account_id, $app_id, MAQUETA_ID_NEW_SALE_OXXO, $company_info, $email, true, function($maqueta_email_msg) use($customer_info){
                return Ventas::ParseTemplateNewSale($customer_info, $maqueta_email_msg);
            });
            */

        }


        //dd($notify_results); exit;


        /*
         * Al hacer un pago con oxxo pay con el email "succeed_immediately@test.com"
         * hacemos un delay de 5 segundos para esperar a que salga el error donde dice que
         * el cliente ya confirmo el pago osea ya mando llamar en javascript la funcion "stripe.confirmOxxoPayment"
         * Error: "You cannot confirm this PaymentIntent because it has already succeeded after being previously confirmed."
         * */
        //sleep(3);


        //
        return $response->withJson(array(
            "payment_results" => $payment_results,
            "sale_payments" => $sale_payments,
            "notify_results" => $notify_results,
            "id" => $new_sale_id
        ), 200);
    }





    

    

    


    //
    public function PostActualizarOcupacion($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);


 

        // Si ya transcurrieron 5 minutos libera asientos Ocupados
        $sp_res = Salidas::liberaAsientosOcupadosTemporalmente($app_id);
        //dd($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        return $response->withJson([]);
    }





    // 
    public function PostAddToInvoice($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //
        $sale_id = $args['sale_id'];
        //echo $sale_id; exit;




        //
        $venta_res = Ventas::GetRecord($sale_id);
        //dd($venta_res); exit;
        if ( !($venta_res && isset($venta_res['id'])) ){
            $results['error'] = "sale not found"; 
            return $response->withJson($results, 200);
        }
        $customer_id = $venta_res['customer_id'];
        //echo $customer_id; exit;



        
        //
        $customer_info = Customers::GetSellerById($customer_id);
        //dd($customer_info); exit;
        if ( !($customer_info && isset($customer_info['id'])) ){
            $results['error'] = "customer not found"; 
            return $response->withJson($results, 200);
        }
        //
        $last_invoice_res = Invoices::GetCustomerLastInvoice($customer_id);
        //dd($last_invoice_res); exit;
        //
        if ( $last_invoice_res && isset($last_invoice_res['id'])){            

            if ( (int)$last_invoice_res['invoice_status_id'] === INVOICE_STATUS_ID_OPENED ){
                
            } else {

                $results['error'] = "no se puede agregar venta, estatus del ultimo invoice del cliente es " . $last_invoice_res['invoice_status']; 
                return $response->withJson($results, 200);

            }


        } else {
            $results['error'] = "cliente no tiene creado un invoice"; 
            return $response->withJson($results, 200);
        }


        
        $last_invoice_id = $last_invoice_res['id'];
        //echo " $customer_id $last_invoice_id $sale_id"; exit;


        //
        $param_record_id = 0;
        $param_updated_rows = 0;
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "debug" => true,
            "stmt" => function(){
                return "{call usp_AddSaleToInvoice(?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
            ],
            "params" => function() use(
                //
                $customer_id,
                $last_invoice_id,
                $sale_id,
                //
                &$param_updated_rows
            ){
                //
                return [
                    // 3
                    array($customer_id, SQLSRV_PARAM_IN),
                    array($last_invoice_id, SQLSRV_PARAM_IN),
                    array($sale_id, SQLSRV_PARAM_IN),
                    // 1
                    array(&$param_updated_rows, SQLSRV_PARAM_OUT)
                ];
            },
        ]);
        //dd($sp_res); exit;
        //
        $sp_res['updated_rows'] = $param_updated_rows;
        $sp_res['id'] = $sale_id;
        

        //
        return $response->withJson($sp_res, 200);
    }




    // 
    public function PostSendConfirmationLink($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //
        $sale_id = $args['sale_id'];


        $res = Ventas::SendConfirmationEmail($account_id, $app_id, $sale_id);
        return $response->withJson($res);


        //
        $cant_ocupaciones = Query::single("select count(*) cant from temp_salidas_ocupacion Where sale_id = ?", [$sale_id]);
        //dd($cant_ocupaciones); exit;
        if ( $cant_ocupaciones && isset($cant_ocupaciones['cant']) && $cant_ocupaciones['cant'] > 0 ){
            //
            $res = Ventas::SendConfirmationEmail($account_id, $app_id, $sale_id);
            //dd($res); exit;
            return $response->withJson($res);
        }

        //
        $res['error'] = "venta sin ocupaciones";
        return $response->withJson($res);
    }





    



    public static function setHtmlPageContent($body_content, $inline_css = ""){
        //
        $site_url = Helper::siteURL();
        // /assets/css/bootstrap.min.css | /assets/css/font-awesome.min.css       

        $font_awesome_min_css = $site_url."/adm/plugins/fontawesome-free/css/all.min.css";

        $bootstrap_min_css = $site_url."/css/bootstrap.min.css";
        //$bootstrap_min_css = $site_url."/adm/css/adminlte.css?v=1.2";

        //
        $page_style = $site_url."/adm/css/ticket-style.css";
        //echo $page_style; exit;
        //
        $additional_css_styles= <<<EOF
        <link rel="stylesheet" type="text/css" media="screen" href="$font_awesome_min_css">
        <link rel="stylesheet" type="text/css" media="screen" href="$bootstrap_min_css">
        <link rel="stylesheet" type="text/css" media="screen" href="$page_style">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
        EOF;


        
        
        //
        return <<<EOF
        <!DOCTYPE html>
        <html lang="en-us">
        <head>
            $additional_css_styles
        <style>
            $inline_css
        </style>
        </head>
        <body>
            $body_content
        </body>
        </html>
        EOF;
    }


    //
    public function PostSendTicket($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //
        $sale_id = $args['sale_id'];


         

        //
        $sale_res = Query::Single("Select id, sale_code, customer_name, email, phone_number FROM v_sales Where id = ?", [$sale_id], function(&$row){            
            //
            $row['salidas_ocupacion'] = Query::Multiple("SELECT t.id FROM salidas_ocupacion t Where t.sale_id = ?", [$row['id']]);
        });
        //dd($sale_res); exit;
        $customer_name = $sale_res['customer_name'];
        $sale_code = $sale_res['sale_code'];
        

        $file_customer_name = strtolower(str_replace(' ', '-', $customer_name));
        

        //
        $tickets_path = PATH_PUBLIC.DS.'files'.DS.'tickets';
        $pdf_file_path = $tickets_path.DS.$sale_id.'.pdf';
        $pdf_name = "ticket-{$file_customer_name}-{$sale_id}.pdf";
        //echo " $tickets_path $pdf_file_path $pdf_name"; exit;




        //
        $pdf = Helper::wkhtmlToPdf();


        //
        if ( $sale_res && isset($sale_res['salidas_ocupacion']) ){
            foreach($sale_res['salidas_ocupacion'] as $index => $item){
                //dd($item); exit;
                

                //
                $ticket_path = $sale_code . "-" . $item['id'];
                //echo $ticket_path . " --- ";

                //
                $site_url = Helper::siteURL();
                //echo $site_url; exit;
                $ticket_url = $site_url."/public/tickets/" . $ticket_path;

                
                
                //
                $pdf->addPage($ticket_url);
                //$pdf->addPage('/path/to/page.html');
                //$pdf->addPage($page_content);
            }
        }
        

        

        //
        $pdf_debug = false;
        

        //
        if ($pdf_debug){

            //
            if (!$pdf->send()) {
                $error = $pdf->getError();
                echo $error; exit;
            }
            exit;

        } else {




            //
            if (!$pdf_file_path){
                echo "no pdf file path provided"; exit;
            }

            


            //
            if (!$pdf->saveAs($pdf_file_path)) {
                $error = $pdf->getError();
                echo $error; exit;
            }




            //
            $send_copy_to_emails = true;
            //
            $maqueta_info = MaquetasMensajes::GetMaquetaInfo($account_id, MAQUETA_ID_ENVIO_TICKET, $send_copy_to_phones = false, $send_copy_to_emails);
            //dd($sale_res); exit;
            //
            if ( !(isset($maqueta_info['id']) && $maqueta_info['id']) ){
                $results['error'] = "maqueta not found";
                return $response->withJson($results);
            }
            $email_subject = $maqueta_info['email_subject'];
            $email_msg = $maqueta_info['email_msg'];
            //
            $parsed_subject = Ventas::ParseTemplateNewSale($sale_res, $email_subject);
            $parsed_msg = Ventas::ParseTemplateNewSale($sale_res, $email_msg);
            //echo "---- $parsed_subject ---- $parsed_msg ----"; exit;
            



            //
            $attachments = [];
            //
            array_push($attachments, array(
                "name" => $pdf_name,
                "filepath" => $pdf_file_path
            ));
            //
            $recipients = array();
            //
            array_push($recipients, array(
                "name" => $sale_res['customer_name'],
                "email" => $sale_res['email']
            ));
            self::sendTicket($account_id, $recipients, $attachments, $parsed_subject, $parsed_msg);
            //echo "done ok"; exit;
        }


        
        


       
        $res = [];
        

    
        //
        return $response->withJson($res);
    }





    public static function sendTicket($account_id, $recipients, $attachments, $parsed_subject, $parsed_msg){
        //
        $config_mail = SendMail::getMailConfig($account_id);
        //dd($config_mail); exit;
        //
        return SendMail::Send($config_mail, $recipients, $attachments, $parsed_subject, $parsed_msg, $parsed_msg);
    }




    //
    public function generatePublicTicketUrl($request, $response, $args) {
        //
        $app_data = $request->getAttribute("app");
        //dd($app_data); exit;
        //
        $account_id = $app_data['account_id'];
        $app_id = $app_data['id'];        
        //echo " $account_id  $app_id "; exit;
        //
        $sale_code_item_id = $args['sale_code_item_id'];
        //echo $sale_code_item_id; exit;
        $arr_parts = explode("-", $sale_code_item_id); 
        //var_dump($arr_parts); exit;
        //
        if ( !(isset($arr_parts[0]) && isset($arr_parts[1])) ){
            $results['error'] = "ticket unrecognized url string format";
            return $response->withJson($results);
        }

        //
        $sale_code = $arr_parts[0];
        $sale_item_id = $arr_parts[1];
        //echo " $sale_code  $sale_item_id "; exit;        



        $site_url = Helper::siteURL();


        //
        $display_pdf = $request->getQueryParam("pdf") ? true : false;

        
        
        //
        $sale_info = Query::Single("
            
            Select 
                
                t.*,
                ts.customer_id,
                ts.customer_name,
                ts.email customer_email,                
                ts.phone_number customer_phone_number

            FROM
                salidas_ocupacion t

            Left Join v_sales ts On ts.id = t.sale_id

                    Where ts.sale_code = ?
                    And t.id = ?

        ", [$sale_code, $sale_item_id]);
        //dd($sale_info); exit;
        if ( !(isset($sale_info['id']) && $sale_info['id']) ){
            $results['error'] = "sale not found";
            return $response->withJson($results);
        }
        $sale_id = $sale_info['sale_id'];
        $customer_id = $sale_info['customer_id'];
        $customer_name = $sale_info['customer_name'];
        $customer_email = $sale_info['customer_email'];
        $customer_phone_number = $sale_info['customer_phone_number'];        
        //echo $sale_id; exit;
        
        
        


        //
        $customer_info = null;
        //
        if ($customer_id){
            $customer_info = Query::Single("Select * FROM v_customers Where id = ?", [$customer_id]);
            //dd($customer_info); exit;
            if ( !(isset($customer_info['id']) && $customer_info['id']) ){
                $results['error'] = "customer not found";
                return $response->withJson($results);
            }
        }
        
            







        //
        $document_info = MaquetasDocumentos::GetMaquetaInfo($account_id, DOCUMENTO_ID_TICKET);
        //dd($document_info); exit;
        if ( !(isset($document_info['id']) && $document_info['id']) ){
            $results['error'] = "maqueta not found";
            return $response->withJson($results);
        }
        //
        $maqueta_name = $document_info['maqueta_name'];
        $maqueta_content = $document_info['maqueta_content'];
        //echo " $maqueta_name $maqueta_content "; exit;





        //
        $ticket_code_id = $sale_code . "-" . $sale_item_id;


        //
        $tickets_path  = PATH_PUBLIC.DS.'files'.DS.'tickets';
        $qrs_path  = PATH_PUBLIC.DS.'files'.DS.'qr';
        $qr_img_name = $ticket_code_id.'.png';
        //
        $qr_code_path  = $qrs_path.DS.$qr_img_name;
        $pdf_file_path = $tickets_path.DS.$sale_id.'.pdf';
        //echo " $tickets_path $pdf_file_path"; exit;

        

        
        //
        $ticket_number = $sale_id . "-" . $sale_item_id;
        $passanger_name = $sale_info['passanger_name'];
        $passanger_age = $sale_info['passanger_age'];
        $passanger_dob = $sale_info['passanger_dob']->format("d/M/Y");
        //
        $origen_info = $sale_info['origen_info'];
        $destino_info = $sale_info['destino_info'];
        $fecha_hora_salida = $sale_info['fecha_hora_salida']->format("d/M/Y h:i");
        $fecha_hora_llegada = $sale_info['fecha_hora_llegada']->format("d/M/Y h:i");
        //
        $autobus_clave = $sale_info['autobus_clave'];
        $num_asiento = $sale_info['num_asiento'];
        $tipo_precio_descripcion = $sale_info['tipo_precio_descripcion'];
        $calc_info = $sale_info['calc_info'];
        $costo_origen_destino = $sale_info['costo_origen_destino'];
        $sub_total = $sale_info['sub_total'];
        $total = $sale_info['total'];
        $datetime_created = $sale_info['datetime_created']->format("d/M/Y h:i");
        
        
        

        //
        $str_tipo_calculo = "";
        if ( $sale_info['tipo_precio_calculo'] === "calculo" ){
            $str_tipo_calculo = "<i style='text-decoration:line-through;color:orangered;'>$".$costo_origen_destino . "</i> $tipo_precio_descripcion <br /> $calc_info ";
        }

        $arr_items = "<tr>
            <td><span class='fa fa-arrow-right'></span> $origen_info - $fecha_hora_salida</td>
            <td><span class='fa fa-arrow-right'></span> $destino_info - $fecha_hora_llegada</td>
            <td>$autobus_clave</td>
            <td>$str_tipo_calculo</td>
            <td>$total</td>
        </tr>";
        //echo $arr_items; exit;




        //
        $sale_info['arr_items'] = $arr_items;
        //
        $sale_info['datetime_created'] = $datetime_created;
        $sale_info['ticket_title'] = "Ticket #<strong>" . $ticket_number . "</strong>";
        $sale_info['ticket_subtitle'] = "<small>created on $datetime_created </small>";
        //
        $sale_info['nombre_pasajero'] = "<h3><strong>" .  ucFirst($passanger_name) . "</strong> - lugar #$num_asiento </h3>";
        $sale_info['pasajero_info'] = "Edad: " . $passanger_age . " <small>(" . $passanger_dob . ")</small>";
        //
        $sale_info['customer_name'] = "Contacto: <strong>" . $customer_name . "</strong>";
        $sale_info['contacto_info'] = "<small>" . $customer_email . " - " . $customer_phone_number . "</small>";


        

        
        //
        $qr_code_url = $site_url."/public/tickets/" . $ticket_code_id;
        $qr_img_url = $site_url . "/files/qr/" . $qr_img_name;        
        //
        CodigoQR::Generar($qrs_path.DS.$qr_img_name, $qr_code_url);
        //
        //echo $qr_img_url; exit;
        $sale_info['qr_img_url'] = "<img src='" . $qr_img_url . "' style='width:200px;height:200px;' alt='QR Code'>";

        


        //dd($sale_info); exit;
        //
        $parsed_content = Ventas::ParseDocumentTicket($sale_info, $maqueta_content);
        //echo $parsed_content; exit;




        //
        $inline_css = "";
        $page_content = self::setHtmlPageContent($parsed_content, $inline_css);
        

        //
        if ($display_pdf){
            //
            $pdf = Helper::wkhtmlToPdf();
            $pdf->addPage($page_content);
            //
            if (!$pdf->send()) {
                $error = $pdf->getError();
                echo $error; exit;
            }
        }


        
        //
        echo $page_content; exit;
    }




    // 
    public function PostAcceptSale($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        var_dump($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        //
        $sale_id = $args['sale_id'];
        // accept | pay
        $sp_res = Ventas::AsignarOcupacion($sale_id, $user_id, "accept");
        
        //
        return $response->withJson($sp_res);
    }






     //
     public function PostPaySale($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        //
        $sale_id = $args['sale_id'];
        // accept | pay
        $sp_res = Ventas::AsignarOcupacion($sale_id, $user_id, "pay");
        //
        return $response->withJson($sp_res);
    }






    //
    public function PostApartarLugar($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        //$app_id = $ses_data['id'];
        //$account_id = $ses_data['account_id'];
        $user_id = null;


        $app_id = APP_ID_MISSIONEXPRESS;




        $results = [];



        // 
        $visitor_id = Helper::safeVar($request->getParsedBody(), 'visitor_id');
        $temp_sale_id = Helper::safeVar($request->getParsedBody(), 'temp_sale_id');
        $ruta_id = Helper::safeVar($request->getParsedBody(), 'ruta_id');
        $salida_id = Helper::safeVar($request->getParsedBody(), 'salida_id');
        $origen_ciudad_id = Helper::safeVar($request->getParsedBody(), 'origen_ciudad_id');
        $destino_ciudad_id = Helper::safeVar($request->getParsedBody(), 'destino_ciudad_id');
        $lugar_id = Helper::safeVar($request->getParsedBody(), 'lugar_id');
        $passanger_name = Helper::safeVar($request->getParsedBody(), 'passanger_name');
        $passanger_dob = Helper::safeVar($request->getParsedBody(), 'passanger_dob');
        $passanger_email = Helper::safeVar($request->getParsedBody(), 'passanger_email');

        //
        if ( !$passanger_name ){
            $results['error'] = "proporciona el passanger_name";
            return $response->withJson($results, 200);
        }
        //
        if ( !$passanger_dob ){
            $results['error'] = "proporciona el passanger_dob";
            return $response->withJson($results, 200);
        }
        //
        if ( !$passanger_email ){
            /*
            $results['error'] = "proporciona el passanger_email";
            return $response->withJson($results, 200);
            */
        }
        //
        if ( !$visitor_id ){
            $results['error'] = "proporciona el visitor_id";
            return $response->withJson($results, 200);
        }
        if ( !$temp_sale_id ){
            $results['error'] = "proporciona el temp_sale_id";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($ruta_id) ){
            $results['error'] = "proporciona el ruta_id";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($salida_id) ){
            $results['error'] = "proporciona el salida_id";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($lugar_id) ){
            $results['error'] = "proporciona el lugar_id";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($origen_ciudad_id) ){
            $results['error'] = "proporciona el origen_ciudad_id";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($destino_ciudad_id) ){
            $results['error'] = "proporciona el destino_ciudad_id";
            return $response->withJson($results, 200);
        }
        //echo $destino_ciudad_id; exit;




        




        // Si ya transcurrieron 5 minutos libera asientos Ocupados
        $sp_res = Salidas::liberaAsientosOcupadosTemporalmente($app_id);
        //dd($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }



        //
        $res = Ventas::addTempPasajeroOcupacion($app_id, $visitor_id, $temp_sale_id, $user_id, $ruta_id, $salida_id, $lugar_id, $origen_ciudad_id, $destino_ciudad_id, $passanger_name, $passanger_dob, $passanger_email);
        //dd($res); exit;
        //
        if ( isset($res['error']) && $res['error'] ){
            return $response->withJson($res, 200);
        }
        //
        if ( isset($res['id']) && $res['id'] ){

            //
            $salida_ocupacion_id = $res['id'];

            //
            $results = Query::single("select * from temp_salidas_ocupacion Where id = ?", [$salida_ocupacion_id]);
            //
            if ( $results && isset($results['id']) ){
                $results['datetime_created'] = $results['datetime_created']->format("Y-m-d H:i");
                $results['passanger_dob'] = $results['passanger_dob']->format("Y-m-d H:i");
                $results['fecha_hora_salida'] = $results['fecha_hora_salida']->format("Y-m-d H:i");
                $results['fecha_hora_llegada'] = $results['fecha_hora_llegada']->format("Y-m-d H:i");
            }
            
            //
            return $response->withJson($results);
        }
        

        
        //
        $results['error'] = "No se aparto el lugar";
        return $response->withJson($results);
    }













    
    


    //
    public function PostClearVenta($request, $response, $args) {
       //
       $ses_data = $request->getAttribute("app");
       //dd($ses_data); exit;
       $app_id = $ses_data['id'];
       $account_id = $ses_data['account_id'];
       //
       $user_id = null;





        //
        //$arr_sale_items = Helper::safeVar($request->getParsedBody(), 'arr_sale_items');
        $visitor_id = Helper::safeVar($request->getParsedBody(), 'visitor_id');
        $temp_sale_id = Helper::safeVar($request->getParsedBody(), 'temp_sale_id');
        //dd($arr_sale_items); exit;

        /*
        if ( !($arr_sale_items && is_array($arr_sale_items) ) ){
            $results['error'] = "array of items required";
            return $response->withJson($results, 200);
        }
        //
        $temp_salida_ocupacion_ids = array();
        //
        foreach ($arr_sale_items as $elemento) {
            // Verificar si 'id' está presente en el subarray
            if (isset($elemento['insert_id'])) {
                // Agregar el valor de 'id' al array $ids
                $temp_salida_ocupacion_ids[] = $elemento['insert_id'];
            }
        }
        //
        $temp_salida_ocupacion_ids_comma_separated = implode(',', $temp_salida_ocupacion_ids);
        //echo $temp_salida_ocupacion_ids_comma_separated; exit;
        //
        $ids = rtrim($temp_salida_ocupacion_ids_comma_separated, ',');
        //
        //echo $ids; exit;
        */


         //
         $stmt = "Delete FROM temp_salidas_ocupacion Where visitor_id = ?; SELECT @@ROWCOUNT";
         //echo $stmt; exit;

         //
         $results = Query::DoTask([
             "task" => "delete",
             "stmt" => $stmt,
             "params" => [
                 $visitor_id
             ],
             "parse" => function($updated_rows, &$query_results) use($visitor_id){
                 $query_results['affected_rows'] = $updated_rows;
                 $query_results['visitor_id'] = $visitor_id;
             }
         ]);
         //var_dump($results); exit;

        //
        $results['success'] = true;


        //
        return $response->withJson($results);
    }








    //
    public function PostDelTempItem($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        $app_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $user_id = null;





        //
        $del_id = Helper::safeVar($request->getParsedBody(), 'id');
        $visitor_id = Helper::safeVar($request->getParsedBody(), 'visitor_id');
        //echo $visitor_id; exit;

        //
        if ( !is_numeric($del_id) ){
            $results['error'] = "del id required";
            return $response->withJson($results, 200);
        }
        if ( !$visitor_id ){
            $results['error'] = "visitor_id required";
            return $response->withJson($results, 200);
        }
        




        //
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM temp_salidas_ocupacion Where visitor_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $visitor_id,
                $del_id
            ],
            "parse" => function($updated_rows, &$query_results) use($del_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = $del_id;
            }
        ]);
        //var_dump($results); exit;




        //
        return $response->withJson($results);
    }







    






    //
    public function PostDeleteStoreVenta($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $store_id = $args['id'];
        //echo $store_id; exit;


        //
        $results = array();


        //
        $sale_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$sale_id ){
            $results['error'] = "proporciona el id"; return $response->withJson($results, 200);
        }



        //
        $venta_res = Query::Single("Select * FROM v_sales Where app_id = ? And store_id = ? And id = ?", [
            $app_id,
            $store_id,
            $sale_id
        ]);
        //dd($venta_res); exit;
        if ( $venta_res && isset($venta_res['id']) && $venta_res['id'] ){
            //
            $sale_status_id_ready = 1;
            $payment_status_id_completed = 1;
            //
            if ( (int)$venta_res['status_id'] === $sale_status_id_ready && (int)$venta_res['payment_status_id'] === $payment_status_id_completed ){
                $results['error'] = "Negocio ya cuenta con una suscripcion activa valida";
                return $response->withJson($results, 200);
            }
        }



        //
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM sales Where app_id = ? And store_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $store_id,
                $sale_id
            ],
            "parse" => function($updated_rows, &$query_results) use($sale_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$sale_id;
            }
        ]);
        //var_dump($results); exit;




        //
        return $response->withJson($results, 200);
    }





    


    //
    public function PostCompleteStoreVenta($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $store_id = $args['id'];
        //echo $store_id; exit;


        //
        $results = array();


        //
        $sale_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$sale_id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }


        //
        $payment_status_id_completed = 1;
        $payment_status_completed = "Completed";


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    sales_payments
                  
                  Set 
                    --
                    payment_status_id = ?,
                    payment_status = ?
                   
                  Where app_id = ? 
                  And sale_id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $payment_status_id_completed,
                $payment_status_completed,
                //
                $app_id,
                $sale_id
            ],
            "parse" => function($updated_rows, &$query_results) use($sale_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$sale_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        $status_completed_id = 1;
        $status_notes = null;


        //
        $update_results['status'] = Query::DoTask([
            "task" => "add",
            "stmt" => "
                   Insert Into sales_status
                  ( app_id, sale_id, status_id, status_notes, user_id, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id,
                $sale_id,
                $status_completed_id,
                $status_notes,
                $user_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);



        //
        return $response->withJson($update_results, 200);
    }





}