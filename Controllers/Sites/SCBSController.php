<?php
//
namespace Controllers\Sites;

//
use App\Site\SiteConfig;
use Controllers\BaseController;
use App\App;
use App\Config\ConfigSquare\ConfigSquare;

$appId = 1234;

//
class SCBSController extends BaseController
{

    //
    public function ViewHome($request, $response, $args) {
        //
        $arr_content = [];
        $arr_content['config'] = ConfigSquare::getConfig(accountId);
        
        //
        return $this->container->php_view->render($response, PATH_REL_VIEWS . DS .'index.phtml', array_merge([
            "App" => new App("en-us"),
            "appId" => appId,
            "hostUrl" => hostUrl,
            "appName" => appName,
            "fbAppId" => fbAppId,
            "site_config" => SiteConfig::GetRecordById(appId)
        ], $arr_content));
    }

    //
    public function ViewContact($request, $response, $args) {
        //
        $arr_content = [];
        $arr_content['config'] = ConfigSquare::getConfig(accountId);
        
        //
        return $this->container->php_view->render($response, PATH_REL_VIEWS . DS .'contact.phtml', array_merge([
            "App" => new App("en-us"),
            "appId" => appId,
            "hostUrl" => hostUrl,
            "appName" => appName,
            "fbAppId" => fbAppId,
            "site_config" => SiteConfig::GetRecordById(appId)
        ], $arr_content));
    }
}