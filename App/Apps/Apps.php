<?php
namespace App\Apps;

//
use Helpers\Query;


//
class Apps
{


    //
    public static function GetApp(){
        //echo $domain_name; exit;
        //
        $domain_name = DOMAIN_NAME;
        if (str_contains(DOMAIN_NAME, "www")){
            $domain_name = str_replace("www.", "", DOMAIN_NAME);
        }
        //echo $domain_name; exit;
        //
        return Query::Single("Select * From accounts_apps Where ( domain_prod = ? Or domain_prod2 = ? Or domain_dev = ? Or domain_dev2 = ? )", [$domain_name, $domain_name, $domain_name, $domain_name], function(&$row) use($domain_name){
            $row['current_host'] = $domain_name;
        });
        //var_dump($res); exit;
    }




}
