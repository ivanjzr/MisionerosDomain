<?php
namespace App\Ventas;




//
use App\Coupons\CouponsCoupons;
use App\Products\MealsSizes;
use App\Products\Products;
use App\Salidas\Salidas;
use App\Utils\Utils;
use App\Products\ProductsTags;
use Helpers\Helper;
use Helpers\PHPMicroParser;
use Helpers\Query;


//
class Ventas
{





    //
    public static function GetAll($app_id, $qty = 12){
        //
        return Query::Multiple("
				SELECT
                    
                    t.*,
                    ts.name as pickup_store,
                    t2.name as kitchen_store
                    
                        FROM ViewVentas t
                
				            Left Join sucursales ts On ts.id = t.pickup_store_id
                            Left Join sucursales t2 On t2.id = t.kitchen_store_id
				
				
                           Where t.app_id = ?      
				           And t.tipo = 'meal'
				
                        order by t.id Desc
			", [
            $app_id
        ], function(&$row) use ($app_id){

            //
            $row['items'] = VentasItems::GetAll($row['id']);

        });
    }






    //
    public static function GetAllPhitPhuel($str_search_clause){
        echo $str_search_clause; exit;


        /*
        $str_where_ids = "";
        if ($str_ids){
            $str_where_ids = " And t.id In($str_ids) ";
        }
        */
        //echo $str_where_ids; exit;



    }


    //
    public static function GetStrSalesIds($arr_sales_ids){
        //
        return implode(', ', array_map(function($v) {
            //var_dump($v); exit;
            return $v['sale_id'];
        }, $arr_sales_ids));
    }



    //
    public static function GetAllPhitPhuelV2($app_id, $str_sales_ids, $pickup_delivery, $pickup_store_id, $ready_o_start_datetime){
        //echo " $app_id, $str_sales_ids, $pickup_delivery, $store_id "; exit;


        //
        $search_clause = "";
        //
        if ( $pickup_delivery === "pickup" ){
            //
            $search_clause .= " And t.pickup_store_id Is Not Null";
            //
            if ( $pickup_store_id ){
                $search_clause .= " And t.pickup_store_id = $pickup_store_id ";
            }
        }
        //
        else if ( $pickup_delivery === "delivery" ){
            $search_clause .= " And t.pickup_store_id Is Null";
        }
        //echo $search_clause; exit;


        //
        return Query::Multiple("
				SELECT
                    
                    t.*,
                    ts.name as pickup_store,
                    t2.name as kitchen_store,
                    t3.name as nearest_store
                    
                        -- 
                        FROM ViewVentas t
                
				            Left Join sucursales ts On ts.id = t.pickup_store_id
                            Left Join sucursales t2 On t2.id = t.kitchen_store_id
                            Left Join sucursales t3 On t3.id = t.nearest_store_id
				
                           Where t.app_id = ?
                           
                           And t.id In($str_sales_ids)
                           
                           {$search_clause}
                                 
                        order by t.id Desc
			", [
            $app_id
        ], function(&$row) use ($app_id, $ready_o_start_datetime){
            //Helper::printFull($row); exit;

            //
            $row['items'] = VentasItems::GetAllPhitPhuelForReport($row['id'], $ready_o_start_datetime);
            //Helper::printFull($row['items']); exit;

        });
    }



    //
    public static function GetDeliveries($app_id, $str_sales_ids, $ready_o_start_datetime){
        //echo $str_sales_ids; exit;
        //
        return Query::Multiple("
				SELECT
                    
                    t.*,
                    ts.name as pickup_store,
                    t2.name as kitchen_store
                    
                        -- 
                        FROM ViewVentas t
                
				            Left Join sucursales ts On ts.id = t.pickup_store_id
                            Left Join sucursales t2 On t2.id = t.kitchen_store_id
				
                           Where t.app_id = ?
                           And t.pickup_store_id Is Null
                           
                           And t.id In($str_sales_ids)
                                 
                        order by t.id Desc
			", [
            $app_id
        ], function(&$row) use ($app_id, $ready_o_start_datetime){
            //Helper::printFull($row); exit;

            //
            $row['items'] = VentasItems::GetAllPhitPhuelForReport($row['id'], $ready_o_start_datetime);
            //Helper::printFull($row['items']); exit;

        });
    }


    //
    public static function GetRecord($id){
        //
        return Query::Single("SELECT t.*FROM v_sales t Where t.id = ?", [$id],
            function(&$row){
                 // SI HA SIDO ACEPTADA (CUANDO ES CREDITO) O HA SIDO PAGADA (CUANDO ES PAGO)
                 if ( $row['seller_accepted'] || $row['sale_paid'] ){                        
                    $row['salidas_ocupacion'] = Ventas::GetSalidasOcupacion($row['id']);
                } else {
                    $row['salidas_ocupacion'] = Ventas::GetTempSalidasOcupacion($row['id']);
                }
                

                //
                $row['sale_payments'] = VentasPayments::GetAll($row['id']);
            }
        );
    }


    
    //
    public static function GetSalidasOcupacion($sale_id, $sale_code = ""){
        //
        return Query::Multiple("SELECT t.* FROM salidas_ocupacion t Where t.sale_id = ?", [$sale_id], function(&$row) use($sale_code){
            //
            if ($sale_code){
                $ticket_path = $sale_code . "-" . $row['id'];
                //echo $ticket_path . " --- ";
                //
                $site_url = Helper::siteURL();
                //echo $site_url; exit;
                //
                $row['ticket_url'] = $site_url."/public/tickets/" . $ticket_path;
            }
        });
    }

     //
     public static function GetTempSalidasOcupacion($sale_id){
        // 
        return Query::Multiple("
				SELECT t.*, (1) as is_temp_salida FROM temp_salidas_ocupacion t Where t.sale_id = ?", [$sale_id]);
    }




    //
    public static function CreateSaleAndValidateItems(
            $a_credito,
            $customer_id,
            $company_name,
            $customer_name,
            $email,
            $payment_type,
            $phone_number,
            $arr_items,
            $sale_app_id,
            $user_id
        ){


        //
        $results = array();



        //
        $xml_conceptos = self::setXMLConceptos($arr_items);
        //echo $xml_conceptos; exit;

        //
        $param_record_id = 0;
        $sale_code = 0;
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "all",
            "stmt" => function(){
                return "{call usp_CreateSale(?,?,?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "ERR_OCUPACION_NOT_FOUND" => "Ocupacion no disponible, verifica disponibilidad",
                "default" => "Error while creating sale",
            ],
            "debug" => false,
            "params" => function() use(
                $a_credito,
                $customer_id,
                $company_name,
                $customer_name,
                $email,
                $payment_type,
                $phone_number,
                $sale_app_id,
                $user_id,
                $xml_conceptos,
                &$param_record_id,
                &$sale_code
            ){
                return [
                    array($a_credito, SQLSRV_PARAM_IN),
                    array($customer_id, SQLSRV_PARAM_IN),
                    array($company_name, SQLSRV_PARAM_IN),
                    array($customer_name, SQLSRV_PARAM_IN),
                    array($email, SQLSRV_PARAM_IN),
                    array($payment_type, SQLSRV_PARAM_IN),
                    array($phone_number, SQLSRV_PARAM_IN),
                    //
                    array($sale_app_id, SQLSRV_PARAM_IN),
                    array($user_id, SQLSRV_PARAM_IN),
                    array($xml_conceptos, SQLSRV_PARAM_IN),
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                    array(&$sale_code, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //Helper::printFull($sp_res); exit;
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['id'] = $param_record_id;
        $results['sale_code'] = $sale_code;
        //
        return $results;
    }






    
    



    //
    public static function addTempPasajeroOcupacion($app_id, $visitor_id, $temp_sale_id, $user_id, $ruta_id, $salida_id, $lugar_id, $origen_ciudad_id, $destino_ciudad_id, $passanger_name, $passanger_dob, $passanger_email) {
    
        
        //
        $results = [];

    
        //
        $salida_info = Salidas::getSalidaInfo($app_id, $ruta_id, $salida_id, $origen_ciudad_id, $destino_ciudad_id);
        //Helper::printFull($salida_info); exit;
        //
        if ( $salida_info && isset($salida_info['id']) ){


            $autobus_id = $salida_info['autobus_id'];
            $discount_id = null;

            //
            $origen_orden_num = $salida_info['origen_orden_num'];
            $origen_ciudad_info = $salida_info['origen_ciudad_info'];
            $fecha_hora_salida = $salida_info['fecha_hora_salida']->format("Y-m-d H:i");
            $ext_destino_ciudad_id = $salida_info['ext_destino_ciudad_id'];
            $ext_destino_info = $salida_info['ext_destino_info'];
            //
            $destino_orden_num = $salida_info['destino_orden_num'];
            $destino_ciudad_info = $salida_info['destino_ciudad_info'];
            $fecha_hora_llegada = $salida_info['fecha_hora_llegada']->format("Y-m-d H:i");
            $ext_origen_ciudad_id = $salida_info['ext_origen_ciudad_id'];
            $ext_origen_info = $salida_info['ext_origen_info'];

            //
            $sum_distancia_metros = $salida_info['sum_distancia_metros'];
            $sum_duracion_mins = $salida_info['sum_duracion_mins'];
            $sum_duracion_mins_espera = $salida_info['sum_duracion_mins_espera'];
            //
            $sum_precio_regular = $salida_info['sum_precio_regular'];
            $sum_precio_prom_distancia = $salida_info['sum_precio_prom_distancia'];
            $sum_precio_prom_ambos = $salida_info['sum_precio_prom_ambos'];
            $sub_total = $salida_info['sub_total'];
            $ext_origen_precio = $salida_info['ext_origen_precio'];
            $ext_destino_precio = $salida_info['ext_destino_precio'];
            $total = $salida_info['total'];
            //
            $destino_aplica_comision = $salida_info['destino_aplica_comision'];
            $destino_comision_monto = $salida_info['destino_comision_monto'];


            //
            $inserted_ocupacion_id = 0;
            //
            $sp_res = Query::StoredProcedure([
                "ret" => "all",
                "debug" => true,
                "stmt" => function(){
                    return "{call usp_AddTempOcupacion(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
                },
                "exeptions_msgs" => [
                    "default" => "Server Error, unable to do operation"
                ],
                "debug" => true,
                "params" => function() use(
                    // 7
                    $app_id,
                    $visitor_id,
                    $temp_sale_id,
                    $ruta_id,
                    $salida_id,
                    $autobus_id,
                    $lugar_id,
                    // 3
                    $passanger_name,
                    $passanger_dob,
                    $discount_id,
                    // 6
                    $origen_ciudad_id,
                    $origen_orden_num,
                    $origen_ciudad_info,
                    $fecha_hora_salida,
                    $ext_destino_ciudad_id,
                    $ext_destino_info,
                    // 6
                    $destino_ciudad_id,
                    $destino_orden_num,
                    $destino_ciudad_info,
                    $fecha_hora_llegada,
                    $ext_origen_ciudad_id,
                    $ext_origen_info,
                    // 3
                    $sum_distancia_metros,
                    $sum_duracion_mins,
                    $sum_duracion_mins_espera,
                    // 3
                    $sum_precio_regular,
                    $sum_precio_prom_distancia,
                    $sum_precio_prom_ambos,
                    // 3
                    $sub_total,
                    $ext_origen_precio,
                    $ext_destino_precio,
                    // 2
                    $destino_aplica_comision,
                    $destino_comision_monto,
                    // 2
                    $user_id,
                    &$inserted_ocupacion_id
                ){
                    // 31
                    return [
                        // 7
                        array($app_id, SQLSRV_PARAM_IN),
                        array($visitor_id, SQLSRV_PARAM_IN),
                        array($temp_sale_id, SQLSRV_PARAM_IN),
                        array($ruta_id, SQLSRV_PARAM_IN),
                        array($salida_id, SQLSRV_PARAM_IN),
                        array($autobus_id, SQLSRV_PARAM_IN),
                        array($lugar_id, SQLSRV_PARAM_IN),
                        // 3
                        array($passanger_name, SQLSRV_PARAM_IN),
                        array($passanger_dob, SQLSRV_PARAM_IN),
                        array($discount_id, SQLSRV_PARAM_IN),
                        // 6
                        array($origen_ciudad_id, SQLSRV_PARAM_IN),
                        array($origen_orden_num, SQLSRV_PARAM_IN),
                        array($origen_ciudad_info, SQLSRV_PARAM_IN),
                        array($fecha_hora_salida, SQLSRV_PARAM_IN),
                        array($ext_destino_ciudad_id, SQLSRV_PARAM_IN),
                        array($ext_destino_info, SQLSRV_PARAM_IN),
                        // 6
                        array($destino_ciudad_id, SQLSRV_PARAM_IN),
                        array($destino_orden_num, SQLSRV_PARAM_IN),
                        array($destino_ciudad_info, SQLSRV_PARAM_IN),
                        array($fecha_hora_llegada, SQLSRV_PARAM_IN),
                        array($ext_origen_ciudad_id, SQLSRV_PARAM_IN),
                        array($ext_origen_info, SQLSRV_PARAM_IN),
                        // 3
                        array($sum_distancia_metros, SQLSRV_PARAM_IN),
                        array($sum_duracion_mins, SQLSRV_PARAM_IN),
                        array($sum_duracion_mins_espera, SQLSRV_PARAM_IN),
                        // 3
                        array($sum_precio_regular, SQLSRV_PARAM_IN),
                        array($sum_precio_prom_distancia, SQLSRV_PARAM_IN),
                        array($sum_precio_prom_ambos, SQLSRV_PARAM_IN),
                        // 3
                        array($sub_total, SQLSRV_PARAM_IN),
                        array($ext_origen_precio, SQLSRV_PARAM_IN),
                        array($ext_destino_precio, SQLSRV_PARAM_IN),
                        // 2
                        array($destino_aplica_comision, SQLSRV_PARAM_IN),
                        array($destino_comision_monto, SQLSRV_PARAM_IN),
                        // 2
                        array($user_id, SQLSRV_PARAM_IN),
                        array(&$inserted_ocupacion_id, SQLSRV_PARAM_OUT),
                    ];
                }
            ]);
            //Helper::printFull($sp_res); exit;
            //
            if (isset($sp_res['error']) && $sp_res['error']){
                $results['error'] = $sp_res['error'];
                return $results;
            }

            //
            $results['id'] = $inserted_ocupacion_id;
        }
        else {
            $results['error'] = "salida not found";
        }
          

        //
        return $results;
    }





    //
    public static function AddSalePayments( $sale_id, $arr_payments ){

        //
        $results = array();

        //
        if ( !($arr_payments && is_array($arr_payments)) ){
            $results['error'] = "no existe array de pago";
            return $results;
        }

        //
        $xml_payments = self::setXMLPayments($arr_payments);
        //echo $xml_payments; exit;

        //
        $inserted_records = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_AddSalePayments(?,?,?)}";
            },
            "exeptions_msgs" => [
                "ERR_COUPON_NOT_VALID" => "Coupon not valid"
            ],
            "debug" => true,
            "params" => function() use($sale_id, $xml_payments, &$inserted_records){
                return [
                    //
                    array($sale_id, SQLSRV_PARAM_IN),
                    //
                    array($xml_payments, SQLSRV_PARAM_IN),
                    array(&$inserted_records, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['inserted_payments'] = $inserted_records;
        //
        return $results;
    }






    

    //
    public static function AsignarOcupacion($sale_id, $user_id, $type){
        //
        $results = array();
        //
        $updated_records = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call dbo.usp_AsignarOcupacion(?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "ERR_COUPON_NOT_VALID" => "Coupon not valid"
            ],
            "debug" => true,
            "params" => function() use($sale_id, $user_id, $type, &$updated_records){
                return [
                    //
                    array($sale_id, SQLSRV_PARAM_IN),
                    array($user_id, SQLSRV_PARAM_IN),
                    array($type, SQLSRV_PARAM_IN),
                    array(&$updated_records, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['id'] = $sale_id;
        $results['updated_records'] = $updated_records;
        //
        return $results;
    }


















    //
    public static function setXMLConceptos($arr_conceptos){
        //Helper::printFull($arr_conceptos); exit;
        //Helper::printFull($arr_conceptos[0]); exit;

        //
        $str_xml = "<root>";

        //
        foreach($arr_conceptos as $index => $item){
            //Helper::printFull($item); exit;
            /*
            $autobus_clave = $item['autobus_clave'];
            $autobus_id = $item['autobus_id'];
            $calc_info = $item['calc_info'];
            $clasificacion_info = $item['clasificacion_info'];
            $datetime_created = $item['datetime_created'];
            $destino_ciudad_id = $item['destino_ciudad_id'];
            $destino_ciudad_info = $item['destino_ciudad_info'];
            $fecha_hora_llegada = json_encode($item['fecha_hora_llegada']); // Convertimos a JSON para mostrar el objeto
            $fecha_hora_salida = json_encode($item['fecha_hora_salida']); // Convertimos a JSON para mostrar el objeto
            $salida_id = $item['id'];
            $temp_salida_ocupacion_id = $item['insert_id'];
            $lugar_id = $item['lugar_id'];
            $origen_ciudad_id = $item['origen_ciudad_id'];
            $origen_ciudad_info = $item['origen_ciudad_info'];
            $passanger_age = $item['passanger_age'];
            $passanger_dob = $item['passanger_dob'];
            $passanger_name = $item['passanger_name'];
            $precio_base = $item['precio_base'];
            $precio_total = $item['precio_total'];
            $ruta_id = $item['ruta_id'];
            echo "$autobus_clave, $autobus_id, $calc_info, $clasificacion_info, $datetime_created, $destino_ciudad_id, $destino_ciudad_info, $fecha_hora_llegada, $fecha_hora_salida, salida_id: $salida_id, $temp_salida_ocupacion_id, $lugar_id, $origen_ciudad_id, $origen_ciudad_info, $passanger_age, $passanger_dob, $passanger_name, $precio_base, $precio_total, $ruta_id";exit;
            */
            //
            $str_xml .= "<item>";
            //
            $str_xml .= "<idx>" . ($index + 1) . "</idx>";
            $str_xml .= "<temp_sale_id>" . $item['temp_sale_id'] . "</temp_sale_id>";
            $str_xml .= "<temp_salida_ocupacion_id>" . $item['id'] . "</temp_salida_ocupacion_id>";
            //
            $str_xml .= "</item>";
        }
        //
        $str_xml .= "</root>";



        //Helper::previewXml($str_xml); exit;
        //
        return $str_xml;
    }






    //
    public static function setXMLPayments($arr_payments){
        //echo "<pre>";print_r($arr_payments);echo "</pre>";exit;
        //var_dump($arr_payments[0]); exit;

        //
        $str_xml = "<root>";

        //
        foreach($arr_payments as $index => $payment){
            //var_dump($payment); exit;

            //
            $str_xml .= "<payment>";
            //
            $str_xml .= "<payment_type_id>" . $payment['payment_type_id'] . "</payment_type_id>";
            $str_xml .= "<payer_name>" . $payment['payer_name'] . "</payer_name>";
            $str_xml .= "<payer_address>" . $payment['payer_address'] . "</payer_address>";
            $str_xml .= "<email_address>" . $payment['email_address'] . "</email_address>";
            $str_xml .= "<phone_number>" . $payment['phone_number'] . "</phone_number>";
            $str_xml .= "<transaction_id>" . $payment['transaction_id'] . "</transaction_id>";
            $str_xml .= "<auth_code>" . $payment['auth_code'] . "</auth_code>";
            $str_xml .= "<paypal_payer_id>" . $payment['paypal_payer_id'] . "</paypal_payer_id>";
            $str_xml .= "<intent>" . $payment['intent'] . "</intent>";
            $str_xml .= "<paypal_status>" . $payment['paypal_status'] . "</paypal_status>";
            $str_xml .= "<payment_status_id>" . $payment['payment_status_id'] . "</payment_status_id>";
            $str_xml .= "<tipo_moneda_id>" . $payment['tipo_moneda_id'] . "</tipo_moneda_id>";
            $str_xml .= "<amount>" . $payment['amount'] . "</amount>";
            //
            $str_xml .= "</payment>";
        }
        //
        $str_xml .= "</root>";


        //echo $str_xml; exit;
        return $str_xml;
    }







    //
    public static function getMailProductsInfo($arr_products){
        //
        $str_prods = "";
        //
        foreach($arr_products as $index => $concepto){
            //Helper::printFull($concepto); exit;
            //
            $str_prods .= "<strong>(x" . $concepto['qty'] . ") " . $concepto['nombre'] . "</strong><br />";
        }
        return $str_prods;
    }







    public static function SendConfirmationEmail($account_id, $app_id, $sale_id){

        //
        $sale_res = Query::single("select * from sales Where id = ?", [$sale_id]);
        //Helper::printFull($sale_res); exit;
        $customer_id = $sale_res['customer_id'];
        $customer_name = $sale_res['customer_name'];
        $customer_email = $sale_res['email'];


        //
        $customer_info = Query::single("select * from v_customers Where id = ?", [$customer_id]);        
        //Helper::printFull($customer_info); exit;
        $customer_info['id'] = $sale_id;
        $customer_info['customer_name'] = $customer_name;
        
        //
        $confirm_link = self::getConfirmationLink($sale_res['sale_code']);
        $customer_info['confirmation_link'] = "<a href='{$confirm_link}'> {$confirm_link} </a>";
        $customer_info['sale_details'] = "";

        //
        return Helper::SendEmail($account_id, $app_id, MAQUETA_ID_CONFIRM_SALE, $customer_name, $customer_email, true, function($maqueta_email_msg) use($customer_info){
            return self::ParseConfirmationLink($customer_info, $maqueta_email_msg);
        });
    }



    public static function getConfirmationLink($sale_code){
        //$domain = "http://missionexpress.us";
        $domain = "http://localhost:3000";
        return $domain."/confirm/".$sale_code;
    }


    

     //
     public static function ParseConfirmationLink($data, $template_content){
        //
        $php_microparser = new PHPMicroParser();
        //
        $sale_id = $data['id'];
        //
        $php_microparser->setVariable("id", $sale_id);
        $php_microparser->setVariable("name", $data['name']);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("sale_details", $data['sale_details']);
        $php_microparser->setVariable("phone_number", $data['phone_number']);        
        $php_microparser->setVariable("confirmation_link", $data['confirmation_link']);

        //
        return $php_microparser->parseVariables($template_content);
    }





    //
    public static function ParseCustomerMessages($data, $template_content){
        //
        $php_microparser = new PHPMicroParser();
        //
        $sale_id = $data['id'];

        //
        $url_oxxo_voucher = (isset($data['url_oxxo_voucher']) && $data['url_oxxo_voucher']) ? $data['url_oxxo_voucher'] : null;
        $email_msg_content = (isset($data['email_msg_content']) && $data['email_msg_content']) ? $data['email_msg_content'] : null;
        $name = (isset($data['name']) && $data['name']) ? $data['name'] : null;

        //
        $ctid = (isset($data['customer_type_id']) && $data['customer_type_id']) ? (int)$data['customer_type_id'] : null;
        //
        $customer_type = "";        
        //
        if ( $ctid === 1 ){
            $customer_type = "Cliente Regular";
        }
        else if ( $ctid === 2 ){
            $customer_type = "Vendedor";
        }
        
        //
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("customer_email", $data['customer_email']);
        $php_microparser->setVariable("phone_number", $data['phone_number']);
        $php_microparser->setVariable("order_details", $data['order_details']);
        $php_microparser->setVariable("customer_type", $customer_type);
        $php_microparser->setVariable("order_number", $sale_id);
        $php_microparser->setVariable("id", $sale_id);
        //
        $php_microparser->setVariable("url_oxxo_voucher", $url_oxxo_voucher);
        $php_microparser->setVariable("name", $name);
        $php_microparser->setVariable("email_msg_content", $email_msg_content);

        //
        return $php_microparser->parseVariables($template_content);
    }



    //
    public static function ParseTemplateNewSale($data, $template_content){
        //
        $php_microparser = new PHPMicroParser();
        
        //
        $sale_id = $data['id'];

        // Variables Opcionales
        $url_oxxo_voucher = (isset($data['url_oxxo_voucher']) && $data['url_oxxo_voucher']) ? $data['url_oxxo_voucher'] : null;
        $email_msg_content = (isset($data['email_msg_content']) && $data['email_msg_content']) ? $data['email_msg_content'] : null;
        $name = (isset($data['name']) && $data['name']) ? $data['name'] : null;
        $sale_details = (isset($data['sale_details']) && $data['sale_details']) ? $data['sale_details'] : null;
        $order_details = (isset($data['order_details']) && $data['order_details']) ? $data['order_details'] : null;
        $ctid = (isset($data['customer_type_id']) && $data['customer_type_id']) ? (int)$data['customer_type_id'] : null;
        $ticket_url = (isset($data['ticket_url']) && $data['ticket_url']) ? (int)$data['ticket_url'] : null;


        //
        $customer_type = "";
        //
        if ( $ctid === 1 ){
            $customer_type = "Cliente Regular";
        }
        else if ( $ctid === 2 ){
            $customer_type = "Vendedor";
        }
        
        //
        $php_microparser->setVariable("ticket_url", $ticket_url);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("email", $data['email']);
        $php_microparser->setVariable("phone_number", $data['phone_number']);
        $php_microparser->setVariable("order_details", $order_details);
        $php_microparser->setVariable("sale_details", $sale_details);
        $php_microparser->setVariable("customer_type", $customer_type);
        //
        $php_microparser->setVariable("order_number", $sale_id);
        $php_microparser->setVariable("order_id", $sale_id);
        $php_microparser->setVariable("id", $sale_id);
        $php_microparser->setVariable("sale_id", $sale_id);
        //
        $php_microparser->setVariable("url_oxxo_voucher", $url_oxxo_voucher);
        $php_microparser->setVariable("name", $name);
        $php_microparser->setVariable("email_msg_content", $email_msg_content);

        //
        return $php_microparser->parseVariables($template_content);
    }




    //
    public static function ParseDocumentTicket($data, $template_content){

        //
        $var_start = "<!--{";
        $var_end = "}-->";
        //
        $php_microparser = new PHPMicroParser($var_start, $var_end);
        
        //
        $sale_id = $data['sale_id'];

        //
        $php_microparser->setVariable("qr_img_url", $data['qr_img_url']);
        $php_microparser->setVariable("datetime_created", $data['datetime_created']);
        $php_microparser->setVariable("ticket_title", $data['ticket_title']);
        $php_microparser->setVariable("ticket_subtitle", $data['ticket_subtitle']);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("contacto_info", $data['contacto_info']);
        $php_microparser->setVariable("nombre_pasajero", $data['nombre_pasajero']);
        $php_microparser->setVariable("pasajero_info", $data['pasajero_info']);
        $php_microparser->setVariable("email", $data['customer_email']);
        $php_microparser->setVariable("arr_items", $data['arr_items']);
        $php_microparser->setVariable("phone_number", $data['customer_phone_number']);
        //
        $php_microparser->setVariable("id", $sale_id);
        $php_microparser->setVariable("sale_id", $sale_id);

        //
        return $php_microparser->parseVariables($template_content);
    }




}