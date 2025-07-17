<?php
namespace Controllers\Customers;


//
use Controllers\BaseController;
use Helpers\Helper;
//
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class CustomersNotificationsController extends BaseController
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
        $table_name = "users_notifications";
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


        //echo " $order_field $order_direction "; exit;






        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($search_clause){
                    //
                    return "Select COUNT(*) total From dbo.udf_GetCustomersNotifications(?) t {$search_clause}";
                },
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (
                                          Select * From dbo.udf_GetCustomersNotifications(?) t
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
        $table_name = "users_notifications";

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


        //dd($results); exit;
        return $response->withJson($results, 200);
    }














    //
    public function GetRecordById($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];


        //
        $coupon_id = $args['id'];
        //
        $results = Query::Single("Select * from coupons Where customer_id = ? And id = ?", [$customer_id, $coupon_id]);

        //
        return $response->withJson($results, 200);
    }






    //
    public function postSetCountOpenned($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $customer_id = $ses_data['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update users_notifications 
                       Set
                           
						is_opened = 1
						   
                      Where customer_id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $customer_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        return $response->withJson($update_results, 200);
    }



    //
    public function postSetAsViewed($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $company_id = $ses_data['company_id'];
        $customer_id = $ses_data['id'];



        $body_data = $request->getParsedBody();


        //
        $v = new ValidatorHelper();
        $notification_id = $v->safeVar($body_data, 'id');
        //echo $notification_id; exit;


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update users_notifications 
                       Set
                           
						view_datetime = GETDATE()
						   
                      Where customer_id = ?
                      and id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $customer_id,
                $notification_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        return $response->withJson($update_results, 200);
    }









}
