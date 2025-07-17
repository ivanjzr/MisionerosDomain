<?php
namespace App\Eventos;


//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use Helpers\Query;


//
class Eventos
{














    //
    public static function GetAll($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
                  
                        FROM eventos t
                
                           Where t.app_id = ?
                           And t.active = 1
			";
            },
            "params" => [
                $app_id
            ],
            "parse" => function(){

            }
        ]);
    }








    //
    public static function GetRecordById($app_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM eventos t
                  
                        Where t.app_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $id
            ],
            "parse" => function(&$row) use($app_id){


                //
                $files_path = Paths::$path_eventos . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //echo $main_files_path; exit;
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_eventos . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_eventos . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }


            }
        ]);
    }








    //
    public static function UpdateImgExt($options){
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
                    eventos
                  
                  Set
                    img_ext = ?
                  
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                //
                $options['img_ext'],
                //
                $options['app_id'],
                $options['evento_id']
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
            $results['id'] = $options['evento_id'];



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