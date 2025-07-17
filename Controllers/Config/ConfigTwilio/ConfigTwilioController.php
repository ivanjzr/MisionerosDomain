<?php
namespace Controllers\Config\ConfigTwilio;

//
use App\Config\ConfigTwilio\ConfigTwilio;
use Controllers\BaseController;
use App\App;
use Helpers\Helper;
use Helpers\Query;

//
class ConfigTwilioController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/config/config_twilio/index.phtml', [
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
        $results = ConfigTwilio::GetRecord($app_id);
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
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number');
        $account_sid = Helper::safeVar($request->getParsedBody(), 'account_sid');
        $auth_token = Helper::safeVar($request->getParsedBody(), 'auth_token');
        //
        $phone_number_test = Helper::safeVar($request->getParsedBody(), 'phone_number_test');
        $account_sid_test = Helper::safeVar($request->getParsedBody(), 'account_sid_test');
        $auth_token_test = Helper::safeVar($request->getParsedBody(), 'auth_token_test');
        //
        $is_prod = Helper::safeVar($request->getParsedBody(), 'is_prod');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }
        if ( !$account_sid ){
            $results['error'] = "proporciona el account sid";
            return $response->withJson($results, 200);
        }
        if ( !$auth_token ){
            $results['error'] = "proporciona el auth token";
            return $response->withJson($results, 200);
        }
        //
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$account_sid_test ){
            $results['error'] = "proporciona el account sid de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$auth_token_test ){
            $results['error'] = "proporciona el auth token de pruebas";
            return $response->withJson($results, 200);
        }



        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigTwilio(?,?,?,?,?,?,?,?,?,?)}";
            },
            "params" => function() use($app_id, &$param_record_id, $phone_number, $account_sid, $auth_token, $phone_number_test, $account_sid_test, $auth_token_test, $is_prod, $active){
                return [
                    //
                    array($phone_number, SQLSRV_PARAM_IN),
                    array($account_sid, SQLSRV_PARAM_IN),
                    array($auth_token, SQLSRV_PARAM_IN),
                    //
                    array($phone_number_test, SQLSRV_PARAM_IN),
                    array($account_sid_test, SQLSRV_PARAM_IN),
                    array($auth_token_test, SQLSRV_PARAM_IN),
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
            return $response->withJson($sp_res, 200);
        }
        //
        $update_results['id'] = $param_record_id;



        //
        return $response->withJson($update_results, 200);
    }










    //
    public function PostSendSMS($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];





        //
        $results = array();


        //
        $phone_cc = trim(Helper::safeVar($request->getParsedBody(), 'phone_cc_send'));
        $phone_number = trim(Helper::safeVar($request->getParsedBody(), 'phone_number_send'));
        $message = Helper::safeVar($request->getParsedBody(), 'message_send');
        //echo " $phone_cc $phone_number $message"; exit;


        //
        if ( !$phone_cc ){
            $results['error'] = "proporciona el codigo del pais";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }
        if ( !$message ){
            $results['error'] = "proporciona el mensaje";
            return $response->withJson($results, 200);
        }
        //
        $full_hone_number = $phone_cc.$phone_number;
        //echo $full_hone_number; exit;

        /*
        $options = [];
        $options['phone'] = "+19152333664";
        $options['sid'] = "ACfd1989a6842e282020fc1d78aa63c046";
        $options['token'] = "26b20095edcfb2c61fa6040465a3bdb1";
        */
        /*
        $customer_info = [];
        $customer_info['name'] = "Ivan";
        $customer_info['activation_code'] = "1234";
        $customer_info['phone_cc'] = "+99";
        $customer_info['phone_number'] = "999999999";
        */
        //
        $send_results = Helper::SendSMS($app_id, null, $full_hone_number, function() use($message){
            return $message;
        });
        //var_dump($send_results); exit;


        //
        return $response->withJson($send_results, 200);
    }










}