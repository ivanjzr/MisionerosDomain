<?php
namespace Controllers\Config\ConfigPayPal;

//
use App\Config\ConfigPayPal\ConfigPayPal;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class ConfigPayPalController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/config/config_paypal/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }














    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = ConfigPayPal::GetRecord($app_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }









    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        //
        $client_id = Helper::safeVar($request->getParsedBody(), 'client_id');
        $client_secret = Helper::safeVar($request->getParsedBody(), 'client_secret');
        //
        $client_id_test = Helper::safeVar($request->getParsedBody(), 'client_id_test');
        $client_secret_test = Helper::safeVar($request->getParsedBody(), 'client_secret_test');
        //
        $is_prod = Helper::safeVar($request->getParsedBody(), 'is_prod');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$client_id ){
            $results['error'] = "proporciona el account sid";
            return $response->withJson($results, 200);
        }
        if ( !$client_secret ){
            $results['error'] = "proporciona el auth token";
            return $response->withJson($results, 200);
        }
        //
        if ( !$client_id_test ){
            $results['error'] = "proporciona el account sid de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$client_secret_test ){
            $results['error'] = "proporciona el auth token de pruebas";
            return $response->withJson($results, 200);
        }



        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigPayPal(?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($client_id, $client_secret, $client_id_test, $client_secret_test, $is_prod, $active, $app_id, &$param_record_id){
                return [
                    //
                    array($client_id, SQLSRV_PARAM_IN),
                    array($client_secret, SQLSRV_PARAM_IN),
                    array($client_id_test, SQLSRV_PARAM_IN),
                    array($client_secret_test, SQLSRV_PARAM_IN),
                    //
                    array($is_prod, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($app_id, SQLSRV_PARAM_IN),
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