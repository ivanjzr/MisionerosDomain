<?php
namespace Controllers\Notifications;


//
use App\App;
use App\Catalogues\CatCategories;
use App\Customers\Customers;
use App\Notifications\Notifications;
use App\Stores\Stores;
use App\Products\Subscriptions;
use App\Ventas\Ventas;
use App\Ventas\VentasItems;
use App\Ventas\VentasPayments;
use Helpers\Helper;
use Controllers\BaseController;
use Helpers\Query;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;



//
class NotificationsController extends BaseController
{







    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        return $this->container->php_view->render($response, 'admin/notifications/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }




    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Notifications::GetRecordById( $app_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }








    //
    public static function strWhereNotificationsFilter($search_value, $status_id, $week_date, $filter_sale_type_id){
        //
        $search_clause = "";
        //
        if ( $status_id ){
            $search_clause .= " And t.status_id = $status_id ";
        }

        //
        if ( $filter_sale_type_id ){
            $search_clause .= " And t.sale_type_id = $filter_sale_type_id ";
        }

        //
        if ( $week_date ){

            //
            $date_end = \DateTime::createFromFormat("Y-m-d" ,$week_date);
            $date_end = $date_end->modify("+6 days")->format("Y-m-d");
            //var_dump($date_end->format("Y-m-d")); exit;

            //
            $search_clause .= " And (
                    (  Convert(date, t.datetime_created) >= '$week_date' And Convert(date, t.datetime_created) <= '$date_end' )    
                )";
        }




        //
        if ( $search_value ){
            $search_clause = " And (
                        ( t.customer_name like '%$search_value%' ) Or 
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' )  
                    )";
        }



        //
        $search_clause = "";


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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        //echo $user_id; exit;



        //
        $tipo_producto_servicio_id = $request->getQueryParam("filter_tipo_producto_servicio_id");






        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::strWhereNotificationsFilter(
                trim($request->getQueryParam("search")['value']),
                $request->getQueryParam("filter_status_id"),
                $request->getQueryParam("filter_week_date"),
                $request->getQueryParam("filter_sale_type_id"),
            ),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "notifications",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //echo $search_clause; exit;
                    return "Select COUNT(*) total From {$table_name} t Where 1 = 1 {$search_clause}";
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
                                      
                                        t.*,
                                        (Select Count(*) From notifications_dismissed Where app_id = t.app_id And notification_id = t.id ) as cant_dismissed
                                        
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
                    $app_id
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
    public function PostSendMsg($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


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
        $arr_sales = Ventas::GetAllPhitPhuel($app_id, $start_date_obj->format("Y-m-d"), $end_date_obj->format("Y-m-d"), null, null, null, null, $selected_ids);
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
            $status_res = Query::Single("SELECT Top 1 t.*, t2.status_title FROM sales_status t Left Join cat_sale_status t2 On t2.id = t.status_id Where t.app_id = ? And t.sale_id = ? Order By t.id Desc", [$app_id, $sale_info['id'], $status_id]);
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
                  ( app_id, sale_id, status_id, status_notes, user_id, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
                    "params" => [
                        $app_id,
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
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();


        //
        $title = Helper::safeVar($request->getParsedBody(), 'title');
        $message = Helper::safeVar($request->getParsedBody(), 'message');
        $send_type = Helper::safeVar($request->getParsedBody(), 'send_type');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$title ){
            $results['error'] = "proporciona el title";
            return $response->withJson($results, 200);
        }
        if ( !$message ){
            $results['error'] = "proporciona el message";
            return $response->withJson($results, 200);
        }
        if ( !($send_type === "c" || $send_type === "s") ){
            $results['error'] = "proporciona un tipo de envio valido";
            return $response->withJson($results, 200);
        }


        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into notifications
                  ( app_id, title, message, send_type, active, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id,
                $title,
                $message,
                $send_type,
                $active
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        //
        return $response->withJson($insert_results, 200);
    }

















    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $title = Helper::safeVar($request->getParsedBody(), 'title');
        $message = Helper::safeVar($request->getParsedBody(), 'message');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$title ){
            $results['error'] = "proporciona el title";
            return $response->withJson($results, 200);
        }
        if ( !$message ){
            $results['error'] = "proporciona el message";
            return $response->withJson($results, 200);
        }





        //
        $record_id = $args['id'];







        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    notifications
                  
                  Set
                    title = ?,
                    message = ?,
                    active = ?
                   
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $title,
                $message,
                $active,
                //
                $app_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }












    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
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
            "stmt" => "Delete FROM notifications Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
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









    //
    public function PaginateScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewNotifications";

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
        $sale_type_id = $ses_data['sale_type_id'];


        //
        $type = null;
        $customer_id = null;
        $store_id = null;
        //
        if ( $sale_type_id === PROD_TYPE_CUSTOMER_ID ){
            $type = "customer";
            $customer_id = $ses_data['id'];
            $store_id = $args['other_id'];
        }
        //
        else if ( $sale_type_id === PROD_TYPE_STORE_ID ){
            $type = "store";
            $store_id = $ses_data['id'];
            $customer_id = $args['other_id'];
        }
        //echo "$type $customer_id $store_id"; exit;


        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");


        //echo " $order_field $order_direction "; exit;



        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t
                                --
                                Where t.customer_id = ?
                                And t.store_id = ?
                                --
                                {$search_clause}";
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
                                        
                                            -- 
                                            Where t.customer_id = ?
                                            And t.store_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id,
                    $store_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;

                    //
                    if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['store_img_ext']) ){
                        $row['biz_logo'] = $biz_logo;
                        unset($row['store_img_ext']);
                    }
                    //
                    if ( $profile_img = Customers::getCustomerProfilePic($row['customer_id'], $row['customer_img_ext'])){
                        $row['profile_img'] = $profile_img;
                        unset($row['customer_img_ext']);
                    }

                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







}