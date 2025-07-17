<?php
namespace App\Config\ConfigSquare;



use Helpers\Query;


//
class ConfigSquare
{




    //
    public static function GetRecord($account_id){
        return Query::Single("SELECT t.* FROM config_square t Where t.account_id = ?", [$account_id]);
    }




    public static function getConfig($account_id){
        
        //
        $square_app_id = null;
        $square_loc_id = null;
        $square_js_url = null;
        $square_mode = null;
        //
        $square_info = self::GetRecord($account_id);

        // DEBUG MODE
        $square_info['active'] = 1; $square_info['is_prod'] = 0;

        //
        if ( isset($square_info['id']) && $square_info['id'] && $square_info['active']){
            //
            if (isset($square_info['is_prod']) && $square_info['is_prod']){
                $square_app_id = $square_info['prod_app_id'];
                $square_loc_id = $square_info['prod_loc_id'];
                //$square_js_url = "https://js.squareup.com/v2/paymentform";
                $square_js_url = "https://web.squarecdn.com/v1/square.js";
                $square_mode = "prod";
            } else {
                $square_app_id = $square_info['dev_app_id'];
                $square_loc_id = $square_info['dev_loc_id'];
                //$square_js_url = "https://js.squareupsandbox.com/v2/paymentform";
                $square_js_url = "https://sandbox.web.squarecdn.com/v1/square.js";
                $square_mode = "dev";
            }
        }


        //
        return array(
            'app_id' => $square_app_id,
            'loc_id' => $square_loc_id,
            'js_url' => $square_js_url,
            'mode' => $square_mode
        );
    }




}