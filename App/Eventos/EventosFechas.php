<?php
namespace App\Eventos;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class EventosFechas
{












    //
    public static function GetAll($app_id, $evento_id){

        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
				
                        FROM eventos_fechas t
                  
                  
                           Where t.app_id = ?
                           And t.evento_id = ?

                        Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $evento_id
            ],
            "parse" => function(){

            }
        ]);
    }












    //
    public static function AddRecord($app_id, $evento_id, $event_date, $description, $user_id){
        //
        $results = array();
        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_EventosFechasAdd(?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_FECHA_IN_USE" => "Ingredient already exists"
            ],
            "params" => function() use($app_id, $evento_id, $event_date, $description, $user_id, &$param_record_id){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($evento_id, SQLSRV_PARAM_IN),
                    array($event_date, SQLSRV_PARAM_IN),
                    array($description, SQLSRV_PARAM_IN),
                    //
                    array($user_id, SQLSRV_PARAM_IN),
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
    public static function Remove($app_id, $evento_id, $id){
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
            $stmt = "Delete FROM eventos_fechas
                        Where app_id = ?
                        And evento_id = ?
                        And id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $app_id,
                $evento_id,
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