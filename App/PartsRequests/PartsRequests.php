<?php
namespace App\PartsRequests;




//
use Helpers\Helper;
use Helpers\PHPMicroParser;
use Helpers\Query;


//
class PartsRequests
{




    //
    public static function RequestPart(
        $customer_id,
        $visitor_id,
        $marca_id,
        $modelo_anio,
        $submarca_id,
        $cilindraje_id,
        $parts_info,
        $s3_file_link
    ){

        //
        $results = array();


        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_RequestPart(?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Oops, no se pudo enviar la solicitud, un error ocurrio, contacta con YonkeParts",
                "ERR_CUSTOMER_NOT_FOUND_OR_INACTIVE" => "Customer not found or inactive",
                "ERR_DAILY_SEARCHES_REACH_LIMIT" => "Has alcanzado el limite de busquedas permitidas por dia",
                "ERR_HAS_OPENED_REQUEST" => "Para iniciar una nueva busqueda debes de cerrar o cancelar la ultima o bien actualizar tu suscripcion"
            ],
            "debug" => true,
            "params" => function() use(
                $customer_id,
                $visitor_id,
                $marca_id,
                $modelo_anio,
                $submarca_id,
                $cilindraje_id,
                $parts_info,
                $s3_file_link,
                //
                &$param_record_id
            ){
                return [
                    //
                    array($customer_id, SQLSRV_PARAM_IN),
                    array($visitor_id, SQLSRV_PARAM_IN),
                    array($marca_id, SQLSRV_PARAM_IN),
                    array($modelo_anio, SQLSRV_PARAM_IN),
                    array($submarca_id, SQLSRV_PARAM_IN),
                    array($cilindraje_id, SQLSRV_PARAM_IN),
                    array($parts_info, SQLSRV_PARAM_IN),
                    array($s3_file_link, SQLSRV_PARAM_IN),
                    //
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['id'] = $param_record_id;
        //
        return $results;
    }





    public static function insertNotific(
        $customer_id,
        $store_id,
        $customer_sent,
        $store_sent,
        $display_customer_notific,
        $display_store_notific,
        $is_chat_msg,
        $message,
        $dest_url,
        $store_part_request_id = null
    ){
        //
        return Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                    Insert Into users_notifications
                    ( customer_id, store_id, customer_sent, store_sent, display_customer_notific, display_store_notific, is_chat_msg, message, dest_url, store_part_request_id, datetime_created )
                    Values 
                    ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE() )
                    ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $customer_id,
                $store_id,
                $customer_sent,
                $store_sent,
                $display_customer_notific,
                $display_store_notific,
                $is_chat_msg,
                $message,
                $dest_url,
                $store_part_request_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);
    }











    //
    public static function ParseCustomerMessages($data, $template_content){
        //Helper::printFull($data); exit;

        //
        $php_microparser = new PHPMicroParser();
        //
        $sale_id = $data['order_id'];

        //
        $php_microparser->setVariable("order_number", $sale_id);
        $php_microparser->setVariable("order_id", $sale_id);
        $php_microparser->setVariable("store_name", $data['store_name']);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("order_details", $data['order_details']);
        $php_microparser->setVariable("email", $data['email']);
        $php_microparser->setVariable("phone_number", $data['phone_number']);
        $php_microparser->setVariable("grand_total", $data['grand_total']);
        $php_microparser->setVariable("notes", $data['notes']);

        //
        return $php_microparser->parseVariables($template_content);
    }

}