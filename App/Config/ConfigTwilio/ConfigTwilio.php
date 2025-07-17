<?php
namespace App\Config\ConfigTwilio;


//
use Helpers\Query;


//
class ConfigTwilio
{




    //
    public static function GetRecord($account_id){
        return Query::Single("
                    SELECT t.* FROM config_twilio t Where t.account_id = ?
    ", [$account_id]);
    }




}