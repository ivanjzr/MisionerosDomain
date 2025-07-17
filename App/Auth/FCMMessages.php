<?php
namespace App\Auth;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;




//
class FCMMessages
{








    //
    public static function CreateMessage($worker_id, $msg, $sent_id, $sender_id = null, $sender_type = null){
        global $app;


        //echo " $worker_id, $token "; exit;

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
                  Insert Into workers_fcm_msgs
                  ( 
                    worker_id, msg, sent_id, sender_id, 
                    sender_type, datetime_created 
                  )
                  Values
                  ( 
                    ?,?,?,?,
                    ?,GETDATE() 
                  );
                  SELECT SCOPE_IDENTITY();
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                $worker_id, $msg, $sent_id, $sender_id,
                $sender_type
            );
            //var_dump($params); exit;


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








}
