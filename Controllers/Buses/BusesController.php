<?php
namespace Controllers\Buses;

//
use App\Catalogues\CatMakes;
use App\Paths;
use App\Buses\BusesPrices;
use App\Buses\Buses;

use App\Buses\BusesGallery;
use App\Buses\BusesIngredients;
use App\Buses\BusesFeatures;
use App\Sucursales\Sucursales;
use App\Utils\Utils;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;
use net\authorize\util\Helpers;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Console\Helper\ProcessHelper;


//
class BusesController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/buses/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }



    //
    public function ViewEdit($request, $response, $args) {
        //
        $view_data = [
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['id']
        ];
        //
        return $this->container->php_view->render($response, 'admin/buses/edit.phtml', $view_data);
    }







    //
    public static function configSearchClause($search_value, $make_id){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.year like '%$search_value%' ) Or
                        ( t.description like '%$search_value%' )
                    )";
        }

        //
        if ( is_numeric($make_id) ){
            //
            $search_clause .= " And t.make_id = $make_id ";
        }

        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "v_buses";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), $request->getQueryParam("mkid"));
        //echo $search_clause; exit;
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];




        



        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "deb" => true,
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
                },
                //
                "cnt_params" => array(
                    $app_id
                ),

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                      
                                            Where t.app_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                //
                "params" => array(
                    $app_id
                ),

                //
                "parseRows" => function(&$row) use($app_id){
                    //dd($row); exit;


                    //
                    //dd($row); exit;

                    //
                    $row['features'] = BusesFeatures::GetAll($app_id, $row['id']);

                    //
                    Buses::getImage($row);


                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }

















    //
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];



        //
        $results = Buses::GetAll($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





   
    //
    public function GetAllAvailableServices($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        

        //
        $results = Query::Multiple("Select * from v_buses Where app_id = ? And tipo_bus = 's' And active = 1", [$app_id]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }









    //
    public function GetAvaiableForPOS($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        

        //
        $results = Query::Multiple("Select * from v_buses Where app_id = ? And active = 1 And is_pos = 1", [$app_id]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }













    /*
     *
     * HELPER PARA CONVERTIR TODAS LAS IMAGENES PESADAS (PNG) DE HASTA EN 2MB EN 30kb, etc
     *
     * */
    public function UpdateImagesSizeAndExtencion($request, $response, $args) {


        $account_id = 5;

        //
        $ret_imgs = Query::Multiple("
				SELECT
                    
                    t.*
                    
                        FROM v_buses t
                
                           Where t.account_id = ?
                           
			", [
            $account_id
        ], function(&$row) use ($account_id){

            //
            $files_path = Paths::$path_buses . DS . $row['id'];




            //
            $prods_gallery = BusesGallery::GetAll($account_id, $row['id']);
            //dd($prods_gallery); exit;


            //
            $orig_img_path = $files_path .DS . "main";
            $gallery_orig_img_path = $files_path .DS . "gallery";


            //
            foreach ($prods_gallery as $idx => $gall){
                //dd($gall); exit;

                //
                $gallery_thumb_img = $gallery_orig_img_path . DS . "thumb-" . $gall['id'] . ".png";
                $gallery_thumb_new_img = $gallery_orig_img_path . DS . "thumb-" . $gall['id'] . ".jpg";
                //
                $gallery_orig_img = $gallery_orig_img_path . DS . "orig-" . $gall['id'] . ".png";
                $gallery_new_img = $gallery_orig_img_path . DS . "orig-" . $gall['id'] . ".jpg";

                //
                if ( is_file($gallery_orig_img)){
                    //
                    if (Buses::convertImageToJpeg($gallery_orig_img, $gallery_new_img)){
                        echo " created to thumb gallery: $gallery_new_img <br />";
                    }
                }
                //
                if ( is_file($gallery_thumb_img)){
                    //
                    if (Buses::convertImageToJpeg($gallery_thumb_img, $gallery_thumb_new_img)){
                        echo " created to gallery: $gallery_thumb_new_img <br />";
                    }
                }

            }


            //
            if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){


                //
                $thumb_img = $orig_img_path . DS . "thumb.png";
                $new_thumb_img = $orig_img_path . DS . "thumb.jpg";

                //
                $orig_img = $orig_img_path . DS . "orig.png";
                $new_img = $orig_img_path . DS . "orig.jpg";


                //
                if ( is_file($thumb_img) ){
                    //
                    if (Buses::convertImageToJpeg($thumb_img, $new_thumb_img)){
                        echo "created to thumb: $new_thumb_img";
                    }
                }
                //
                if ( is_file($orig_img) ){
                    //
                    if (Buses::convertImageToJpeg($orig_img, $new_img)){
                        echo " created to orig: $new_img <br />";
                    }
                }

            }



        });




        //
        echo "DONE"; exit;

    }




    

    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];
        //echo $sucursal_id; exit;



        //
        $results = Buses::GetRecordBySucursalId( $app_id, $args['id']);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }






    //
    public function GetByPlanTypeForSubscriptions($request, $response, $args) {

        //
        $app = $request->getAttribute("app");
        //var_dump($app); exit;
        $account_id = $app['account_id'];
        $user_id = $app['id'];


        //
        $make_id = $request->getQueryParam("cid");
        //echo $make_id; exit;


        //
        $lang_code = $request->getQueryParam("lang", "en-us");
        //echo $lang_code; exit;


        //
        $results = Buses::GetByPlanTypeForSubscriptions( $account_id, $args['plan_type_id'], $make_id, true, $lang_code);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }







    //
    public function GetPublic($request, $response, $args) {

        //
        $app = $request->getAttribute("app");
        //dd($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];


        //
        $results = [];


        //
        $bus_id = $args['id'];


        //
        $results = Buses::GetDetails($account_id);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }




    //
    public function GetSearch($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //echo "$account_id, $app_id"; exit;
        
        

        //
        $search_text = $request->getQueryParam("q");
        //echo $search_text; exit;

        //
        $results = Buses::SearchBuses($app_id, $search_text, 10);
        //dd($results); exit;
        
        //
        return $response->withJson($results, 200);
    }





    //
    public function GetBusesPublic($request, $response, $args) {

        //
        $app = $request->getAttribute("app");
        //dd($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];


        //
        $results = array();


        //
        $make_url = $request->getQueryParam("cat");
        $tags_ids = $request->getQueryParam("tags");

        //
        $lang_code = $request->getQueryParam("lang", "en-us");
        //echo $lang_code; exit;


        /*
         * Get Tags Ids
         * */
        $str_tags_ids = "";
        //
        if ($tags_ids){
            $arr_tags_ids = json_decode($tags_ids, true);
            $str_tags_ids = rtrim(implode(',', $arr_tags_ids), ',');
        }
        //echo $str_tags_ids; exit;



        /*
         * Get make Info
         * */
        //
        $make_id = null;
        $results['make'] = array();
        $results['make']['nombre'] = "All";
        //
        if ($make_url){
            //
            $results['make'] = CatMakes::GetRecordByUrl($account_id, $make_url, $lang_code);
            $make_id = ($results['make'] && isset($results['make']['id'])) ? $results['make']['id'] : null;
        }
        //dd($results['make']); exit;



        //
        if ($make_id){
            $results['data'] = Buses::GetByMakeIdV2($account_id, $make_id, $str_tags_ids, $lang_code);
            //dd($res); exit;
        } else {
            $results['data'] = Buses::GetAllV2($account_id, $str_tags_ids, true, $lang_code);
            //dd($res); exit;
        }

        //
        return $response->withJson($results, 200);
    }





    //
    public function GetALaCarte($request, $response, $args) {

        //
        $app = $request->getAttribute("app");
        //dd($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];


        //
        $results = array();




        //
        $lang_code = $request->getQueryParam("lang", "en-us");
        //echo "$lang_code"; exit;


        //
        $make_id_a_la_carte = 11;
        //
        $results['data'] = Buses::GetByMakeIdV2($account_id, $make_id_a_la_carte, null, $lang_code);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }





    //
    public function GetBusInfoPublic($request, $response, $args) {

        //
        $app = $request->getAttribute("app");
        //dd($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];

        /*
         * [id] => 2
    [account_id] => 5
    [app_name] => PhitPhuel
    [domain_prod] => phitphuel.testingbox.co
    [domain_dev] => phitphuel.test
    [domain_controller] => PhitPhuel
    [login_logo_path] => /login-phitphuel.png
    [logo_path] => /phitphuel.png
    [views_name] => phitphuel
    [description] =>
         * */



        //
        $results = array();


        //
        $bus_url = $args['bus_url'];
        $state_code = $args['state_code'];
        $city_code = $args['city_code'];
        //echo "$bus_url $state_code $city_code"; exit;





        //
        $city_info = Query::Single("
            Select
                t.*,
                est.nombre estado,
                est.abreviado estado_abrev
                From sys_cat_ciudades t
                    Left Join sys_cat_estados est On est.id = t.estado_id
                    Where t.nombre = ?
                    And est.abreviado = ?
        ", [
            $city_code,
            $state_code
        ]);
        
        //
        $results = Buses::GetBusByUrl($account_id, $bus_url);


        //
        return $response->withJson($results, 200);
    }





    //
    public function UploadXlsFile_2($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];
        //echo $sucursal_id; exit;

        //
        $results = array();
        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;



        //
        $file_xls = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['xls_file']) &&
            ( $uploadedFile = $uploadedFiles['xls_file'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $file_xls = $uploadedFile;
            //
            $file_extension = pathinfo($file_xls->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //echo $file_extension; exit;
        //dd($file_xls); exit;
        //echo $file_xls->getClientFilename(); exit;


        //
        $inputFileType = IOFactory::identify($file_xls->file);
        //var_dump($inputFileType); exit;


        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($file_xls->file);
        $rows = $spreadsheet->getActiveSheet()->toArray();




        //
        $account_id = 5;
        $sucursal_id = 7;
        $bus_sucursal_active = 7;
        $user_id = 14;




        //
        foreach( $rows as $row_idx => $row ){
            //var_dump($row); exit;
            //
            if ( $row_idx > 1 ){


                //
                $nombre = null;
                $url = null;
                $description = null;
                $make_id = null;
                $multiple_prices = 1;
                $active = 1;

                // traditional
                $trad_precio = null;
                $trad_cals = null;
                $trad_prots = null;
                $trad_carbs = null;
                $trad_fat = null;

                // athlete
                $ath_precio = null;
                $ath_cals = null;
                $ath_prots = null;
                $ath_carbs = null;
                $ath_fat = null;

                // premium
                $prem_precio = null;
                $prem_cals = null;
                $prem_prots = null;
                $prem_carbs = null;
                $prem_fat = null;



                //
                foreach( $row as $col_idx => $col ){
                    //dd($col);
                    //
                    if ($col_idx === 1){$nombre = $col;}
                    if ($col_idx === 2){$description = $col;}
                    if ($col_idx === 3){$make_id = $col;} // col - 3

                    // traditional
                    if ($col_idx === 4){$trad_precio = $col;}
                    if ($col_idx === 5){$trad_cals = $col;}
                    if ($col_idx === 6){$trad_prots = $col;}
                    if ($col_idx === 7){$trad_carbs = $col;}
                    if ($col_idx === 8){$trad_fat = $col;}

                    // athlete
                    if ($col_idx === 9){$ath_precio = $col;}
                    if ($col_idx === 10){$ath_cals = $col;}
                    if ($col_idx === 11){$ath_prots = $col;}
                    if ($col_idx === 12){$ath_carbs = $col;}
                    if ($col_idx === 13){$ath_fat = $col;}

                    // premium
                    if ($col_idx === 14){$prem_precio = $col;}
                    if ($col_idx === 15){$prem_cals = $col;}
                    if ($col_idx === 16){$prem_prots = $col;}
                    if ($col_idx === 17){$prem_carbs = $col;}
                    if ($col_idx === 18){$prem_fat = $col;}


                }
                //dd($arr_prod); exit;

                //
                $url = Helper::converToValidUrl($nombre);


                // DEBUT DATA:
                //echo "$nombre, $url, $description, $make_id, Traditional: $trad_precio $trad_cals, $trad_prots, $trad_carbs, $trad_fat, Athlete: $ath_precio $ath_cals, $ath_prots, $ath_carbs, $ath_fat , Premium: $prem_precio $prem_cals, $prem_prots, $prem_carbs, $prem_fat "; exit;



                $cals= 0;
                $prots= 0;
                $carbs= 0;
                $fats= 0;
                $precio = 0;


                //
                $add_edit_record_id = 0;
                //
                $sp_res = Query::StoredProcedure([
                    "stmt" => function(){
                        return "{call usp_UpsertBus(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
                    },
                    "exeptions_msgs" => [
                        "default" => "Server Error, unable to do operation"
                    ],
                    "debug" => true,
                    "params" => function() use($nombre, $url, $description, $make_id, $cals, $prots, $carbs, $fats, $precio, $multiple_prices, $user_id, $active, $bus_sucursal_active, $account_id, $sucursal_id, $record_id, &$add_edit_record_id){


                        //
                        return [
                            //
                            array($nombre, SQLSRV_PARAM_IN),
                            array($url, SQLSRV_PARAM_IN),
                            array($description, SQLSRV_PARAM_IN),
                            array($make_id, SQLSRV_PARAM_IN),
                            //
                            array($cals, SQLSRV_PARAM_IN),
                            array($prots, SQLSRV_PARAM_IN),
                            array($carbs, SQLSRV_PARAM_IN),
                            array($fats, SQLSRV_PARAM_IN),
                            //
                            array($precio, SQLSRV_PARAM_IN),
                            array($multiple_prices, SQLSRV_PARAM_IN),
                            array($user_id, SQLSRV_PARAM_IN),
                            array($active, SQLSRV_PARAM_IN),
                            //
                            array($bus_sucursal_active, SQLSRV_PARAM_IN),
                            array($account_id, SQLSRV_PARAM_IN),
                            //
                            array($sucursal_id, SQLSRV_PARAM_IN),
                            //
                            array($record_id, SQLSRV_PARAM_IN),
                            array(&$add_edit_record_id, SQLSRV_PARAM_OUT),
                        ];
                    }
                ]);
                //
                if (isset($sp_res['error']) && $sp_res['error']){
                    return $response->withJson($sp_res, 200);
                }
                //echo $add_edit_record_id; exit;

                //
                $results['id'] = $add_edit_record_id;
                

            }
        }






        return $response->withJson($results, 200);
    }








    //
    public function UploadXlsFile($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];
        //echo $sucursal_id; exit;

        //
        $results = array();
        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;



        //
        $file_xls = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['xls_file']) &&
            ( $uploadedFile = $uploadedFiles['xls_file'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $file_xls = $uploadedFile;
            //
            $file_extension = pathinfo($file_xls->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //echo $file_extension; exit;
        //dd($file_xls); exit;
        //echo $file_xls->getClientFilename(); exit;


        //
        $inputFileType = IOFactory::identify($file_xls->file);
        //var_dump($inputFileType); exit;


        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($file_xls->file);
        $rows = $spreadsheet->getActiveSheet()->toArray();




        //
        $account_id = 5;
        $sucursal_id = 7;
        $bus_sucursal_active = 7;
        $user_id = 14;




        //
        foreach( $rows as $row_idx => $row ){
            //var_dump($row); exit;
            //
            if ( $row_idx > 0 ){


                //
                $nombre = null;
                $url = null;
                $description = null;
                $make_id = null;
                $cals = null;
                $prots = null;
                $carbs = null;
                $fats = null;
                $precio = null;
                $multiple_prices = null;
                $active = 1;



                //
                foreach( $row as $col_idx => $col ){
                    //dd($col);
                    //
                    if ($col_idx === 1){$nombre = $col;}
                    if ($col_idx === 2){$description = $col;}
                    if ($col_idx === 3){$make_id = $col;}
                    if ($col_idx === 4){$precio = $col;}
                    if ($col_idx === 5){$cals = $col;}
                    if ($col_idx === 6){$prots = $col;}
                    if ($col_idx === 7){$carbs = $col;}
                    if ($col_idx === 8){$fats = $col;}
                }
                //dd($arr_prod); exit;


                //
                $url = Helper::converToValidUrl($nombre);
                //echo $url; exit;


                //
                $add_edit_record_id = 0;
                //
                $sp_res = Query::StoredProcedure([
                    "stmt" => function(){
                        return "{call usp_UpsertBus(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
                    },
                    "exeptions_msgs" => [
                        "default" => "Server Error, unable to do operation"
                    ],
                    "debug" => true,
                    "params" => function() use($nombre, $url, $description, $make_id, $cals, $prots, $carbs, $fats, $precio, $multiple_prices, $user_id, $active, $bus_sucursal_active, $account_id, $sucursal_id, $record_id, &$add_edit_record_id){


                        //
                        return [
                            //
                            array($nombre, SQLSRV_PARAM_IN),
                            array($url, SQLSRV_PARAM_IN),
                            array($description, SQLSRV_PARAM_IN),
                            array($make_id, SQLSRV_PARAM_IN),
                            //
                            array($cals, SQLSRV_PARAM_IN),
                            array($prots, SQLSRV_PARAM_IN),
                            array($carbs, SQLSRV_PARAM_IN),
                            array($fats, SQLSRV_PARAM_IN),
                            //
                            array($precio, SQLSRV_PARAM_IN),
                            array($multiple_prices, SQLSRV_PARAM_IN),
                            array($user_id, SQLSRV_PARAM_IN),
                            array($active, SQLSRV_PARAM_IN),
                            //
                            array($bus_sucursal_active, SQLSRV_PARAM_IN),
                            array($account_id, SQLSRV_PARAM_IN),
                            //
                            array($sucursal_id, SQLSRV_PARAM_IN),
                            //
                            array($record_id, SQLSRV_PARAM_IN),
                            array(&$add_edit_record_id, SQLSRV_PARAM_OUT),
                        ];
                    }
                ]);
                //
                if (isset($sp_res['error']) && $sp_res['error']){
                    return $response->withJson($sp_res, 200);
                }
                //echo $add_edit_record_id; exit;





            }
        }






        // ------------ function end
    }






    //
    public function Upsert($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];
        //echo $sucursal_id; exit;


        //
        $record_id = ( isset($args['id']) && $args['id'] ) ? $args['id'] : null;

        

        //
        $results = array();

        //
        $uploadedFiles = $request->getUploadedFiles();
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        //
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $bus_code = Helper::safeVar($request->getParsedBody(), 'bus_code');
        //
        $tipo_bus = Helper::safeVar($request->getParsedBody(), 'tipo_bus');
        $make_id = Helper::safeVar($request->getParsedBody(), 'make_id');
        $model_id = Helper::safeVar($request->getParsedBody(), 'model_id');
        $year = Helper::safeVar($request->getParsedBody(), 'year');
        //
        $precio = Helper::safeVar($request->getParsedBody(), 'precio');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


        
        
        //
        $img_section = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['img_section']) &&
            ( $uploadedFile = $uploadedFiles['img_section'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $img_section = $uploadedFile;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //var_dump($img_section); exit;
        //
        $precio = ( is_numeric($precio) ) ? $precio : 0;
        //echo $precio; exit;




        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$url ){
            $results['error'] = "proporciona el URL";
            return $response->withJson($results, 200);
        }


        if(!$record_id){

            //
            if ( !($tipo_bus ==="p" || $tipo_bus ==="s") ){
                $results['error'] = "proporciona el tipo de autobus";
                return $response->withJson($results, 200);
            }

            //
            if ( !is_numeric($make_id) ){
                $results['error'] = "Provide bus make";
                return $response->withJson($results, 200);
            }
            if ( !is_numeric($model_id) ){
                $results['error'] = "Provide bus model";
                return $response->withJson($results, 200);
            }
            if ( !is_numeric($year) ){
                $results['error'] = "Provide bus year";
                return $response->withJson($results, 200);
            }

        }
        


        


        //
        $description = ($description) ? $description : null;
        $bus_code = ($bus_code) ? $bus_code : null;
        $precio = ($precio) ? $precio : 0;
        //echo $description; exit;



        //
        $add_edit_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertBus(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "BUS_ALREADY_EXISTS" => "bus $nombre already exists"
            ],
            "debug" => true,
            "params" => function() use(
                    $nombre, $url, $bus_code, $description, 
                    $tipo_bus, $make_id, $model_id, $year, 
                    $precio, $user_id, $active, $sucursal_id, 
                    $app_id, $record_id, &$add_edit_record_id){
                //
                return [
                    //
                    array($nombre, SQLSRV_PARAM_IN),
                    array($url, SQLSRV_PARAM_IN),
                    array($bus_code, SQLSRV_PARAM_IN),
                    array($description, SQLSRV_PARAM_IN),
                    //
                    array($tipo_bus, SQLSRV_PARAM_IN),
                    array($make_id, SQLSRV_PARAM_IN),
                    array($model_id, SQLSRV_PARAM_IN),
                    array($year, SQLSRV_PARAM_IN),
                    //
                    array($precio, SQLSRV_PARAM_IN),
                    array($user_id, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    array($sucursal_id, SQLSRV_PARAM_IN),
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($record_id, SQLSRV_PARAM_IN),
                    array(&$add_edit_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        if ($add_edit_record_id){
            //
            $results['id'] = $add_edit_record_id;
            //
            if ( $img_section && $file_extension ){
                //
                if ( $record_id ){
                    $results['update_img_results'] = self::updateBusImage($app_id, $img_section, $file_extension, $add_edit_record_id);
                }
                //
                else {
                    $results['add_img_results'] = self::updateBusImage($app_id, $img_section, $file_extension, $add_edit_record_id, true);
                }
            }
        }

        //
        return $response->withJson($results, 200);
    }







    

    //
    public function UpdatePriceType($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];
        //echo $sucursal_id; exit;


        //
        $record_id = $args['id'];


        //
        $results = array();
        //
        $multiple_prices = Helper::safeVar($request->getParsedBody(), 'multiple_prices') ? 1 : 0;



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    buses
                  
                  Set 
                    --
                    multiple_prices = ?
                    
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $multiple_prices,
                //
                $account_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }









    //
    public function DeleteRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }



        //
        $results = Query::DoTask([
            "task" => "delete",
            "debug" => true,
            "stmt" => "Delete FROM buses Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $id
            ],
            "parse" => function($updated_rows, &$query_results) use($id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$id;
            }
        ]);
        //var_dump($results); exit;



        //
        return $response->withJson($results, 200);
    }









    //
    public static $main_img_width = 784;
    public static $main_img_height = 545;
    //
    public static $thumb_img_width = 196;
    public static $thumb_img_height = 136;


    public static function updateBusImage($app_id, $img_section, $file_extension, $bus_id, $add_gallery_img = false){
        //dd($img_section); exit;

        //
        $results = array();
        //
        $files_path = Paths::$path_buses . DS . $bus_id;


        //
        $main_files_path = $files_path .DS . "main";
        //echo $main_files_path; exit;
        //
        if (!is_dir($files_path)){
            mkdir($files_path);
        }
        //
        if (!is_dir($main_files_path)){
            mkdir($main_files_path);
        }


        //
        $remove_img = ($add_gallery_img) ? false : true;




        //$img_nombre = $img_section->getClientFilename();
        $new_img_name = "orig." . $file_extension;
        $new_img_thumb_name = "thumb." . $file_extension;

        //---- MAIN
        if ( !ImagesHandler::smart_resize_image($img_section->file, null, self::$main_img_width, self::$main_img_height, false, $main_files_path . DS . $new_img_name, false) ){
            $results['main_img_resize'] = "unable to upload main file " . $new_img_name;
        } else {
            $results['main_img_resize'] = "upload main file {$new_img_name} success";
        }

        //---- THUMB
        if ( !ImagesHandler::smart_resize_image($img_section->file, null, self::$thumb_img_width, self::$thumb_img_height, false, $main_files_path . DS . $new_img_thumb_name, $remove_img) ){
            $results['thumb_img_resize'] = "unable to upload thumb file " . $new_img_thumb_name;
        } else {
            $results['thumb_img_resize'] = "upload main file {$new_img_thumb_name} success";
        }
        // self::$thumb_img_width, self::$thumb_img_height,
        //
        $results['main_img'] = Buses::UpdateImgExt($file_extension, $app_id, $bus_id);



        //------------------------------ GALLERY IMG
        //echo $add_gallery_img; exit;
        if ($add_gallery_img){
            //
            $results['gallery_insert'] = BusesGallery::Create(
                array(
                    "app_id" => $app_id,
                    "bus_id" => $bus_id
                ));

            //var_dump($results['gallery_insert']); exit;
            if ( isset($results['gallery_insert']['error']) && $results['gallery_insert']['error'] ){
                $results['error'] = $results['gallery_insert']['error'];
                return $results;
            }
            //
            $bus_gallery_id = $results['gallery_insert']['id'];


            //
            $gallery_files_path = $files_path .DS . "gallery";
            //
            if (!is_dir($gallery_files_path)){
                mkdir($gallery_files_path);
            }

            //$img_nombre = $img_section->getClientFilename();
            $new_img_name = "orig-" . $bus_gallery_id . "." . $file_extension;
            $new_img_thumb_name = "thumb-" . $bus_gallery_id . "." . $file_extension;


            // file
            if ( !ImagesHandler::smart_resize_image($img_section->file, null, self::$main_img_width, self::$main_img_height, false, $gallery_files_path . DS . $new_img_name, false) ){
                $results['gallery_main_img_resize'] = "unable to upload main file " . $new_img_name;
            }
            if ( !ImagesHandler::smart_resize_image($img_section->file, null, self::$thumb_img_width, self::$thumb_img_height, false, $gallery_files_path . DS . $new_img_thumb_name, true) ){
                $results['gallery_thumb_img_resize'] = "unable to upload thumb file";
            }


            //
            $results['gallery_update'] = BusesGallery::UpdateImgExt($file_extension, $app_id, $bus_id, $bus_gallery_id);
        }


        //
        return $results;
    }




}