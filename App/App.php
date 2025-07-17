<?php
namespace App;

//
use App\Site\SiteConfig;
use Helpers\Helper;
use Helpers\Query;

//
class App {



    /*
     * Scripts
     * */
    public static $header_scrits = "";
    public static $footer_scripts = "";
    public static $footer_section_scripts = "";
    public static $body_classes = "";
    //
    public static $lang_info = null;
    public static $user_data = null;


    //
    public function __construct($lang_info, $user_data = null, $app_id = null){
        self::$lang_info = $lang_info;
        self::$user_data = $user_data;
    }




    private static function searchForId($name, $array, $field_name) {
        foreach ($array as $key => $val) {
            if ( $val["name"] === $name ) {
                return $val[$field_name];
            }
        }
        return null;
    }



    //
    public static function GetLangCode(){
        return self::$lang_info['code'];
    }


    //
    public static function PageUrl($url = ""){
        $new_url = UD.self::$lang_info['code'].$url;
        return $new_url;
    }



    /*
     * Set Section Info
     * */
    private static $section_title = null;
    private static $menu_section = null;
    private static $menu_open_section = null;
    private static $sub_menu_open_section = null;
    //
    public static function setSectionInfo($section_title, $menu_section = "", $menu_open_section = "", $sub_menu_open_section = ""){
        self::$section_title = $section_title;
        self::$menu_section = $menu_section;
        self::$menu_open_section = $menu_open_section;
        self::$sub_menu_open_section = $sub_menu_open_section;
    }
    //
    public static function getSectionTitle(){
        return self::$section_title;
    }
    //
    public static function isMenuActive($section){
        return (self::$menu_section==$section) ? "active" : "";
    }
    //
    public static function isMenuOpen($menu_open_section){
        return (self::$menu_open_section==$menu_open_section) ? "menu-open" : "";
    }
    //
    public static function isDropDownMenuActive($menu_open_section){
        return (self::$menu_open_section==$menu_open_section) ? "active" : "";
    }
    //
    public static function isSubMenuOpen($sub_menu_open_section){
        return (self::$sub_menu_open_section==$sub_menu_open_section) ? "menu-open" : "";
    }
    //
    public static function isSubDropDownMenuAactive($sub_menu_open_section){
        return (self::$sub_menu_open_section==$sub_menu_open_section) ? "active" : "";
    }



    /*
     * SESION
     * */
    //
    public static function GetUserData(){
        return self::$user_data;
    }
    //
    public static function getUserSession($type){
        //
        $type = strtolower($type);
        if ( isset($_SESSION[$type]) && isset($_SESSION[$type]['id']) ){
            //echo "*** ";dd($_SESSION);
            //
            $user_id = $_SESSION[$type]['id'];
            $app_id = $_SESSION[$type]['app_id'];
            $account_id = $_SESSION[$type]['account_id'];
            //echo " $user_id $account_id $app_id "; exit;
            //
            return Query::StoredProcedure([
                "ret" => "single",
                "debug" => false,
                "stmt" => function(){
                    return "{call usp_AuthAdmin(?,?,?)}";
                },
                "params" => function() use($account_id, $user_id){
                    return [
                        array(DOMAIN_NAME, SQLSRV_PARAM_IN),
                        array($account_id, SQLSRV_PARAM_IN),
                        array($user_id, SQLSRV_PARAM_IN)
                    ];
                },
                "parse" => function(&$row) use ($app_id, $type){
                    //dd($row);

                    // type backend
                    $row["t"] = "b";

                    //
                    $row["image_url"] = "";
                    $files_path = Paths::$path_users . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["image_url"] = Paths::$url_users . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                    }

                    // Aqui se guardan las variables de sesion ya que lo demas se trae mediante el usp_AuthAdmin
                    //dd($_SESSION);
                    if (isset($_SESSION[$type]['ses'])){
                        $row['ses'] = $_SESSION[$type]['ses'];
                    }

                },
            ]);
            //echo "***test: "; dd($res);
        }
        //echo "is null"; exit;
        //
        return null;
    }
    //
    public static function getAdminSession($type){
        //
        $type = strtolower($type);
        if ( isset($_SESSION[$type]) && isset($_SESSION[$type]['id']) && $_SESSION[$type]['id']){
            //
            $user_id = $_SESSION[$type]['id'];
            //
            return Query::StoredProcedure([
                "ret" => "single",
                "stmt" => function(){
                    return "{call usp_AuthSupadmin(?)}";
                },
                "params" => function() use($user_id){
                    return [
                        array($user_id, SQLSRV_PARAM_IN)
                    ];
                },
                "parse" => function(&$row){
                    //var_dump($row); exit;

                    $row["image_url"] = "";
                    $files_path = Paths::$path_administrators . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["image_url"] = Paths::$url_administrators . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                    }

                },
            ]);
        }
        //
        return null;
    }
    //
    public static function setUserSession($type, $new_data){
        $_SESSION[strtolower($type)] = $new_data;
        //var_dump($_SESSION); exit;
    }




}