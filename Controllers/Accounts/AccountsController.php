<?php
namespace Controllers\Accounts;

//
use App\Accounts\Accounts;
use App\Paths;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class AccountsController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //echo "test"; exit;
        //
        return $this->container->php_view->render($response, 'supadmin/accounts/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }
    //
    public function ViewEdit($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/accounts/edit.phtml', array(
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['id']
        ));
    }












    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.company_name like '%$search_value%' ) Or
                        ( t.contact_name like '%$search_value%' ) 
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {
        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);
        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "accounts",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where 1=1 {$search_clause}";
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
                                      
                                        t.*
                                      
                                        From {$table_name} t
                                      
                                            Where 1=1
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                ),

                //
                "parseRows" => function(&$row){
                    /**/
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }














    //
    public function GetAll($request, $response, $args) {

        //
        $results = Accounts::GetAll();
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }











    //
    public function GetRecord($request, $response, $args) {

        //
        $results = Accounts::GetRecordById( $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;


        //
        $results = array();




        //
        $company_name = Helper::safeVar($request->getParsedBody(), 'company_name');
        $contact_name = Helper::safeVar($request->getParsedBody(), 'contact_name');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        if ( !$company_name ){
            $results['error'] = "proporciona el company_name";
            return $response->withJson($results, 200);
        }
        //
        $notes = ($notes) ? $notes : null;
        $contact_name = ($contact_name) ? $contact_name : null;






        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "stmt" => "
                   Insert Into accounts
                  ( company_name, contact_name, notes, active, datetime_created )
                  Values
                  ( ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $company_name,
                $contact_name,
                $notes,
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


        //
        $results = array();



        //
        $company_name = Helper::safeVar($request->getParsedBody(), 'company_name');
        $contact_name = Helper::safeVar($request->getParsedBody(), 'contact_name');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        if ( !$company_name ){
            $results['error'] = "proporciona el company_name";
            return $response->withJson($results, 200);
        }
        //
        $notes = ($notes) ? $notes : null;
        $contact_name = ($contact_name) ? $contact_name : null;





        //
        $record_id = $args['id'];

        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    accounts
                  
                  Set 
                    --
                    company_name = ?,
                    contact_name = ?,
                    notes = ?,
                    active = ?
                    
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $company_name,
                $contact_name,
                $notes,
                $active,
                //
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
            "debug" => false,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "FK_account_menus_accounts" => "No se puede eliminar el registro por que tiene menus asociados"
            ],
            "stmt" => "Delete FROM accounts Where id = ?;SELECT @@ROWCOUNT",
            "params" => [
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




}