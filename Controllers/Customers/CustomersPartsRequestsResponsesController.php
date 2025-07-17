<?php
namespace Controllers\Customers;


//
use App\Customers\Customers;
use App\PartsRequests\PartsRequests;
use App\Paths;
use App\Stores\Stores;
use Controllers\BaseController;
use Google\Service\Compute\Help;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class CustomersPartsRequestsResponsesController extends BaseController
{





    //
    public function GetStorePartRequestForCustomer($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];


        //
        $part_request_id = $args['part_request_id'];
        $deal_with_store_id = $args['deal_with_store_id'];
        //
        $results = Query::Single("Select Top 1 t.* from ViewStoresPartsRequests t Where t.customer_id = ? And t.part_request_id = ? And t.store_id = ? Order By t.id Desc", [$customer_id, $part_request_id, $deal_with_store_id]);

        //
        return $response->withJson($results, 200);
    }










    //
    public function PaginateScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewStoresPartsRequests";

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
        $part_request_id = $args['part_request_id'];



        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t
                                --
                                Where t.part_request_id = ? 
                                And t.customer_id = ? 
                                --   
                                And EXISTS (
                                    SELECT 1
                                    FROM users_notifications k
                                    WHERE k.store_part_request_id = t.id
                                    And k.store_sent = 1
                                )
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
                                        
                                      t.*,
                                      (select count(*) from users_notifications j where j.store_part_request_id = t.id And j.store_sent = 1 ) as cant 
                                        
                                        From {$table_name} t
                                            -- 
                                            Where t.part_request_id = ? 
                                            And t.customer_id = ?
                                            --   
                                            And EXISTS (
                                                SELECT 1
                                                FROM users_notifications k
                                                WHERE k.store_part_request_id = t.id
                                                And k.store_sent = 1
                                            )
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $part_request_id,
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;

                    //
                    if ( isset($row['store_img_ext']) && $row['store_img_ext']  && ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['store_img_ext']) ) ){
                        $row['biz_logo'] = $biz_logo;
                        unset($row['store_img_ext']);
                    }


                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }










}
