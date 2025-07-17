<?php
namespace Controllers\Site;

//
use App\Site\SiteConfig;
use App\Site\SitePages;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class SiteConfigController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/site/config.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }




    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = SiteConfig::GetRecordById($app_id);
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
        $site_title = Helper::safeVar($request->getParsedBody(), 'site_title');
        $email = Helper::safeVar($request->getParsedBody(), 'email');
        $address = Helper::safeVar($request->getParsedBody(), 'address');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number');
        $phone_number_2 = Helper::safeVar($request->getParsedBody(), 'phone_number_2');
        //
        $facebook_url = Helper::safeVar($request->getParsedBody(), 'facebook_url');
        $twitter_url = Helper::safeVar($request->getParsedBody(), 'twitter_url');
        $youtube_url = Helper::safeVar($request->getParsedBody(), 'youtube_url');
        $linkedin_url = Helper::safeVar($request->getParsedBody(), 'linkedin_url');
        $instagram_url = Helper::safeVar($request->getParsedBody(), 'instagram_url');




        //
        if ( !$site_title ){
            $results['error'] = "proporciona el titulo de la pagina";
            return $response->withJson($results, 200);
        }



        //
        $param_new_id = 0;
        $param_mode = "this-is-the-length";

        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertSiteConfig(?,?,?,?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => true,
            "params" => function() use($site_title, $email, $address, $phone_number, $phone_number_2, $facebook_url, $twitter_url, $youtube_url, $linkedin_url, $instagram_url, $app_id, &$param_new_id, &$param_mode){
                return [
                    //
                    array($site_title, SQLSRV_PARAM_IN),
                    array($email, SQLSRV_PARAM_IN),
                    array($address, SQLSRV_PARAM_IN),
                    array($phone_number, SQLSRV_PARAM_IN),
                    array($phone_number_2, SQLSRV_PARAM_IN),
                    //
                    array($facebook_url, SQLSRV_PARAM_IN),
                    array($twitter_url, SQLSRV_PARAM_IN),
                    array($youtube_url, SQLSRV_PARAM_IN),
                    array($linkedin_url, SQLSRV_PARAM_IN),
                    array($instagram_url, SQLSRV_PARAM_IN),
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    //
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
        $results['id'] = $param_new_id;
        $results['mode'] = $param_mode;

        //
        return $response->withJson($results, 200);
    }





}