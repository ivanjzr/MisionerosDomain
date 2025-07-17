<?php
namespace Controllers\Ventas;


//
use App\Utils\Utils;
use App\Ventas\Ventas;
use App\Ventas\VentasItems;
use App\Ventas\VentasPayments;
use Helpers\Helper;
use Controllers\BaseController;
use Helpers\PHPMicroParser;
use Helpers\Query;



//
class VentaNotificationsController extends BaseController
{








    //
    public static function configSearchClause($search_value, $status_id, $store_id, $week_date){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.description like '%$search_value%' )
                    )";
        }
        //
        if ( $status_id ){
            $search_clause .= " And t.status_id = $status_id ";
        }
        //
        if ( $store_id ){
            $search_clause .= " And t.pickup_store_id = $store_id ";
        }
        //
        if ( $week_date ){

            //
            $date_end = \DateTime::createFromFormat("Y-m-d" ,$week_date);
            $date_end = $date_end->modify("+6 days")->format("Y-m-d");
            //var_dump($date_end->format("Y-m-d")); exit;

            $search_clause .= " And (
                    (  Convert(date, t.datetime_created) >= '$week_date' And Convert(date, t.datetime_created) <= '$date_end' )    
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
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $sale_id = $args['id'];
        //echo $sale_id; exit;



        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value']), $request->getQueryParam("filter_status_id"), $request->getQueryParam("filter_store_id"), $request->getQueryParam("filter_week_date")),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sales_msgs",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.sale_id = ? {$search_clause}";
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
                                      
                                        t.*,
                                        t3.nombre msg_template_name,
                                        te.nombre username
                                        
                                        From {$table_name} t
                                      
                                            Left Join maquetas_mensajes t2 On t2.id = t.msg_id
                                            Left Join sys_maquetas t3 On t3.id = t2.maqueta_id
                                            Left Join empleados te On te.id = t.user_id
                                      
                                            Where t.app_id = ?
                                            And t.sale_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $sale_id
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
    public function PostSendMsg($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();





        //
        $msg_id = Helper::safeVar($request->getParsedBody(), 'msg_id');
        $msg = Helper::safeVar($request->getParsedBody(), 'msg');
        //
        if ( !(is_numeric($msg_id) && $msg) ){
            $results['error'] = "proporciona el mensaje";
            return $response->withJson($results, 200);
        }
        //
        $sale_id = $args['id'];



        //
        $sale_info = Ventas::GetRecord($app_id, $sale_id);
        //dd($sale_info); exit;

        //
        $phone_number = $sale_info['phone_number'];
        //
        $parsed_msg = null;


        //
        $send_sms = Helper::SendSMS($app_id, MAQUETA_ID_SALE_UPDTD, $phone_number, function($maqueta_sms_msg) use(&$parsed_msg, $sale_info){
            // set parsed msg as referenced var
            $parsed_msg = self::ParseSaleMsg1($sale_info, $maqueta_sms_msg);
            return $parsed_msg;
        });
        //
        $msg_twilio_id = null;
        if ( $send_sms && isset($send_sms['main_msg']) && isset($send_sms['main_msg']['id']) ){
            $msg_twilio_id = $send_sms['main_msg']['id'];
        }
        //echo $msg_twilio_id; exit;



        //
        $insert_results = Query::DoTask([
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
                $sale_id,
                $msg_id,
                $parsed_msg,
                $msg_twilio_id,
                $user_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);



        //
        $insert_results['send_sms'] = $send_sms;


        //
        return $response->withJson($insert_results, 200);
    }





    //
    public static function ParseSaleMsg1($data, $template_content){
        //dd($data); exit;
        //
        $php_microparser = new PHPMicroParser();
        //
        $php_microparser->setVariable("order_id", $data['id']);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("email", $data['email']);
        $php_microparser->setVariable("phone_number", $data['phone_number']);
        $php_microparser->setVariable("address", $data['address']);
        $php_microparser->setVariable("pickup_store", $data['pickup_store']);
        $php_microparser->setVariable("status_title", $data['status_title']);
        //
        return $php_microparser->parseVariables($template_content);
    }









}