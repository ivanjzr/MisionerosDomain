<?php
namespace App\Auth;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
//
use Helpers\Helper;
use Helpers\Query;


//
class Auth
{




    //
    public static function ActivateAccount($customer_id, $activation_code){
        //
        $results = array();
        //
        $param_updated_rows = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_Auth_ActivateAccount(?,?,?)}";
            },
            "debug" => false,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_CUSTOMER_CODE_NOT_FOUND" => "Codigo incorrecto o cuenta no existente",
            ],
            "params" => function() use($customer_id, $activation_code, &$param_updated_rows){
                return [
                    //
                    array($customer_id, SQLSRV_PARAM_IN),
                    array($activation_code, SQLSRV_PARAM_IN),
                    //
                    array(&$param_updated_rows, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //Helper::printFull($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['updated_rows'] = $param_updated_rows;
        //
        return $results;
    }





    //
    public static function PostLogin($app_id, $email, $password){
        //
        $results = array();
        //
        $out_record_id = 0;
        $out_token_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "stmt" => function(){
                return "{call usp_Auth_Login(?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_CUSTOMER_DOES_NOT_EXISTS" => "No se encontro la cuenta de usuario",
                "LOGIN_NOT_SAME_APP" => "**No se encontro la cuenta de usuario",
            ],
            "debug" => false,
            "params" => function() use($app_id, $email, $password, &$out_record_id, &$out_token_id){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($email, SQLSRV_PARAM_IN),
                    array($password, SQLSRV_PARAM_IN),
                    //
                    array(&$out_record_id, SQLSRV_PARAM_OUT),
                    array(&$out_token_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //var_dump($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }

        //
        $results['id'] = $out_record_id;
        $results['token_id'] = $out_token_id;
        $results['t'] = "f";

        //
        return $sp_res;
    }




    //
    public static function PostDirectLogin($app_id, $record_id){
        //
        $results = array();
        //
        $out_token_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "debug" => true,
            "stmt" => function(){
                return "{call usp_Auth_DirectLogin(?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_CUSTOMER_DOES_NOT_EXISTS" => "No se encontro la cuenta de usuario",
                "LOGIN_NOT_SAME_APP" => "**No se encontro la cuenta de usuario",
            ],
            "params" => function() use($app_id, $record_id, &$out_token_id){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($record_id, SQLSRV_PARAM_IN),
                    array(&$out_token_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //var_dump($sp_res); exit;
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['id'] = $record_id;
        $results['token_id'] = $out_token_id;
        //
        return $sp_res;
    }







    //
    public static function RecoverUpdatePassword($app_id, $record_id, $activation_code, $password){
        //
        $results = array();
        //
        $param_updated_rows = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_Auth_UpdateRecoverPassword(?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_CUSTOMER_AC_DOES_NOT_EXISTS" => "codigo de activacion invalido o cuenta inexistente",
            ],
            "debug" => false,
            "params" => function() use($app_id, $record_id, $activation_code, $password, &$param_updated_rows){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($record_id, SQLSRV_PARAM_IN),
                    array($activation_code, SQLSRV_PARAM_IN),
                    array($password, SQLSRV_PARAM_IN),
                    //
                    array(&$param_updated_rows, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }

        //
        $results['updated_rows'] = $param_updated_rows;
        $results['id'] = $record_id;

        //
        return $results;
    }





}
