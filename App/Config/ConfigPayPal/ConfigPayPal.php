<?php
namespace App\Config\ConfigPayPal;


//
use Helpers\Query;


//
class ConfigPayPal
{





    //
    public static function GetRecord($app_id){
        return Query::Single("SELECT t.* FROM config_paypal t Where t.app_id = ?", [$app_id]);
    }




}