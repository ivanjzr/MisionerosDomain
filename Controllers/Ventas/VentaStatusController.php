<?php
namespace Controllers\Ventas;


//
use App\Catalogues\CatSalesStatus;
use App\Utils\Utils;
use App\Ventas\VentasItems;
use App\Ventas\VentasPayments;
use Helpers\Helper;
use Controllers\BaseController;
use Helpers\Query;



//
class VentaStatusController extends BaseController
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


        //
        $sale_id = $args['id'];
        //echo $sale_id; exit;


        //
        $update_type = $request->getQueryParam("ut");


        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value']), $request->getQueryParam("filter_status_id"), $request->getQueryParam("filter_store_id"), $request->getQueryParam("filter_week_date")),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sales_status",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.sale_id = ? {$search_clause}";
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
                                        t2.sale_status,
                                        t2.status_title,
                                        te.nombre username
                                        
                                        From {$table_name} t
                                      
                                            Left Join cat_sale_status t2 On t2.id = t.status_id
                                            Left Join empleados te On te.id = t.user_id
                                      
                                            Where t.sale_id = ?
                                            
                                            {$search_clause}
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
                "parseRows" => function(&$row) use($update_type){
                    //
                    //CatSalesStatus::renameStatusTitle($update_type, $row);
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }












    //
    public function PostUpdateStatus($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $status_id = (int)Helper::safeVar($request->getParsedBody(), 'status_id');
        $status_notes = Helper::safeVar($request->getParsedBody(), 'status_notes');
        //
        if ( !is_numeric($status_id) ){
            $results['error'] = "proporciona el status";
            return $response->withJson($results, 200);
        }
        //
        $status_notes = ($status_notes) ? $status_notes : null;

        //
        $sale_id = $args['id'];



        //
        $status_res = Query::Single("SELECT Top 1 t.*, t2.status_title FROM sales_status t Left Join cat_sale_status t2 On t2.id = t.status_id Where t.sale_id = ? Order By t.id Desc", [$sale_id, $status_id]);
        //dd($status_res); exit;
        if ( isset($status_res['id']) && (int)$status_res['status_id'] === (int)$status_id ){
            $results['error'] = "Cannot update to same status '" . $status_res['status_title'] . "'";
            return $response->withJson($results, 200);
        }




        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into sales_status
                  ( sale_id, status_id, status_notes, user_id, datetime_created )
                  Values
                  ( ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $sale_id,
                $status_id,
                $status_notes,
                $user_id
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        //
        if ($status_id===1){
            //
            $insert_results['update_payment_completed'] = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                   Update 
                    sales_payments
                  
                  Set 
                    --
                    payment_status_id = 1,
                    payment_status = 'Completed'
                    
                  Where sale_id = ?
                  ;SELECT @@ROWCOUNT
                ",
                "params" => [
                    $sale_id
                ],
                "parse" => function($updated_rows, &$query_results) use($sale_id){
                    $query_results['affected_rows'] = $updated_rows;
                    $query_results['id'] = (int)$sale_id;
                }
            ]);
        } else {
            //
            $insert_results['update_payment_pending'] = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                   Update 
                    sales_payments
                  
                  Set 
                    --
                    payment_status_id = 2,
                    payment_status = 'Pending'
                    
                  Where sale_id = ?
                  ;SELECT @@ROWCOUNT
                ",
                "params" => [
                    $sale_id
                ],
                "parse" => function($updated_rows, &$query_results) use($sale_id){
                    $query_results['affected_rows'] = $updated_rows;
                    $query_results['id'] = (int)$sale_id;
                }
            ]);
        }




        //
        return $response->withJson($insert_results, 200);
    }










}