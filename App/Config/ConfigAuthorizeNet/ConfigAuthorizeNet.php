<?php
namespace App\Config\ConfigAuthorizeNet;

//
use Helpers\Query;


//
class ConfigAuthorizeNet
{




    //
    public static function GetRecord($account_id){
        return Query::Single("SELECT t.* FROM config_authorizenet t Where t.account_id = ?", [$account_id]);
    }

}