<?php
namespace App\Auth;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
//
use Helpers\Helper;
use Helpers\Query;


//
class ActivationCodes
{





    //
    public static function GetLastActivationCode($new_record_id){
        //
        return Query::Single("SELECT Top 1 t.* FROM users_activation_codes t Where t.user_id = ? And t.active = 1 Order By t.id Desc", [$new_record_id]);
    }



    //
    public static function GetActivationCode($new_record_id, $activation_code){
        //
        return Query::Single("SELECT t.* FROM users_activation_codes t 
            Left Join v_users t2 On t2.id = t.user_id 
            Where t.user_id = ? And t.activation_code = ? And t.active = 1", [$new_record_id, $activation_code]);
    }




    //
    public static function Create($account_id, $record_id, $activation_code, $request_identifier){
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "stmt" => "
               Insert Into users_activation_codes
              ( account_id, user_id, activation_code, request_identifier, active, datetime_created)
              Values
              ( ?, ?, ?, ?, 1, GETDATE())
              ;SELECT SCOPE_IDENTITY()   
            ",
            "params" => [
                $account_id,
                $record_id,
                $activation_code,
                $request_identifier
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);
        //
        return $insert_results;
    }








}
