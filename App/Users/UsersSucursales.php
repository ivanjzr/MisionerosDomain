<?php
namespace App\Users;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class UsersSucursales
{







    //
    public static function GetAll($account_id, $user_id){
        //
        return Query::All([
            "debug" => true,
            "stmt" => function(){
                return "
                    SELECT

                        t.sucursal_id as id,
                        suc.name name,
                        suc.address

                        FROM users_sucursales t
                  
                            Left Join sucursales suc On suc.id = t.sucursal_id
                        
                           Where t.account_id = ?
                           And t.user_id = ?
                           And ( t.tipos_permisos = 1 Or t.tipos_permisos = 2 )
                ";
            },
            "params" => [
                $account_id,
                $user_id
            ]
        ]);
    }







    //
    public static function GetSucursalPermisos($account_id, $user_id, $sucursal_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                  
                      FROM users_sucursales t
                      
                        Where t.account_id = ?
                        And t.user_id = ?
                        And t.sucursal_id = ?
                ";
            },
            "params" => [
                $account_id,
                $user_id,
                $sucursal_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }













    //
    public static function IsValidForUser($account_id, $user_id, $sucursal_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*,
                    ts.name as sucursal
                  
                      FROM users_sucursales t
                  
                  
                        Left Join sucursales ts On ts.id = t.sucursal_id
                      
                  
                        Where t.account_id = ?
                        And t.user_id = ?
                        And t.sucursal_id = ?
                          
                        And ( t.tipos_permisos = ? Or t.tipos_permisos = ? )
                ";
            },
            "params" => [
                $account_id,
                $user_id,
                $sucursal_id,
                TIPO_PERMISO_ID_TODOS,
                TIPO_PERMISO_ID_ESPECIFICOS
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }














    //
    public static function UpsertUserSucursal($account_id, $user_id, $sucursal_id, $tipos_permisos){

        //
        $param_record_id = 0;
        $param_oper_type = 'operation_add_edit';


        //
        Query::StoredProcedure([
            "debug" => true,
            "stmt" => function(){
                return "{call usp_UpsertUserSucursal(?,?,?,?,?,?)}";
            },
            "params" => function() use($account_id, $user_id, $sucursal_id, $tipos_permisos, &$param_record_id, &$param_oper_type){
                return [
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array($user_id, SQLSRV_PARAM_IN),
                    array($sucursal_id, SQLSRV_PARAM_IN),
                    array($tipos_permisos, SQLSRV_PARAM_IN),
                    //
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                    array(&$param_oper_type, SQLSRV_PARAM_OUT)
                ];
            },
        ]);

        //
        $results = array();
        $results['id'] = $param_record_id;
        $results['oper_type'] = $param_oper_type;
        //
        return $results;
    }




}
