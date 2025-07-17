<?php
namespace Controllers\Users;

//
use App\Users\UsersSucursalesPermisos;

use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;


//
class UsersSucursalesController extends BaseController
{







    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' ) 
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        //echo $account_id; exit;


        //
        $user_id = $args['user_id'];

        
        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);
        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            \Helpers\Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sucursales",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From sucursales t Where t.account_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
                    global $account_id;
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.id,
                                        t.account_id,
                                        t.name as sucursal,
                                        es.user_id,
                                        t.address,
                                        es.tipos_permisos
                                      
                                        From sucursales t
                                      
                                            Left Join users_sucursales es On (es.sucursal_id = t.id And es.user_id = ?)
                                      
                                            Where t.account_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "cnt_params" => array(
                    $account_id
                ),

                //
                "params" => array(
                    $user_id,
                    $account_id
                ),

                //
                "parseRows" => function(&$row)use($account_id){
                    //dd($row); exit;
                    //echo $user_id; exit;

                    //
                    $row['permisos'] = self::getUserSucursalPermisos($account_id, $row['id'], $row['user_id']);
                    //dd($row);

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }




    public static function getUserSucursalPermisos($account_id, $sucursal_id, $user_id){

        return Query::Multiple("
            Select 

                t.id,
                t.account_id,
                --
                t.clave,
                t.nombre,
                t.fa_icon,
                --
                t.parent_id,
                t2.clave as parent_clave,
                t2.nombre as parent_nombre,
                --
                t.todos,
                t.leer,
                t.agregar,
                t.editar,
                t.eliminar,
                t.cancelar
                
                --
                from v_user_sucursales_permisos t

                    -- select * from v_account_sections 
                    Left Join v_account_sections t2 On (t2.account_id = t.account_id And t2.id = t.parent_id)
                
                Where t.account_id = ?
                And t.sucursal_id = ?
                And t.user_id = ?
                And (
                    ( t.todos = 1) Or (t.leer = 1 Or t.agregar = 1 Or t.editar = 1 Or t.eliminar= 1 Or t.cancelar = 1 )
                )
        ", [$account_id, $sucursal_id, $user_id]);
    }




}
