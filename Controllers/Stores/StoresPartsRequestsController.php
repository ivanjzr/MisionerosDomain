<?php
namespace Controllers\Stores;


//
use App\Customers\Customers;
use App\PartsRequests\PartsRequests;
use App\Paths;
use App\Stores\Stores;
use Controllers\BaseController;
use Google\Service\Compute\Help;
use Google\Service\Reseller\Customer;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class StoresPartsRequestsController extends BaseController
{







    //
    public function PaginateScrollRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];


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
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        $filter_type = $request->getQueryParam("t");
        //echo " $filter_type "; exit;


        //
        if ( $filter_type === "new" ){
            $search_clause .= " 
                And t.cancel_datetime is null
                And t.deal_datetime is null   
                And ( t.status_id Is Null Or t.status_id = 1 )
            ";
        }
        //
        else if ( $filter_type === "others" ){
            $search_clause .= " And (
                ( t.cancel_datetime is not null ) Or
                ( t.deal_datetime is not null ) Or 
                ( t.status_id In (2, 3, 4) )
            )
            ";
        }






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
                                      
                                        t.*,
                                        (Select count(*) from users_notifications k where k.store_part_request_id = t.id And k.store_id = t.store_id And customer_sent = 1) as cant_sent_by_customer,
                                        (Select count(*) from users_notifications k where k.store_part_request_id = t.id And k.store_id = t.store_id And store_sent = 1) as cant_sent_by_store
                                           
                                        
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
                    if ( isset($row['customer_img_ext']) && $row['customer_img_ext']  && ( $customer_profile_img = Customers::getCustomerProfilePic($row['customer_id'], $row['customer_img_ext']) ) ){
                        $row['profile_img'] = $customer_profile_img;
                        unset($row['customer_img_ext']);
                    }

                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








    //
    public function GetRecordById($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];


        //
        $record_id = $args['id'];
        //
        $results = Query::Single("
            Select
                
                *,
                -- (Select count(*) from users_notifications k where k.store_part_request_id = t.id And k.store_id = t.store_id And customer_sent = 1) as cant_sent_by_customer,
                (Select count(*) from users_notifications k where k.store_part_request_id = t.id And k.store_id = t.store_id And store_sent = 1) as cant_sent_by_store
                    
                    --
                    from ViewStoresPartsRequests t 
                        --
                        Where t.store_id = ? 
                        And t.id = ?", [
            $store_id,
            $record_id
        ]);

        //
        return $response->withJson($results, 200);
    }








    //
    public function PostUpdateStatus($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];





        //
        $results = array();


        //
        $store_part_request_id = Helper::safeVar($request->getParsedBody(), 'id');
        $part_request_id = Helper::safeVar($request->getParsedBody(), 'part_request_id');
        $status_type = Helper::safeVar($request->getParsedBody(), 'status_type');

        //
        if ( !is_numeric($store_part_request_id) ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($part_request_id) ){
            $results['error'] = "proporciona el part_request_id";
            return $response->withJson($results, 200);
        }


        //
        $arr_items = ['type_avail', 'type_not_avail', 'type_ignore'];
        //
        if ( !in_array($status_type, $arr_items)){
            $results['error'] = "Se requiere un tipo de mensaje valido";
            return $response->withJson($results, 200);
        }




        //
        $status_id = null;
        //
        switch ($status_type) {
            case "type_avail":
                $status_id = 1;
                break;
            case "type_not_avail":
                $status_id = 2;
                break;
            case "type_ignore":
                $status_id = 4;
                break;
            default:
                $status_id = null;
        }
        //echo " $status_type $status_id "; exit;




        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into stores_parts_requests_status
                  ( part_request_id, store_part_request_id, status_id, datetime_created )
                  Values
                  ( ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $part_request_id,
                $store_part_request_id,
                $status_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        //
        return $response->withJson($insert_results, 200);
    }





}
