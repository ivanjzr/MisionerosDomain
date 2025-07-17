<?php
namespace Helpers;


//

use App\Config\ConfigTwilio\ConfigTwilio;
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;
use Twilio\Rest\Client;

use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Exception\ServiceException;




class QboHelper {

    
    

    //
    public static function getQBOLoginConfig(){
        //
        $info = Query::Single("Select Top 1 * from config_quickbooks Where active = 1");
        //Helper::printFull($info); exit;
        //
        $client_id = null;
        $client_secret = null;
        $redirect_url = null;
        //
        if ($info && isset($info['id']) && $info['active']){
            //
            if ($info['is_prod']){
                //
                $client_id = $info['prod_client_id'];
                $client_secret = $info['prod_client_secret'];
                $redirect_url = $info['prod_redirect_url'];
            } else {
                //
                $client_id = $info['dev_client_id'];
                $client_secret = $info['dev_client_secret'];
                $redirect_url = $info['dev_redirect_url'];
            }
        }
        //echo "$client_id $client_secret $redirect_url"; exit;        
        return array(
            'authorizationRequestUrl' => 'https://appcenter.intuit.com/connect/oauth2',
            'tokenEndPointUrl' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'oauth_redirect_uri' => $redirect_url,
            'oauth_scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
        );
    }




    //
    public static function getQbOApiCallsConfig($realm_id, $access_token, $refresh_token){
        //
        $info = Query::Single("Select Top 1 * from config_quickbooks Where active = 1 order by id desc");
        //Helper::printFull($info); exit;
        //
        $client_id = null;
        $client_secret = null;
        $redirect_url = null;
        //
        if ($info && isset($info['id']) && $info['active']){
            //
            if ($info['is_prod']){
                //
                $client_id = $info['prod_client_id'];
                $client_secret = $info['prod_client_secret'];
                $redirect_url = $info['prod_redirect_url'];
            } else {
                //
                $client_id = $info['dev_client_id'];
                $client_secret = $info['dev_client_secret'];
                $redirect_url = $info['dev_redirect_url'];
            }
        }
        //echo "$client_id $client_secret $redirect_url"; exit;        
        return array(
            'auth_mode' => 'oauth2',
            'ClientID' => $client_id,
            'ClientSecret' =>  $client_secret,
            'scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
            'accessTokenKey'  => $access_token,
            'refreshTokenKey' => $refresh_token,
            'QBORealmID'      => $realm_id,
            'baseUrl' => "development",
        );
    }



    public static function insertQbToken($realmId, $accessTokenValue, $refreshTokenValue){
        //
        $active = 1;
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                Insert Into quickbooks_tokens
                ( realm_id, access_token, refresh_token, active, datetime_created )
                Values
                ( ?, ?, ?, ?, GETDATE() )
                ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $realmId,
                $accessTokenValue,
                $refreshTokenValue,
                $active,
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);
        //
        return $insert_results;
    }



    public static function updateRevokeToken($token_id){
        //
        return Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update quickbooks_tokens                  
                  Set
                    active = 0,
                    revoke_datetime = GETDATE()
                   
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $token_id,
            ],
            "parse" => function($updated_rows, &$query_results) use($token_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$token_id;
            }
        ]);
    }



    //
    public static function createToken($dataService, $code, $realmId){
         //
         try {

            //
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            //Helper::printFull($OAuth2LoginHelper); exit;

            // AQUI LO INTERCAMBIA POR EL CODE
            $accessTokenObj = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);
            $accessTokenValue = $accessTokenObj->getAccessToken();
            $refreshTokenValue = $accessTokenObj->getRefreshToken();

            //
            $error = $OAuth2LoginHelper->getLastError();
            //Helper::printFull($error); exit;

            //
            $res = [];

            //
            if($error){

                //            
                $res['error'] = "Error while creating token";
                return $res;
                

            } else {
            
                //
                return self::insertQbToken($realmId, $accessTokenValue, $refreshTokenValue);
                
            }
            
        }
        catch (\Exception $ex){
            //var_dump($ex->getMessage()); exit;
            $res['error'] = $ex->getMessage();
            return $res;
        }

    }



    public static function checkAccessToken($dataService){
        //
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        //Helper::printFull($OAuth2LoginHelper); exit;
        //
        $accessTokenObj = $OAuth2LoginHelper->refreshToken();
        //

    }


    //
    public static function refreshToken($dataService, $realmId){
         //
         try {
         
            //
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            //Helper::printFull($OAuth2LoginHelper); exit;

            // AQUI LO ACTUALIZA
            $accessTokenObj = $OAuth2LoginHelper->refreshToken();
            $accessTokenValue = $accessTokenObj->getAccessToken();
            $refreshTokenValue = $accessTokenObj->getRefreshToken();
            //Helper::printFull($accessTokenValue); exit;

            //
            $error = $OAuth2LoginHelper->getLastError();
            //Helper::printFull($error); exit;

            $res = [];

            if($error){

                //
                $res['error'] = "Error while creating token";
                return $res;

            } else {

                //Refresh Token is called successfully
                $dataService->updateOAuth2Token($accessTokenObj);
                //Helper::printFull($accessTokenObj); exit;
                //
                return self::insertQbToken($realmId, $accessTokenValue, $refreshTokenValue);

            }

        }
        catch (\Exception $ex){
            //var_dump($ex->getMessage()); exit;
            $res['error'] = $ex->getMessage();
            return $res;
        }
    }



     // 
     public static function revokeToken($dataService, $token_id, $accessOrRefreshToken){ 
        //
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper(); 
        //Helper::printFull($OAuth2LoginHelper); exit; 
        //
        $res = [];
        //
        try {
            //
            $revokeResult = $OAuth2LoginHelper->revokeToken($accessOrRefreshToken);
            if($revokeResult){           
                //
                $revok_results = self::updateRevokeToken($token_id);
                $res['message'] = "Token revoked Ok";
                return $res;
            } else {
                $res['error'] = "Error unable to revoke token";
                return $res;
            }

        }
        catch (\Exception $ex){
            //var_dump($ex->getMessage()); exit;
            $res['error'] = $ex->getMessage();
            return $res;
        }

    }




    public static function getTokenInfo($token_id) {        
        //Helper::printFull($config); exit;
        //
        $qbo_res = Query::Single("Select * from quickbooks_tokens where id = ? order by id desc", [$token_id]);
        //Helper::printFull($qbo_res); exit;
        return $qbo_res;
    }


    public static function getLastToken() {        
        //Helper::printFull($config); exit;
        //
        $qbo_res = Query::Single("Select Top 1 * from quickbooks_tokens where active = 1 order by id desc", []);
        //Helper::printFull($qbo_res); exit;
        return $qbo_res;
    }



    public static function getDataService($config) {        
        //Helper::printFull($config); exit;
        //
        return DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' =>  $config['client_secret'],
            'RedirectURI' => $config['oauth_redirect_uri'],
            'scope' => $config['oauth_scope'],
            'baseUrl' => "Development"
        ));
    }


}

