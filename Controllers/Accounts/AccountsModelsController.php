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
class AccountsModelsController extends BaseController
{






    //
    public function ViewModels($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/accounts/models.phtml', array(
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['account_id']
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
        $account_id = $args['account_id'];
        //echo $account_id; exit;

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sys_admin_secciones",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.parent_id Is Null {$search_clause}";
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
                                      ,ROW_NUMBER() OVER (ORDER BY orden asc) as row
                                    From
                                      (Select
                                      
                                        t.*,
                                        acc.account_id,
                                        Case   
                                            When ( acc.id > 0 ) Then 1
                                            Else 0
                                        End as acct_active
                                      
                                        From {$table_name} t
                                      
                                      
                                            Left Join accounts_menus acc On ( acc.account_id = ? And acc.menu_id = t.id )
                                            
                                            Where t.parent_id Is Null
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                //
                "cnt_params" => array(
                    /* send empty */
                ),
                //
                "params" => array(
                    $account_id
                ),
                //
                "parseRows" => function(&$row) use($account_id){
                    //
                    $row['children'] = self::GetAll($account_id, $row['id']);
                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public static function GetAll($account_id, $parent_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*,
                    acc.account_id,
                    Case   
                        When ( acc.id > 0 ) Then 1
                        Else 0
                    End as acct_active
                        
                        FROM sys_admin_secciones t
                        
				           Left Join accounts_menus acc On ( acc.account_id = ? And acc.menu_id = t.id )
                        
                           Where t.parent_id = ?
			";
            },
            "params" => [
                $account_id,
                $parent_id
            ],
            "parse" => function(){

            }
        ]);
    }











    //
    public function PostSetMode($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $id = $ses_data['id'];


        //
        $results = array();



        //
        $account_id = $args['account_id'];
        //
        $menu_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$menu_id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }



        //
        $param_record_id = 0;
        $param_oper_type = 'operation_upsert_record';
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "debug" => false,
            "stmt" => function(){
                return "{call usp_EnableDisableAccountMenu(?,?,?,?)}";
            },
            "params" => function() use($menu_id, $account_id, &$param_record_id, &$param_oper_type){
                //
                return [
                    //
                    array($menu_id, SQLSRV_PARAM_IN),
                    array($account_id, SQLSRV_PARAM_IN),
                    //
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                    array(&$param_oper_type, SQLSRV_PARAM_OUT)
                ];
            },
        ]);
        //
        $results = array();
        $results['id'] = $param_record_id;
        $results['oper_type'] = $param_oper_type;
        //
        //var_dump($sp_res); var_dump($results);  exit;


        //
        return $response->withJson($results, 200);
    }







}