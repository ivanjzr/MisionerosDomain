<?php
namespace Controllers\Config\ConfigStripe;

//
use App\Config\ConfigStripe\ConfigStripe;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class ConfigStripeController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/config/config_stripe/index.phtml', [
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
        $results = ConfigStripe::GetRecord($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





















    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        //
        $public_key = Helper::safeVar($request->getParsedBody(), 'public_key');
        $secret_key = Helper::safeVar($request->getParsedBody(), 'secret_key');
        //
        $public_key_test = Helper::safeVar($request->getParsedBody(), 'public_key_test');
        $secret_key_test = Helper::safeVar($request->getParsedBody(), 'secret_key_test');
        //
        $is_prod = Helper::safeVar($request->getParsedBody(), 'is_prod');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$public_key ){
            $results['error'] = "proporciona el account sid";
            return $response->withJson($results, 200);
        }
        if ( !$secret_key ){
            $results['error'] = "proporciona el auth token";
            return $response->withJson($results, 200);
        }
        //
        if ( !$public_key_test ){
            $results['error'] = "proporciona el account sid de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$secret_key_test ){
            $results['error'] = "proporciona el auth token de pruebas";
            return $response->withJson($results, 200);
        }



        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigStripe(?,?,?,?,?,?,?,?)}";
            },
            "debug" => true,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($public_key, $secret_key, $public_key_test, $secret_key_test, $is_prod, $active, $account_id, &$param_record_id){
                return [
                    //
                    array($public_key, SQLSRV_PARAM_IN),
                    array($secret_key, SQLSRV_PARAM_IN),
                    array($public_key_test, SQLSRV_PARAM_IN),
                    array($secret_key_test, SQLSRV_PARAM_IN),
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