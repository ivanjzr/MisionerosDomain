<?php
namespace Controllers\Config\ConfigAuthorizeNet;

//
use App\Config\ConfigAuthorizeNet\ConfigAuthorizeNet;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class ConfigAuthorizeNetController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/config/config_authorizenet/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }














    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $results = ConfigAuthorizeNet::GetRecord($account_id);
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
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        //
        $login_id = Helper::safeVar($request->getParsedBody(), 'login_id');
        $trans_key = Helper::safeVar($request->getParsedBody(), 'trans_key');
        //
        $login_id_test = Helper::safeVar($request->getParsedBody(), 'login_id_test');
        $trans_key_test = Helper::safeVar($request->getParsedBody(), 'trans_key_test');
        //
        $is_prod = Helper::safeVar($request->getParsedBody(), 'is_prod');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$login_id ){
            $results['error'] = "proporciona el login id";
            return $response->withJson($results, 200);
        }
        if ( !$trans_key ){
            $results['error'] = "proporciona el trans key";
            return $response->withJson($results, 200);
        }
        //
        if ( !$login_id_test ){
            $results['error'] = "proporciona el login id de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$trans_key_test ){
            $results['error'] = "proporciona el trans key de pruebas";
            return $response->withJson($results, 200);
        }




        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigAuthorizeNet(?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($login_id, $trans_key, $login_id_test, $trans_key_test, $is_prod, $active, $account_id, &$param_record_id){
                return [
                    //
                    array($login_id, SQLSRV_PARAM_IN),
                    array($trans_key, SQLSRV_PARAM_IN),
                    array($login_id_test, SQLSRV_PARAM_IN),
                    array($trans_key_test, SQLSRV_PARAM_IN),
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
            $results['id'] = $sp_res['error'];
            return $response->withJson($results, 200);
        }


        //
        $results['id'] = $param_record_id;


        //
        return $response->withJson($results, 200);
    }













}