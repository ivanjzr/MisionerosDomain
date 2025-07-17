<?php
namespace App\Config\ConfigStripe;



use Helpers\Query;


//
class ConfigStripe
{




    //
    public static function GetRecord($account_id){
        return Query::Single("SELECT t.* FROM config_stripe t Where t.account_id = ?
    ", [$account_id]);
    }



}