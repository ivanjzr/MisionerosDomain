<?php
namespace App\Locations;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;
use Helpers\Query;


//
class CatCiudades
{







    //
    public static function GetAll($estado_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*
                        
                        FROM sys_cat_ciudades t
                        
                           Where t.estado_id = ?
                           And t.active = 1
			";
            },
            "params" => [
                $estado_id
            ],
            "parse" => function(){

            }
        ]);
    }










    //
    public static function GetRecordById($estado_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM sys_cat_ciudades t
                  
                        Where t.estado_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $estado_id,
                $id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }


















    //
    public static function Create($options){
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
            $stmt = "
                  Insert Into sys_cat_ciudades
                  ( estado_id, nombre, abreviado, active )
                  Values
                  ( ?, ?, ?, 1)
                  ;SELECT SCOPE_IDENTITY()   
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                $options['estado_id'],
                $options['nombre'],
                $options['abreviado']
            );
            //print_r($params); exit;


            //echo $stmt; exit;
            $query_insert = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_insert) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }

            //
            if ( $query_insert && is_numeric($query_insert) ){
                $results['id'] = $query_insert;
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









    //
    public static function Edit($options){
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
            $stmt = "
                  Update 
                    sys_cat_ciudades
                  
                  Set
                    nombre = ?,
                    abreviado = ?
                  
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                //
                $options['nombre'],
                $options['abreviado'],
                //
                $options['id']
            );
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query_update = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_update) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            $results['affected_rows'] = $query_update;
            $results['id'] = $options['id'];



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








    //
    public static function Remove($estado_id, $id){
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
            $stmt = "Delete FROM sys_cat_ciudades
                        Where estado_id = ?
                        And id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $estado_id,
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