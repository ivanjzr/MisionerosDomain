<?php
namespace Controllers\DocsManager;

//
use App\Accounts\Accounts;
use App\DocsManager\Folders;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;
use Stripe\Account;


//
class FoldersController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/docs_manager/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }











    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.sms_msg like '%$search_value%' ) Or 
                        ( t.email_subject like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


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
                "table_name" => "docs_manager_folders",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
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
                                      
                                        t.*active
                                      
                                        From {$table_name} t
                                      
                                            Where t.app_id = ?
                                            
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
                "parseRows" => function(&$row) use($app_id){

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }










    //
    public function GetAll($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Folders::GetAll($app_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }















    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Folders::GetRecordById( $app_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function UpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $folder_name = Helper::safeVar($request->getParsedBody(), 'folder_name');
        $description = Helper::safeVar($request->getParsedBody(), 'description');




        //
        $record_id = (isset($args['id']) && $args['id']) ? (int)$args['id'] : null;
        //echo $record_id; exit;


        //
        if ( !$folder_name ){
            $results['error'] = "proporciona el nombre del folder";
            return $response->withJson($results, 200);
        }
        //
        $description = ($description) ? $description : null;




        /*
         * si es edit obtenemos el nombre original del folder para uso posterior
         * */
        $original_folder_name = null;
        if ($record_id) {
            $folder_info = Folders::GetRecordById($app_id, $record_id);
            //var_dump($folder_info); exit;
            $original_folder_name = (isset($folder_info['folder_name']) ? $folder_info['folder_name'] : null);
        }
        //echo $original_folder_name; exit;




        //
        $param_new_id = 0;
        $param_mode = "this-is-the-length";

        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_DocsManagerUpsertFolder(?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_FOLDER_ALREADY_EXISTS" => "Folder already exists",
            ],
            "debug" => false,
            "params" => function() use($folder_name, $description, $app_id, $record_id, &$param_new_id, &$param_mode){
                return [
                    //
                    array($folder_name, SQLSRV_PARAM_IN),
                    array($description, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    //
                    array($record_id, SQLSRV_PARAM_IN),
                    array(&$param_new_id, SQLSRV_PARAM_OUT),
                    array(&$param_mode, SQLSRV_PARAM_OUT)
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        if ($param_new_id){
            self::createUpdateFolder($app_id, $folder_name, $record_id, $original_folder_name, $results);
        }

        //
        $results['id'] = $param_new_id;
        $results['mode'] = $param_mode;

        //
        return $response->withJson($results, 200);
    }










    public static function createUpdateFolder($app_id, $folder_name, $record_id, $original_folder_name, &$results){
        //
        $site_path = Accounts::getPath($app_id);

        //
        $new_folder = $site_path.DS.$folder_name;

        // if is update
        if ($record_id){
            //
            $original_folder = $site_path.DS.$original_folder_name;
            //
            if (is_dir($original_folder)){
                rename($original_folder, $new_folder);
                $results['msg'] = "folder " . $original_folder . " already exists, renamed on edit to $new_folder";
            }
            // si no existe lo creamos
            else {
                mkdir($new_folder);
                $results['msg'] = "folder " . $new_folder . " created on edit Ok";
            }
        }
        // si es add
        else {
            // si ya existe mostramos el mensaje
            if (is_dir($new_folder)){
                $results['msg'] = "folder " . $new_folder . " already exists";
            }
            // si no existe lo creamos
            else {
                mkdir($new_folder);
                $results['msg'] = "folder " . $new_folder . " created on add Ok";
            }
        }
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
        $account_results = Accounts::GetRecordById($app_id);
        $folder_results = Folders::GetRecordById($app_id, $id);
        //var_dump($folder_results); exit;
        $folder_path = PATH_PUBLIC.DS."sites".DS.$account_results['app_folder_name'].DS.$folder_results['folder_name'];
        //echo $folder_path; exit;



        //
        $results['query_res'] = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM docs_manager_folders Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
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
        if (is_dir($folder_path)) {
            if (!@rmdir($folder_path)) {
                //
                $error = error_get_last();
                //
                if (preg_match('/Directory not empty/', $error['message'])) {
                    $error = 'Directorio no esta vacio';
                }
                //
                $results['error'] = $error;
                return $response->withJson($results, 200);
            }
            //
            else {
                $results['success'] = true;
            }
        } else {
            $results['error'] = $folder_path . " is not a directory";
            return $response->withJson($results, 200);
        }




        //
        return $response->withJson($results, 200);
    }




}