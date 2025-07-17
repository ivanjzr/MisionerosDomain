<?php
namespace App\Blog;


//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class Posts
{














    //
    public static function GetAll($app_id, $qty = 12, $str_search = null){

        //
        $str_where = "";
        if ($str_search){
            $str_where .= " And t.nombre like '%$str_search%'";
        }
        //echo $str_where; exit;

        //
        return Query::All([
            "stmt" => function() use ($qty, $str_where){
                return "
				SELECT
                    
				    Top $qty
                    t.*,
				    emp.nombre username,
				    cat.category
                  
                        FROM posts t
				
				            Left Join empleados emp On emp.id = t.user_id
				            Left Join cat_blog_categories cat On cat.id = t.category_id
                
                           Where t.app_id = ?
                           And t.active = 1
                           
                           {$str_where}

                    Order By t.id Desc
			";
            },
            "params" => [
                $app_id
            ],
            "parse" => function(&$row){


                //
                self::setPostFields($row);


                //
                $files_path = Paths::$path_posts . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }


            }
        ]);
    }







    //
    public static function setPostFields(&$row){
        //
        $short_content = strip_tags($row['contenido']);
        $s = str_replace('&nbsp;', " ", $short_content);
        $s = str_replace('&emsp;', " ", $s);
        //
        $row['short_content'] = substr($s, 0, 200) . "...";
        $row['post_day'] = $row['datetime_created']->format("d");
        $row['post_month'] = $row['datetime_created']->format("M");
        //
        $row['datetime'] = $row['datetime_created']->format("M d Y h:i A");
        $row['date'] = $row['datetime_created']->format("M d Y");
    }







    //
    public static function GetRecordById($app_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM posts t
                  
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
                //$row['features'] = postsFeatures::GetAll($app_id, $row['id']);

                //
                $files_path = Paths::$path_posts . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }


            }
        ]);
    }




    //
    public static function GetRecordByUrl($app_id, $url){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                    Select
                        k.*
                        From
                           (SELECT
                            
                            t.*,
                            emp.nombre username,
                            cat.category,
                            --
                            lag(t.nombre) over (partition by t.app_id order by t.id) as prev_post_nombre,
                            lag(t.url) over (partition by t.app_id order by t.id) as prev_post_url,
                            --
                            lead(t.nombre) over (partition by t.app_id order by t.id) as next_post_nombre,
                            lead(t.url) over (partition by t.app_id order by t.id) as next_post_url
                            
                              FROM posts t
                            
                                Left Join empleados emp On emp.id = t.user_id
                                Left Join cat_blog_categories cat On cat.id = t.category_id
                          
                                Where t.app_id = ?
                               ) k 

                        Where k.url = ?
                ";
            },
            "params" => [
                $app_id,
                $url
            ],
            "parse" => function(&$row) use($app_id){


                //
                self::setPostFields($row);


                //
                $files_path = Paths::$path_posts . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }


            }
        ]);
    }





    //
    public static function GetAllByCategoryId($app_id, $category_id, $qty = 12, $exclude_id = null, $str_search = null){


        //
        $str_where = "";
        if ($exclude_id){
            $str_where = " And t.id != $exclude_id";
        }
        //
        if ($str_search){
            $str_where .= " And t.nombre like '%$str_search%'";
        }
        //echo $str_where; exit;

        //
        return Query::All([
            "stmt" => function() use ($qty, $str_where){
                return "
				SELECT
                    
				    Top $qty
				    
                    t.*,
				    emp.nombre username,
				    cat.category
                  
                        FROM posts t
				
				            Left Join empleados emp On emp.id = t.user_id
				            Left Join cat_blog_categories cat On cat.id = t.category_id
                
                           Where t.app_id = ?
                           And t.category_id = ?
                           And t.active = 1
				           
				           {$str_where}

                    Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $category_id
            ],
            "parse" => function(&$row){
                //
                //$row['features'] = postsFeatures::GetAll($app_id, $row['id']);


                //
                self::setPostFields($row);


                //
                $files_path = Paths::$path_posts . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }

            }
        ]);
    }





    //
    public static function GetRecentPosts($app_id, $qty = 4, $exclude_id = null){


        //
        $str_where = "";
        if ($exclude_id){
            $str_where = " And t.id != $exclude_id";
        }

        //
        return Query::All([
            "stmt" => function() use ($qty, $str_where){
                return "
				SELECT
                    
				    Top $qty
				    
                    t.*,
				    emp.nombre username
                  
                        FROM posts t
				
				            Left Join empleados emp On emp.id = t.user_id
                
                           Where t.app_id = ?
                           And t.active = 1
				           
				           {$str_where}

                    Order By t.id Desc
			";
            },
            "params" => [
                $app_id
            ],
            "parse" => function(&$row){
                //
                //$row['features'] = postsFeatures::GetAll($app_id, $row['id']);


                //
                self::setPostFields($row);


                //
                $files_path = Paths::$path_posts . DS . $row['id'];
                $main_files_path = $files_path .DS . "main";
                //
                $row["orig_img_url"] = "";
                $row["thumb_img_url"] = "";
                //
                if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                    //
                    $img_path = $main_files_path.DS."orig." . $img_ext;
                    if ( is_file($img_path) ){
                        $row["orig_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                    }
                    //
                    $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                    if ( is_file($thumb_img_path) ){
                        $row["thumb_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                    }
                }

            }
        ]);
    }











    //
    public static $main_img_width = 886;
    public static $main_img_height = 960;
    //
    public static $thumb_img_width = 200;
    public static $thumb_img_height = 217;


    public static function updateImage($app_id, $img_section, $file_extension, $post_id){
        //var_dump($img_section); exit;

        //
        $results = array();
        //
        $files_path = Paths::$path_posts . DS . $post_id;


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
        // thumb
        if (!ImagesHandler::resizeImage($img_section->file, self::$thumb_img_width, self::$thumb_img_height,  $main_files_path . DS . $new_img_thumb_name, false)) {
            array_push($results['thumb_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
        }

        // original
        if ( !move_uploaded_file($img_section->file, $main_files_path . DS . $new_img_name) ) {
            array_push($results['img_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
        }


        //
        $results['main_img'] = self::UpdateImgExt(
            array(
                "img_ext" => $file_extension,
                //
                "app_id" => $app_id,
                "post_id" => $post_id
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
                    posts
                  
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
                $options['post_id']
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
            $results['id'] = $options['post_id'];



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