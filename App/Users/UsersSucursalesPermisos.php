<?php
namespace App\Users;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class UsersSucursalesPermisos
{



    
    /**
     * 
     * Admnin Menus & Sub Menus
     * 
     */
    public static function GetAdminMenus($account_id){
        //echo "$account_id"; exit;
        return Query::Multiple("select * from v_admin_permisos Where account_id = ? And parent_id is Null Order By orden Asc", [$account_id], function(&$row){
            //dd($row);
            $row['children'] = self::GetAdminSubMenus($row['account_id'], $row['id']);
        });
    }
    //
    public static function GetAdminSubMenus($account_id, $seccion_id, $prepend_model_name = ""){
        $sub_menus = Query::Multiple("select t.* from v_admin_permisos t Where t.account_id = ? And t.parent_id = ? Order By orden Asc", [$account_id, $seccion_id]);
        
        //
        if ($prepend_model_name && $sub_menus && is_array($sub_menus)){
            foreach($sub_menus as $index => $menu){
                //dd($menu);
                $sub_menus[$index]['model_path'] = $prepend_model_name.".".$menu['model_name'];
            }
        }
        
        //dd($sub_menus);
        return $sub_menus;
    }


    /**
     * 
     * User Menus & Sub Menus
     * 
     */
    public static function GetUserMenus($account_id, $sucursal_id, $user_id){
        //echo "$account_id, $sucursal_id, $user_id"; exit;
        return Query::Multiple("select * from v_user_sucursales_permisos Where account_id = ? And parent_id is Null And sucursal_id = ? And user_id = ? Order By orden Asc", [$account_id, $sucursal_id, $user_id], function(&$row){
            //dd($row);
            $row['children'] = self::GetUserSubMenus($row['account_id'], $row['sucursal_id'], $row['user_id'], $row['id']);
        });
    }
    //
    public static function GetUserSubMenus($account_id, $sucursal_id, $user_id, $parent_id, $prepend_model_name = ""){
        $sub_menus = Query::Multiple("select t.* from v_user_sucursales_permisos t Where t.account_id = ? And t.sucursal_id = ? And t.user_id = ? And t.parent_id = ? Order By orden Asc", [$account_id, $sucursal_id, $user_id, $parent_id]);
        
        //
        if ($prepend_model_name && $sub_menus && is_array($sub_menus)){
            foreach($sub_menus as $index => $menu){
                //dd($menu);
                $sub_menus[$index]['model_path'] = $prepend_model_name.".".$menu['model_name'];
            }
        }

        //dd($sub_menus);
        return $sub_menus;
    }

    


    

    //
    public static function userHasPermission($account_id, $sucursal_id, $user_id, $perm_type = ""){
        //echo " $account_id, $sucursal_id, $user_id $perm_type"; exit;
        if ($perm_type){
            //
            $user_permission = Query::Single(
                "SELECT * FROM v_user_sucursales_permisos t 
                WHERE t.account_id = ? AND t.sucursal_id = ? AND t.user_id = ? AND t.model_name = ?", 
                [$account_id, $sucursal_id, $user_id, $perm_type]
            );
            //dd($user_permission);
            if(isset($user_permission['id']) && ($user_permission['todos'] || $user_permission['agregar'])){
                return $user_permission;
            }
        }
        //
        return false;
    }
    
    



    /**
     * 
     * Checamos permisos del usuario
     * 1 - si es admin pasa, caso contrario:
     * 2 - busca en la tabla de users sucursales, si la sucursal tiene permisos:
     * 3 - si es admin (tipo todos) regresa true, caso contrario busca permisos especificos con userHasPermission 
     * 4 - si encuentra es true, caso contario false
     */
    public static function checkUserPerm($account_id, $sucursal_id, $user_data, $perm_type){
        //
        $user_id = $user_data['id'];
        //
        // si es admin pasamos directamente
        if ( $user_data['is_admin'] ){
            //echo "es admin"; exit;
            return true;
        } 
        // si no es admin validamos permisos
        else {            
            //echo "no es admin"; exit;
            //
            $user_sucursales = Query::Single("select * from users_sucursales Where account_id = ? And sucursal_id = ? And user_id = ?", [$account_id, $sucursal_id, $user_id]);
            //dd($user_sucursales);
            if ( !isset($user_sucursales['id']) ){
                //echo "no tiene user_sucursales"; exit;
                //$results['error'] = "no se encontro el usuario";return $response->withJson($results, 200);
                return false;
            }
            $tipos_permisos = $user_sucursales['tipos_permisos'];
            //echo $tipos_permisos; exit;
            if ( $tipos_permisos == TIPO_PERMISO_ID_TODOS){
                //echo "todos los permisos"; exit;
                return true;
            } 
            //
            else if ( $tipos_permisos == TIPO_PERMISO_ID_ESPECIFICOS){
                //echo "permisos especificos"; exit;
                if ( self::userHasPermission($account_id, $sucursal_id, $user_id, $perm_type) ){
                    return true;
                }   
            }
        }
        //
        return false;
    }





    /*
     *
     * ESPECIFICO PARA JSTREEVIEW
     * SE TRAE TODOS LOS PERMISOS DE LA SUCURSAL DEL USUARIO
     *
     * */
    public static function GetPermisos($account_id, $user_id, $sucursal_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
                    select 

                        m.id,
                        m.parent_id,
                        m.nombre as text,
                               
                        Case 
                            When perm.todos = 1 Then 1
                            Else 0
                        End as checked,
                               
                        perm.user_id,
                        perm.sucursal_id
                        
                            from accounts_menus t
                                
                                Left Join sys_admin_secciones m On ( m.id = t.menu_id )
                                Left Join users_sucursales_permisos perm ON ( perm.account_id = ? And perm.user_id = ? And perm.sucursal_id = ? And perm.seccion_id = m.id )
                
                                Where t.account_id = ?
                                And m.parent_id Is Null

                            Order By m.orden Asc
                ";
            },
            "params" => [
                $account_id,
                $user_id,
                $sucursal_id,
                $account_id
            ],
            "parse" => function(&$row) use($account_id, $user_id, $sucursal_id){
                //
                $row['children'] = self::GetSubPermisos($account_id, $user_id, $sucursal_id, $row['id']);
            }
        ]);
    }
    //
    public static function GetSubPermisos($account_id, $user_id, $sucursal_id, $parent_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
                    select 

                        m.id,
                        m.parent_id,
                        m.nombre as text,
                               
                        Case 
                            When perm.todos = 1 Then 1
                            Else 0
                        End as checked,
                               
                        perm.user_id,
                        perm.sucursal_id
                        
                            from accounts_menus t
                                
                                Left Join sys_admin_secciones m On ( m.id = t.menu_id )
                                Left Join users_sucursales_permisos perm ON ( perm.account_id = ? And perm.user_id = ? And perm.sucursal_id = ? And perm.seccion_id = m.id )
                
                                Where t.account_id = ?
                                And m.parent_id = ?
                ";
            },
            "params" => [
                $account_id,
                $user_id,
                $sucursal_id,
                $account_id,
                $parent_id
            ]
        ]);
    }















    //
    public static function upsertPermisos($account_id, $user_id, $sucursal_id, $secciones_ids){

        //
        $param_updated_rows = 0;
        //
        //var_dump($arr_conceptos); exit;
        $xml_secciones_ids = self::setXmlSecciones($secciones_ids);
        //echo $xml_secciones_ids; exit;

        //
        $res = Query::StoredProcedure([
            "ret" => "single",
            "stmt" => function(){
                return "{call usp_UpsertAdminPermisos(?,?,?,?,?)}";
            },
            "params" => function() use($account_id, $user_id, $sucursal_id, $xml_secciones_ids, &$param_updated_rows){
                return [
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array($user_id, SQLSRV_PARAM_IN),
                    array($sucursal_id, SQLSRV_PARAM_IN),
                    array($xml_secciones_ids, SQLSRV_PARAM_IN),
                    //
                    array(&$param_updated_rows, SQLSRV_PARAM_OUT),
                ];
            },
        ]);
        if (isset($res['error']) && $res['error']){
            return $res;
        }

        //
        $results = array();
        $results['id'] = $sucursal_id;
        $results['user_id'] = $user_id;
        $results['updated_rows'] = $param_updated_rows;
        //
        return $results;
    }








    //
    public static function setXmlSecciones($secciones_ids){
        //var_dump($secciones_ids); exit;

        //
        $str_xml = "<root>";

        //
        if (is_array($secciones_ids)){
            foreach($secciones_ids as $index => $seccion_id){
                //var_dump($seccion_id); exit;
                //
                $str_xml .= "<seccion_id>" . $seccion_id . "</seccion_id>";
            }
        }
        //
        $str_xml .= "</root>";


        //echo $str_xml; exit;
        return $str_xml;
    }








    
    


}
