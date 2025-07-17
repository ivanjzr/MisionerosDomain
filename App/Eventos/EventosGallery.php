<?php
namespace App\Eventos;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class EventosGallery
{








    //
    public static function GetAll($app_id, $evento_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				select 

                    t.*
                  
                    from eventos_images t
                
                        Where t.app_id = ?
                        And t.evento_id = ?
			";
            },
            "params" => [
                $app_id,
                $evento_id
            ],
            "parse" => function(&$row){


                //
                $evento_id = $row['evento_id'];
                $img_gallery_id = $row['id'];
                $img_ext = $row['img_ext'];
                //
                $evento_path = Paths::$path_eventos . DS . $evento_id;
                $gallery_files_path = $evento_path .DS . "gallery";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( $img_ext ){
                    //
                    $img_path = $gallery_files_path.DS."orig-" . $img_gallery_id . "." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_eventos . UD . $evento_id . UD . "gallery" . UD . "orig-" . $img_gallery_id . "." . $img_ext;
                    }
                    //
                    $thumb_img_path = $gallery_files_path.DS."thumb-" . $img_gallery_id . "." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_eventos . UD . $evento_id . UD . "gallery" . UD . "thumb-" . $img_gallery_id . "." . $img_ext;
                    }
                }


            }
        ]);
    }










    //
    public static function GetRecordById($app_id, $evento_id, $img_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM eventos_images t
                  
                        Where t.app_id = ?
                        And t.evento_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $evento_id,
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
                  Insert Into eventos_images
                  ( app_id, evento_id, datetime_created )
                  Values
                  ( ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                  ";
            //echo $stmt; exit;
            //
            $params = array(
                $options['app_id'],
                $options['evento_id']
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
                    eventos_images
                  
                  Set
                    img_ext = ?
                  
                  Where app_id = ?
                  And evento_id = ?
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
                $options['evento_id'],
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
            $results['evento_id'] = $options['evento_id'];
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
            $stmt = "Delete FROM eventos_images
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