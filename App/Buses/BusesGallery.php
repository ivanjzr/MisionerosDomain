<?php
namespace App\Buses;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class BusesGallery
{








    //
    public static function GetAll($app_id, $bus_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				select 

                    t.*
                  
                    from buses_images t
                
                        Where t.app_id = ?
                        And t.bus_id = ?
			";
            },
            "params" => [
                $app_id,
                $bus_id
            ],
            "parse" => function(&$row){


                //
                $bus_id = $row['bus_id'];
                $img_gallery_id = $row['id'];
                $img_ext = $row['img_ext'];
                //
                $bus_path = Paths::$path_buses . DS . $bus_id;
                $gallery_files_path = $bus_path .DS . "gallery";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( $img_ext ){
                    //
                    $img_path = $gallery_files_path.DS."orig-" . $img_gallery_id . "." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_buses . UD . $bus_id . UD . "gallery" . UD . "orig-" . $img_gallery_id . "." . $img_ext;
                    }
                    //
                    $thumb_img_path = $gallery_files_path.DS."thumb-" . $img_gallery_id . "." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_buses . UD . $bus_id . UD . "gallery" . UD . "thumb-" . $img_gallery_id . "." . $img_ext;
                    }
                }


            }
        ]);
    }










    //
    public static function GetRecordById($account_id, $bus_id, $img_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM buses_images t
                  
                        Where t.bus_id = ?
                        And t.feature_id = ?
                ";
            },
            "params" => [
                $account_id,
                $bus_id,
                $img_id
            ],
            "parse" => function(&$row){

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
                  Insert Into buses_images
                  ( app_id, bus_id, datetime_created )
                  Values
                  ( ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                  ";
            //echo $stmt; exit;
            //
            $params = array(
                $options['app_id'],
                $options['bus_id'],
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






    public static function updateImgExt($file_extension, $app_id, $bus_id, $record_id){
        //
        $update_results = Query::DoTaskV2([
            "task" => "update",
            "stmt" => function(){
                return "
                 Update 
                    buses_images
                  
                  Set
                    img_ext = ?
                  
                  Where app_id = ?
                  And bus_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ";
            },
            "params" => function() use($file_extension, $app_id, $bus_id, $record_id){
                //
                return [
                    $file_extension,
                    $app_id,
                    $bus_id,
                    $record_id,
                ];
            },
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;
        return $update_results;
    }



    
    



    //
    public static function Remove($app_id, $bus_id, $id){
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
            $stmt = "Delete FROM buses_images
                        Where app_id = ?
                        And bus_id = ?
                        And id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $app_id,
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