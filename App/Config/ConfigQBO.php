<?php
namespace App\Config;



use Helpers\Query;


//
class ConfigQBO
{




    //
    public static function GetRecord($account_id){
        return Query::Single("SELECT t.* FROM config_quickbooks t Where t.account_id = ?", [$account_id]);
    }



}