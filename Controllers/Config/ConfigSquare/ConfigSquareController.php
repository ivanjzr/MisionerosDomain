<?php
namespace Controllers\Config\ConfigSquare;

//
use App\Config\ConfigSquare\ConfigSquare;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class ConfigSquareController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/config/config_square/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }








    public function getConfig($request, $response, $args){
        //
        $ses_data = $request->getAttribute("ses_data");
        //\Helpers\dd($ses_data); exit;
    
        //
        $account_id =  $ses_data['account_id'];
        $config = ConfigSquare::getConfig($account_id);

        
        //
        return $response->withJson($config, 200);
    }









    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];

        //
        $results = ConfigSquare::GetRecord($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





















    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];



        //
        $results = array();


        //
        $prod_app_id = Helper::safeVar($request->getParsedBody(), 'prod_app_id');
        $prod_loc_id = Helper::safeVar($request->getParsedBody(), 'prod_loc_id');
        $prod_access_token = Helper::safeVar($request->getParsedBody(), 'prod_access_token');
        //
        $dev_app_id = Helper::safeVar($request->getParsedBody(), 'dev_app_id');
        $dev_loc_id = Helper::safeVar($request->getParsedBody(), 'dev_loc_id');
        $dev_access_token = Helper::safeVar($request->getParsedBody(), 'dev_access_token');
        //
        $is_prod = Helper::safeVar($request->getParsedBody(), 'is_prod');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$prod_app_id ){
            $results['error'] = "proporciona el account sid";
            return $response->withJson($results, 200);
        }
        if ( !$prod_loc_id ){
            $results['error'] = "proporciona el location id";
            return $response->withJson($results, 200);
        }
        if ( !$prod_access_token ){
            $results['error'] = "proporciona el auth token";
            return $response->withJson($results, 200);
        }
        //
        if ( !$dev_app_id ){
            $results['error'] = "proporciona el account sid de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$dev_loc_id ){
            $results['error'] = "proporciona el location id de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$dev_access_token ){
            $results['error'] = "proporciona el auth token de pruebas";
            return $response->withJson($results, 200);
        }
        //echo $prod_access_token; exit;



        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigSquare(?,?,?,?,?,?,?,?,?,?)}";
            },
            "debug" => true,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($prod_app_id, $prod_loc_id, $prod_access_token, $dev_app_id, $dev_loc_id, $dev_access_token, $is_prod, $active, $account_id, &$param_record_id){
                return [
                    //
                    array($prod_app_id, SQLSRV_PARAM_IN),
                    array($prod_loc_id, SQLSRV_PARAM_IN),
                    array($prod_access_token, SQLSRV_PARAM_IN),
                    //
                    array($dev_app_id, SQLSRV_PARAM_IN),
                    array($dev_loc_id, SQLSRV_PARAM_IN),
                    array($dev_access_token, SQLSRV_PARAM_IN),
                    //
                    array($is_prod, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            $results['error'] = $sp_res['error'];
            return $response->withJson($results, 200);
        }
        //
        $results['id'] = $param_record_id;
        //
        return $response->withJson($results, 200);
    }













}