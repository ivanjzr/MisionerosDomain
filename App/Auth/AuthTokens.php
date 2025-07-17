<?php
namespace App\Auth;

//
use App\Customers\Customers;
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Stores\Stores;
use Helpers\Helper;
use Helpers\Query;

//
class AuthTokens
{






    //
    public static function GetAccountByToken($token){
        //
        return Query::Single("
                        SELECT
                                
                            t2.id,
                            t2.account_id,
                            t2.app_id,
                            ap.app_name acct_app_name,
                            'f' as t,
                            t.id as token_id
                            
                                --
                                FROM users_auth_tokens t
                                
                                Left Join v_users t2 On t2.id = t.user_id
                                Left Join accounts_apps ap On ap.id = t2.app_id
                                
                                --
                                Where t.token = ?
                                
                                -- donde el token este activos
                                And t2.active = 1
                                And t.active = 1
                                ",[
            $token
        ], function(&$row){
            //Helper::printFull($row); exit;
        });
    }









    //
    public static function DisableAuthToken($app_id, $record_id, $token_id){
        //
        $update_results = Query::DoTask([
        "task" => "update",
        "stmt" => "
            Update 
                customers_auth_tokens
            
            Set
                active = 0
            
            
            Where 
                customer_id = ?

            And 
                id = ?
            ;SELECT @@ROWCOUNT
        ",
        "params" => [
            $record_id,
            $token_id
        ],
        "parse" => function($updated_rows, &$query_results) use($token_id){
            $query_results['affected_rows'] = $updated_rows;
            $query_results['id'] = (int)$token_id;
        }
    ]);
    //
    return $update_results;
    }






}
