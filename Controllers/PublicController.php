<?php
namespace Controllers;

//
use App\Config\ConfigQBO;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\QboHelper;
use Helpers\Query;

use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use  QuickBooksOnline\API\Exception\ServiceException;


//
class PublicController extends BaseController
{




    //
    // https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
    //

    

    //
    public function ViewQBORedirUrl($request, $response, $args) {
        //
        $domain_name = DOMAIN_NAME;
        if (str_contains(DOMAIN_NAME, "www")){
            $domain_name = str_replace("www.", "", DOMAIN_NAME);
        }
        //echo $domain_name; exit;
        //
        $code = $request->getQueryParam("code");
        $realmId = $request->getQueryParam("realmId");

        //$_SESSION['auth_code'] = $auth_code;
        //$_SESSION['realmId'] = $realmId;


        //
        $config = QboHelper::getQBOLoginConfig();
        //dd($config); exit;
        $dataService = QboHelper::getDataService($config);
        //dd($dataService); exit;

        //
        $token_results = QboHelper::createToken($dataService, $code, $realmId);
        //dd($token_results); exit;
        //
        if ( isset($token_results['error']) && $token_results['error'] ){
            return $response->withJson($token_results, 400);
        }        
        //
        $token_id = $token_results['id'];



        //
        return $this->container->php_view->render($response, 'admin/intuit-qbo/qbo-redir-url.phtml', [
            "token_id" => $token_id,
        ]);
    }




}