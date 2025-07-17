<?php
namespace App\Accounts;

//
use Helpers\Query;


//
class Accounts
{







    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
                    SELECT
                        t.*
                        FROM accounts t
                ";
            },
            "params" => [
            ],
            "parse" => function(){

            }
        ]);
    }







    //
    public static function GetRecordById($id){
        //
        return Query::Get([
            "stmt" => function(){
                return "SELECT t.* FROM accounts t Where t.id = ?
                ";
            },
            "params" => [
                $id
            ]
        ]);
    }





    public static function getPath($app_id){
        //
        $account_results = self::GetRecordById($app_id);
        //var_dump($account_results); exit;
        return PATH_PUBLIC.DS."sites".DS.$account_results['app_folder_name'];
    }





}
