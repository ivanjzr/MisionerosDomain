<?php
namespace App\Config\ConfigClabe;



use Helpers\Query;


//
class ConfigClabe
{




    //
    public static function GetRecord($app_id){
        return Query::Single("SELECT t.* FROM config_clabe t Where t.app_id = ?", [$app_id]);
    }



}