<?php
namespace App\Orders;




//
use Helpers\Helper;
use Helpers\PHPMicroParser;
use Helpers\Query;


//
class Orders
{



    //
    public static function RequestOrder(
        $customer_id,
        $store_id,
        $order_notes,
        $arr_order
    ){



        //
        $results = array();





        //
        $xml_items = self::setXmlItems($arr_order);
        //echo $xml_items; exit;


        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_AddOrder(?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Error while creating sale"
            ],
            "debug" => true,
            "params" => function() use(
                $customer_id,
                $store_id,
                $order_notes,
                $xml_items,
                //
                &$param_record_id
            ){
                return [
                    //
                    array($customer_id, SQLSRV_PARAM_IN),
                    array($store_id, SQLSRV_PARAM_IN),
                    array($order_notes, SQLSRV_PARAM_IN),
                    array($xml_items, SQLSRV_PARAM_IN),
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




    //
    public static function setXmlItems($arr_order){
        //Helper::printFull($arr_order); exit;

        //
        $str_xml = "<root>";

        //
        foreach($arr_order as $index => $item){
            //Helper::printFull($item); exit;
            //
            $str_xml .= "<item>";
            //
            $the_qty = ( isset($item['qty']) && $item['qty'] > 0 ) ? $item['qty'] : 1;
            //
            $str_xml .= "<item_index>" . ($index+1) . "</item_index>";
            $str_xml .= "<id>" . $item['id'] . "</id>";
            $str_xml .= "<qty>" . $the_qty . "</qty>";
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
    public static function getMailProductsInfo($order_id){

        //
        $arr_products = Query::Multiple("Select * from orders_items where order_id = ?", [$order_id]);

        //
        $str_prods = "";
        //
        if ($arr_products && is_array($arr_products) ){
            foreach($arr_products as $index => $concepto){
                //Helper::printFull($concepto); exit;
                //
                $str_prods .= "<div>(x" . $concepto['qty'] . ") <strong>" . $concepto['item_info'] . "</strong> $" . $concepto['final_amount'] . "</div>";
            }
        }
        //
        return $str_prods;
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