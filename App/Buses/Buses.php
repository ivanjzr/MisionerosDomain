<?php
namespace App\Buses;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use App\Utils\Utils;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class Buses
{







    //
    public static function GetAll($account_id, $qty = 12){

        //
        $city_id = 3;

        //
        return Query::Multiple("
				SELECT
                    
				    Top $qty
                    t.*
                    
                        FROM v_buses t
                
                           Where t.account_id = ?
                           And t.active = 1

                        order by newid()
			", [
			    $account_id
        ], function(&$row) use ($account_id, $city_id){

            //
            self::preciosInfo($account_id, $city_id, $row);
            $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);
            Buses::getImage($row);

        });
    }




    //
    public static function GetAllForWebsite($account_id, $app_id){

        //
        return Query::Multiple("
				SELECT t.* FROM v_buses t
                           Where t.app_id = ?
                           And t.active = 1
                        order by newid()
			", [
			    $app_id
        ], function(&$row) use ($app_id){

            //
            $row['features'] = BusesFeatures::GetAll($app_id, $row['id']);
            $row['images'] = BusesGallery::GetAll($app_id, $row['id']);
            Buses::getImage($row);

        });
    }


    //
    public static function GetAllV2($account_id, $city_id, $str_tags_ids, $exclude_a_la_carte = true, $lang_code = "en-us"){
        //
        $str_where_tags_ids = "";
        if ($str_tags_ids){
            $str_where_tags_ids = "And t.id In ( Select bus_id from products_tags Where account_id = $account_id And tag_id In ($str_tags_ids) )";
        }
        //echo $str_where_tags_ids; exit;

        //
        $str_where_not_a_la_carte = "";
        if ($exclude_a_la_carte){
            $str_where_not_a_la_carte = " And t.category_id != 11 ";
        }

        //
        return Query::Multiple("
				SELECT
				       
                    t.*
				
                        FROM v_buses t
                
                           Where t.account_id = ?
                           And t.active = 1
                           
				           {$str_where_not_a_la_carte}
				           {$str_where_tags_ids}
				
                        order by newid()
			",[
                $account_id
            ],
            function(&$row) use ($account_id, $city_id, $lang_code){
                //Helper::printFull($row); exit;
                //
                Utils::setNombreDescriptionLang($row, $lang_code);
                //
                self::preciosInfo($account_id, $city_id, $row, $lang_code);
                $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);
                Buses::getImage($row);
            });
    }




    //
    public static function GetProductByUrl($account_id, $product_url, $city_id, $lang_code = "en-us"){
        //
        return Query::Single("SELECT t.* FROM v_buses t Where t.account_id = ? And t.url = ?", [
            $account_id,
            $product_url
        ], function(&$row) use ($account_id, $city_id, $lang_code){
            //Helper::printFull($row); exit;
            //
            Utils::setNombreDescriptionLang($row, $lang_code);
            //
            self::preciosInfo($account_id, $city_id, $row, $lang_code);
            $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);
            Buses::getImage($row);
            //
            //Helper::printFull($row); exit;
        });
    }



    //
    public static function convertImageToJpeg($orig_img, $new_img){
        $image = imagecreatefrompng($orig_img);
        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, TRUE);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        $quality = 50; // 0 = worst / smaller file, 100 = better / bigger file
        if (imagejpeg($bg, $new_img, $quality)){
            imagedestroy($bg);
            return true;
        }
        return false;
    }






    //
    public static function getImage(&$row){
        //dd($row);
        //
        $files_path = Paths::$path_buses . DS . $row['id'];
        $main_files_path = $files_path .DS . "main";
        //
        $row["orig_img_url"] = "";
        $row["thumb_img_url"] = "";
        //
        if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
            //
            $img_path = $main_files_path.DS."orig." . $img_ext;
            //$img_path = $main_files_path.DS."thumb." . $img_ext;
            if ( is_file($img_path) ){
                $row["orig_img_url"] = Paths::$url_buses . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                //$row["orig_img_url"] = Paths::$url_buses . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
            }
            //
            $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
            if ( is_file($thumb_img_path) ){
                $row["thumb_img_url"] = Paths::$url_buses . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
            }
        }
    }


    public static function convertirBytesAMegabytes($bytes) {
        $mb = $bytes / (1024 * 1024); // Convertir bytes a megabytes
        $mb_redondeado = round($mb, 2); // Redondear a 2 decimales

        return $mb_redondeado;
    }

    //
    public static function getImageInfo(&$row){
        //
        $files_path = Paths::$path_buses . DS . $row['id'];
        $main_files_path = $files_path .DS . "main";
        //
        $row["orig_img_url"] = "";
        $row["thumb_img_url"] = "";
        //
        if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
            //
            $img_path = $main_files_path.DS."orig." . $img_ext;


            if ( file_exists($img_path) ){
                //
                $orig_img_url = Paths::$url_buses . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                //
                $dimensiones = getimagesize($img_path);
                $img_size_mb = self::convertirBytesAMegabytes(filesize($img_path));
                //
                $row["ancho"] = $dimensiones[0];
                $row["alto"] = $dimensiones[1];
                $row["img_size"] = $img_size_mb;
                $row["img_url"] = $orig_img_url;
            }
            /*
            //
            $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
            //
            if ( is_file($thumb_img_path) ){
                $thumb_img_url = Paths::$url_buses . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                $row["thumb_size"] = filesize($thumb_img_path);
                $row["thumb_url"] = $thumb_img_url;
            }
            */
        }
    }







    //
    public static function GetAllByCategoryId($account_id, $category_id, $qty = 12, $exclude_id = null){
        //
        $str_where = "";
        if ($exclude_id){
            $str_where = " And t.id != $exclude_id";
        }

        //
        $city_id = 3;

        //
        return Query::Multiple("
				SELECT
				    Top $qty
                    t.*
                        FROM v_buses t
				
                           Where t.account_id = ?
                           And t.category_id = ?
                           And t.active = 1
				           {$str_where}

                    Order By newid()
			", [
            $account_id,
            $category_id
        ], function(&$row) use ($account_id, $city_id){
            //
            self::preciosInfo($account_id, $city_id, $row);
            $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);
            Buses::getImage($row);
        });
    }




    //
    public static function GetByCategoryIdV2($account_id, $city_id, $category_id, $str_tags_ids, $lang_code = "en-us"){
        //
        $str_where_tags_ids = "";
        if ($str_tags_ids){
            $str_where_tags_ids = "And t.id In ( Select bus_id from products_tags Where account_id = $account_id And tag_id In ($str_tags_ids) )";
        }
        //echo $str_where_tags_ids; exit;
        //
        return Query::Multiple("
				SELECT
                    t.*
                        FROM v_buses t
				
                           Where t.account_id = ?
                           And t.category_id = ?
                           And t.active = 1
                           
				           {$str_where_tags_ids}

                    Order By newid()
			", [
            $account_id,
            $category_id
        ], function(&$row) use ($account_id, $city_id, $lang_code){
            //Helper::printFull($row); exit;
            //
            Utils::setNombreDescriptionLang($row, $lang_code);
            //
            self::preciosInfo($account_id, $city_id, $row, $lang_code);
            $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);
            Buses::getImage($row);
            //Helper::printFull($row); exit;
        });
    }





    //
    public static function preciosInfo($account_id, $city_id, &$row, $lang_code = "en-us"){
        //Helper::printFull($row); exit;
        //
        if ( $row['multiple_prices'] ){

            
        }
        //
        else {
            
        }
    }




    //
    public static function SearchProducts($app_id, $search_val, $qty = 12){

        //
        $city_id = 3;

        //
        return Query::Multiple("
				SELECT
                    
				    Top $qty
                    t.*
                    
                        FROM v_buses t
                
                           Where t.app_id = ?
                           And t.active = 1

                           And (
                                
                               ( t.nombre like '%{$search_val}%' ) Or
                               
                               ( t.description like '%{$search_val}%' )
                                
                           )
			", [
            $app_id
        ],
            function(&$row) use ($app_id, $city_id){

                //
                Buses::getImage($row);

        });
    }













    //
    public static function GetByPlanTypeForSubscriptions($account_id, $plan_type_id, $category_id = null, $exclude_a_la_carte = true, $lang_code = "en-us"){


        /*
         * SI ES MIXED SE TRAE ALL
         * SI ES LEAN, SOLO LO QUE SEA "is_lean"
         * */
        $where_is_lean = "";
        if ( $plan_type_id == PLAN_ID_LEAN ){
            //
            $tag_id_lean_protein = 5;
            //
            $where_is_lean = " And t.id In (select 
                                                bus_id 
                                                from products_tags 
                                                    where tag_id In({$tag_id_lean_protein}) 
                                                    and account_id = {$account_id})";
        }
        //echo $where_is_lean; exit;

        //
        $where_cat_id = "";

        //
        if ( $category_id ){
            $where_cat_id = " And t.category_id = $category_id";
        }
        //echo $where_cat_id; exit;


        //
        $str_where_not_a_la_carte = "";
        if ($exclude_a_la_carte){
            $str_where_not_a_la_carte = " And t.category_id != 11 ";
        }
        //
        return Query::Multiple("
				SELECT
				       
                    t.*
				
                    FROM v_buses t
            
                       Where t.account_id = ?
                       And t.active = 1
                       
                       {$str_where_not_a_la_carte}
                       {$where_is_lean}
                       {$where_cat_id}
                       
                Order By t.id Desc
			", [
            $account_id,
            $plan_type_id
        ], function(&$row) use ($account_id, $lang_code){
            //Helper::printFull($row); exit;

            //
            Utils::setNombreDescriptionLang($row, $lang_code);
            //
            $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);


            //
            Buses::getImage($row);
        });
    }




    public static function updateImgExt($file_extension, $app_id, $bus_id){
        //echo $file_extension . " - " . $bus_id; exit;
        //
        $update_results = Query::DoTaskV2([
            "task" => "update",
            "stmt" => function(){
                return "
                Update 
                    buses
                  
                  Set
                    img_ext = ?
                  
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ";
            },
            "params" => function() use($file_extension, $app_id, $bus_id){
                //
                return [
                    $file_extension,
                    $app_id,
                    $bus_id
                ];
            },
            "parse" => function($updated_rows, &$query_results) use($bus_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$bus_id;
            }
        ]);
        //var_dump($update_results); exit;
        return $update_results;
    }



    //
    public static function GetByUrl($account_id, $url){
        //
        return Query::Single("SELECT t.* FROM v_buses t Where t.account_id = ? And t.url = ? And t.active = 1", [$account_id, $url], function(&$row) use($account_id){
            //var_dump($row);

            //
            $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);

            //
            if ($row['multiple_prices']){
                
            }
            //
            Buses::getImage($row);
        });
    }












    //
    public static function GetRecordBySucursalId($app_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM v_buses t

                        Where t.app_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $id
            ],
            "parse" => function(&$row){
                //
                Buses::getImage($row);
            }
        ]);
    }




    //
    public static function GetRecordById($account_id, $bus_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    t.*
                      FROM v_buses t
                   
                        Where t.account_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $bus_id
            ],
            "parse" => function(&$row) use($account_id){
                //
                Buses::getImage($row);

            }
        ]);
    }




    //
    public static function GetDetails($account_id, $bus_id, $city_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                      t.*
                      FROM v_buses t
                   
                        Where t.account_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $bus_id
            ],
            "parse" => function(&$row) use($account_id, $city_id){
                //Helper::printFull($row); exit;

                //
                Buses::getImage($row);
                $row['features'] = BusesFeatures::GetAll($account_id, $row['id']);
                //var_dump($row['features']); exit;

            }
        ]);
    }




    //
    public static function GetRecentProducts($account_id, $qty = 4, $exclude_id = null){

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
				    
                    t.*
                  
                        FROM v_buses t
				
				
                           Where t.account_id = ?
                           And t.active = 1
				           
				           {$str_where}

                    Order By t.id Desc
			";
            },
            "params" => [
                $account_id
            ],
            "parse" => function(&$row){
                //
                Buses::getImage($row);
            }
        ]);
    }













}