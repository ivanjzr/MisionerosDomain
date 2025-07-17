<?php
namespace App\Buses;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class ProductsPromociones
{












    //
    public static function GetAll($app_id, $product_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*,
                    (prom.descripcion + ' (' + prom.clave + ')') as promocion,
                    prom.fecha_hora_inicio,
                    prom.fecha_hora_fin
                  
                        FROM products_promociones t
                  
                            Left Join promociones prom On prom.id = t.promocion_id
                  
                           Where t.app_id = ?
                           And t.product_id = ?

                        Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $product_id
            ],
            "parse" => function(){

            }
        ]);
    }












    //
    public static function AddRecord($app_id, $product_id, $promocion_id, $user_id, $active){
        //
        $results = array();
        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_PromocionesAdd(?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_PROMOCION_ALREADY_IN_USE" => "Promo already exists"
            ],
            "params" => function() use($app_id, &$param_record_id, $product_id, $promocion_id, $user_id, $active){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($product_id, SQLSRV_PARAM_IN),
                    array($promocion_id, SQLSRV_PARAM_IN),
                    array($user_id, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }
        //
        $results['id'] = $param_record_id;
        //
        return $results;
    }









    //
    public static function Remove($app_id, $product_id, $id){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);



            //
            $stmt = "Delete FROM products_promociones
                        Where app_id = ?
                        And product_id = ?
                        And id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $app_id,
                $product_id,
                $id
            );
            //var_dump($params); exit;



            //echo $stmt; exit;
            $query_delete = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_delete) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            if ( $query_delete && is_numeric($query_delete) ){
                //
                $results['msg'] = "affected rows " . $query_delete;
                $results['id'] = $id;
            }



            //
            return $results;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }







}