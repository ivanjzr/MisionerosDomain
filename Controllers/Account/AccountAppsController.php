<?php
namespace Controllers\Account;


//
use App\Orders\Orders;
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
class AccountAppsController extends BaseController
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


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $customer_id = $args['customer_id'];
        //echo $customer_id; exit;


        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];
        //echo $order_direction; exit;


        //
        $table_name = "v_customer_people";
        //echo $table_name; exit;



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
                    return "Select COUNT(*) total From {$table_name} t Where app_id = ? and customer_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause){
                    //echo $where_row_clause; exit;
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
                                      
                                           
                                            Where app_id = ?
                                            And customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $customer_id
                ),

                //
                "parseRows" => function(&$row){
                    //
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }





    //
    public function PaginateRecordsPrimeReact($request, $response, $args) {

        //
        $table_name = "v_customer_people";

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
        $selected_store_id = (int)$request->getQueryParam("sid");
        if ( is_numeric($selected_store_id) && $selected_store_id ){
            $search_clause .= " And t.store_id = {$selected_store_id}";
        }
        //echo $search_clause; exit;



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
                    //
                }
            ]
        );


        //dd($results); exit;
        return $response->withJson($results, 200);
    }




    //
    public function GetRelativesList($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];

        
        
        /**
         * 
         * Si customer viene de args es request de admin
         * caso contrario es de la sesion osea del api customer
         */
        //       
        if ( isset($args['customer_id']) && $args['customer_id'] ){
            $customer_parent_id = $args['customer_id'];
        } else {
            $customer_parent_id = $ses_data['id'];
        }
        //echo $customer_parent_id; exit;

        
        //
        $results = Query::Multiple("SELECT * FROM v_customer_people where app_id = ? and customer_parent_id = ? Order by Id Desc", [$app_id, $customer_parent_id]);
        //
        return $response->withJson($results, 200);
    }






    //
    public function GetRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];

        //
        $customer_parent_id = $args['customer_id'];
        //
        $results = Query::Single("SELECT * FROM v_customer_people where app_id = ? and customer_parent_id = ? ", [$app_id, $customer_parent_id]);
        //
        return $response->withJson($results, 200);
    }





    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();

        

        //
        $v = new ValidatorHelper();

        $body = $request->getParsedBody();



        /**
         * 
         * Si customer viene de args es request de admin
         * caso contrario es de la sesion osea del api customer
         */
        //       
        if ( isset($args['customer_id']) && $args['customer_id'] ){
            $customer_id = $args['customer_id'];
        } else {
            $customer_id = $ses_data['id'];
        }
        //echo $customer_id; exit;


        //
        $person_name = Helper::safeVar($body, 'person_name');
        $relative_id = Helper::safeVar($body, 'relative_id');
        $birth_date = Helper::safeVar($body, 'birth_date');
        $active = Helper::safeVar($body, 'active') ? 1 : 0;
        


        //
        if ( !$person_name ){
            $results['error'] = "Provide person name"; return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($relative_id) ){
            $results['error'] = "Provide relative id"; return $response->withJson($results, 200);
        }



        //
        if (!$birth_date) {
            $results['error'] = "La fecha de nacimiento es obligatoria"; 
            return $response->withJson($results, 200);
        }

        // Validar formato de fecha
        //echo $birth_date; exit;
        $birth_date_obj = \DateTime::createFromFormat('!Y-m-d', $birth_date);
        if (!$birth_date_obj) {
            $results['error'] = "Formato de fecha de nacimiento inválido"; 
            return $response->withJson($results, 200);
        }
        //dd($birth_date_obj);

        //
        $today = new \DateTime();
        //
        $diff = $today->diff($birth_date_obj);
        $edad_years = $diff->y;
        $age_months = $diff->m;
        $age_days = $diff->d;
        //
        //echo "Edad: " . $edad_years . " años"; exit;



        
        $person_res = Query::Single("Select * from customer_people Where app_id = ? and customer_id = ? and person_name = ? and relative_id = ?", [$app_id, $customer_id, $person_name, $relative_id]);
        //dd($person_res); 
        if ($person_res && isset($person_res['id'])){
            $results['error'] = "Ya existe un familiar con el mismo tipo y nombre"; 
            return $response->withJson($results, 200);
        }


        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into customer_people
                  ( app_id, customer_id, person_name, relative_id, birth_date, edad_years, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                ",
            "params" => [
                $app_id,
                $customer_id,
                $person_name,
                $relative_id,
                $birth_date,
                $edad_years,
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
        //
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();

        //
        $record_id = $args['id'];






        //
        $v = new ValidatorHelper();

        $body = $request->getParsedBody();

        //
        $person_name = Helper::safeVar($body, 'person_name');
        $relative_id = Helper::safeVar($body, 'relative_id');
        $birth_date = Helper::safeVar($body, 'birth_date');
        $active = Helper::safeVar($body, 'active') ? 1 : 0;
        



        /**
         * 
         * Si customer viene de args es request de admin
         * caso contrario es de la sesion osea del api customer
         */
        //       
        if ( isset($args['customer_id']) && $args['customer_id'] ){
            $customer_id = $args['customer_id'];
        } else {
            $customer_id = $ses_data['id'];
        }
        //echo $customer_id; exit;



        //
        if ( !$person_name ){
            $results['error'] = "Provide person name"; return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($relative_id) ){
            $results['error'] = "Provide relative id"; return $response->withJson($results, 200);
        }



        //
        if (!$birth_date) {
            $results['error'] = "La fecha de nacimiento es obligatoria"; 
            return $response->withJson($results, 200);
        }

        // Validar formato de fecha
        //echo $birth_date; exit;
        $birth_date_obj = \DateTime::createFromFormat('!Y-m-d', $birth_date);
        if (!$birth_date_obj) {
            $results['error'] = "Formato de fecha de nacimiento inválido"; 
            return $response->withJson($results, 200);
        }
        //dd($birth_date_obj);

        //
        $today = new \DateTime();
        //
        $diff = $today->diff($birth_date_obj);
        $edad_years = $diff->y;
        $age_months = $diff->m;
        $age_days = $diff->d;
        //
        //echo "Edad: " . $edad_years . " años"; exit;

        
        



        $person_res = Query::Single("Select * from customer_people Where app_id = ? and customer_id = ? And person_name = ? And relative_id = ? And id != ?", [$app_id, $customer_id, $person_name, $relative_id, $record_id]);
        //dd($person_res); 
        if ($person_res && isset($person_res['id'])){
            $results['error'] = "Ya existe un familiar con el mismo tipo y nombre"; 
            return $response->withJson($results, 200);
        }

        

        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update customer_people
                       Set
                        person_name = ?,
                        relative_id = ?,
                        birth_date = ?,
                        edad_years = ?
                        
                      Where app_id = ?
                      And customer_id = ?
                      And id = ?
                      
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $person_name,
                $relative_id,
                $birth_date,
                $edad_years,
                //
                $app_id,
                $customer_id,
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
        //
        $results = array();


        /**
         * 
         * Si customer viene de args es request de admin
         * caso contrario es de la sesion osea del api customer
         */
        //       
        if ( isset($args['customer_id']) && $args['customer_id'] ){
            $customer_id = $args['customer_id'];
        } else {
            $customer_id = $ses_data['id'];
        }
        //echo $customer_id; exit;



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
            "stmt" => "Delete FROM customer_people Where app_id = ? And customer_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $customer_id,
                $id
            ],
            "parse" => function($updated_rows, &$query_results) use($id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$id;
            }
        ]);
        //dd($results); exit;
        return $response->withJson($results, 200);
    }




}
