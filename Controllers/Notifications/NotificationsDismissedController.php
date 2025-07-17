<?php
namespace Controllers\Notifications;


//
use Helpers\Helper;
use Controllers\BaseController;
use Helpers\Query;



//
class NotificationsDismissedController extends BaseController
{



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
        $notification_id = $args['id'];
        //echo $notification_id; exit;



        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            null,
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "notifications_dismissed",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.notification_id = ? {$search_clause}";
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
                                        (t2.company_name + ' ' + t2.store_title) as store_name,
                                        (t3.name + ' ' + t3.last_name) as customer_name
                                        
                                        From {$table_name} t
                                      
                                            Left Join ViewStores t2 On t2.id = t.store_id
                                            Left Join v_customers t3 On t3.id = t.customer_id
                                      
                                            Where t.app_id = ?
                                            And t.notification_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $notification_id
                ),

                //
                "parseRows" => function(&$row) {
                    //
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }






    //
    public function PostDismissStoreNotification($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $store_id = $ses_data['id'];


        //
        $results = array();


        //
        $notification_id = $args['id'];



        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into notifications_dismissed
                  ( app_id, notification_id, store_id, datetime_created )
                  Values
                  ( ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id,
                $notification_id,
                $store_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        //
        return $response->withJson($insert_results, 200);
    }




    //
    public function PostDismissCustomerNotification($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $customer_id = $ses_data['id'];


        //
        $results = array();


        //
        $notification_id = $args['id'];



        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into notifications_dismissed
                  ( app_id, notification_id, customer_id, datetime_created )
                  Values
                  ( ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id,
                $notification_id,
                $customer_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        //
        return $response->withJson($insert_results, 200);
    }











}