<?php
namespace App\Proveedores;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class Proveedores
{






    //
    public static function GetAll($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*,
                    cit.nombre ciudad
                  
                        FROM proveedores t
                        
                            Left JOIN cat_ciudades cit On cit.id = t.city_id
                        
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
                    
                    t.*,
                    cit.estado_id,
                    cit.nombre ciudad,
                    est.nombre estado
                    
                      FROM proveedores t
                  
                  
                        Left Join sys_cat_ciudades cit On cit.id = t.city_id
                        Left Join sys_cat_estados est On est.id = cit.estado_id
                  
                        Where t.app_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $id
            ],
            "parse" => function(&$row){

                $files_path = Paths::$path_proveedores . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_proveedores . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_proveedores . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }

            }
        ]);
    }







    //
    public static function updateProveedorLogo($img_section, $file_extension, $id){
        //var_dump($img_section); exit;

        //
        $results = array();
        //
        $files_path = Paths::$path_proveedores . DS . $id;


        //
        $main_files_path = $files_path .DS . "main";
        //
        if (!is_dir($files_path)){
            mkdir($files_path);
        }
        //
        if (!is_dir($main_files_path)){
            mkdir($main_files_path);
        }

        //$img_nombre = $img_section->getClientFilename();
        $new_img_name = "orig." . $file_extension;
        $new_img_thumb_name = "thumb." . $file_extension;
        //---------------------------MAIN
        // original
        if (!ImagesHandler::resizeImage($img_section->file, 886, 960,  $main_files_path . DS . $new_img_name, false)) {
            array_push($update_results['img_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
        }
        // thumb
        if (!ImagesHandler::resizeImage($img_section->file, 200, 217,  $main_files_path . DS . $new_img_thumb_name, false, true)) {
            array_push($update_results['thumb_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
        }
        //
        $results['main_img'] = self::UpdateImgExt(
            array(
                "img_ext" => $file_extension,
                "id" => $id
            ));





        //
        return $results;
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
                    proveedores
                  
                  Set
                    img_ext = ?
                  
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                //
                $options['img_ext'],
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







}