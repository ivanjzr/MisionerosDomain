<?php
namespace App\Buses;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class BusesFeatures
{












    //
    public static function GetAll($app_id, $bus_id){

        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
				
                        FROM buses_features t
				
				            Left Join cat_features feats On feats.id = t.feature_id
                  
                           Where t.app_id = ?
                           And t.bus_id = ?

                        Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $bus_id
            ],
            "parse" => function(&$row){
            }
        ]);
    }












    //
    public static function AddRecord($app_id, $bus_id, $feature_id, $user_id, $active){
        //
        $results = array();
        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_BusesFeaturesAdd(?,?,?,?,?,?)}";
            },
            "debug" => false,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_TAG_ALREADY_IN_USE" => "Feature already exists"
            ],
            "params" => function() use($app_id, $bus_id, $feature_id, $user_id, $active, &$param_record_id){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($bus_id, SQLSRV_PARAM_IN),
                    array($feature_id, SQLSRV_PARAM_IN),
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
    public static function Remove($bus_id, $id){
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
            $stmt = "Delete FROM buses_features
                        Where bus_id = ?
                        And id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $bus_id,
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