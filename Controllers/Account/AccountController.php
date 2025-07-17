<?php
namespace Controllers\Account;

//
use App\Locations\CatPaises;
use App\Paths;

//
use Controllers\BaseController;
use App\App;
use App\Customers\Customers;

use Helpers\Geolocation;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;




//
class AccountController extends BaseController
{





   


    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/account/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }

 





    


    
    
    

    //
    public function postUpdateBasicInfo($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $sale_type_id = $ses_data['sale_type_id'];
        $customer_id = $ses_data['id'];



        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;

        //
        $name = Helper::getFirstTextOnly($v->safeVar($body_data, 'name'));
        $password = $v->safeVar($body_data, 'password');
        //echo " $name "; exit;


        //
        if ( !$v->validateString([2, 128], $name) ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        //
        if (!$password){
            $results['error'] = "Provide password"; return $response->withJson($results, 200);
        }



        //
        $info_results = Customers::GetRecordByIdAndPassword($customer_id, $password);
        //dd($info_results); exit;
        if ( isset($info_results['error']) && $info_results['error'] ){
            $results['error'] = $info_results['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($info_results['id']) && $info_results['id']) ){
            $results['error'] = "Invalid password";
            return $response->withJson($results, 200);
        }



        //
        $img_section = null;
        $file_type_ext = null;
        //
        if ( isset($uploadedFiles['cust_img']) && $uploadedFiles['cust_img'] && $uploadedFiles['cust_img']->getError() === UPLOAD_ERR_OK ) {
            //
            $img_section = $uploadedFiles['cust_img'];
            //dd($img_section); exit;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_type_ext = strtolower($file_extension);
            //echo $file_type_ext; exit;
            // Validate type of file
            if( !in_array($file_type_ext, ['jpeg', 'jpg', 'png', 'gif']) ){
                //
                $results['error'] = "Solo se permiten archivos Jpeg, Png o Gif";
                return $response->withJson($results, 200);
            }
            //echo "Ok"; exit;
        } else {
            //$results['error'] = "Se require la imagen del usuario";
            //return $response->withJson($results, 200);
        }
        //dd($img_section); exit;
        //echo $file_type_ext; exit;






        


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update customers 
                       Set
                           
						name = ?
						   
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $name,
                //
                $customer_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
            }
        ]);
        //dd($update_results); exit;




        //
        if ($img_section && $file_type_ext){


            //
            $customer_profile_path = Customers::getCustomerSectionPath($customer_id, "profile");
            $customer_profile_url = FULL_DOMAIN."/files/customers/".$customer_id."/profile";
            //echo " $customer_profile_path --- $customer_profile_url "; exit;


            // product && product original image
            $new_img_name = "me.{$file_type_ext}";
            //echo $new_img_name; exit;


            // elimina archivos de imagenes previos
            if ( is_file($customer_profile_path.DS."me.png") ){unlink($customer_profile_path.DS."me.png");}
            if ( is_file($customer_profile_path.DS."me.jpg") ){unlink($customer_profile_path.DS."me.jpg");}
            if ( is_file($customer_profile_path.DS."me.jpeg") ){unlink($customer_profile_path.DS."me.jpeg");}
            if ( is_file($customer_profile_path.DS."me.gif") ){unlink($customer_profile_path.DS."me.gif");}


            //
            if ( ImagesHandler::resizeImage(
                $img_section->file,
                250,
                250,
                $customer_profile_path.DS.$new_img_name
            )){

                //
                $results['store_img_ext'] = Query::DoTask([
                    "task" => "update",
                    "stmt" => "
                   Update customers 
                       Set
                       
						img_ext = ?
						
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                ",
                    "params" => [
                        //
                        $file_type_ext,
                        //
                        $customer_id
                    ],
                    "parse" => function($updated_rows, &$query_results){
                        $query_results['affected_rows'] = $updated_rows;
                    }
                ]);
                //
                $update_results['customer_img_updated'] = true;

            } else {
                $update_results['err_msg'] = "Error, Unable to upload customer image";
            }
            /*
            //
            if (move_uploaded_file($img_section->file, $products_path.DS.$orig_img_name)){
                $insert_results['prod_orig_img_updated_ok'] = true;
            } else {
                $insert_results['msg'] = "Unable to upload orig product image";
            }
            */
            //
            if (is_file($img_section->file)){
                unlink($img_section->file);
            }
        } else {
            $update_results['err_msg'] = "No image provided, not update done";
        }



        // Datos necesarios para que se actualicen en el cliente
        $update_results['updateData'] = Customers::getAuthData($customer_id);

        //
        return $response->withJson($update_results, 200);
    }









    

    //
    public function GetRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];

        //
        $results = Query::Single("
            select 
               
                t.*,
                acct.company_name,
                acct.contact_name,
                acct.app_folder_name,
                acct.datetime_created

            from
                accounts_apps t 
            left Join accounts acct On acct.id = t.account_id
            Where t.account_id = ?
            And t.id = ?
            ", [$account_id, $app_id]);


        //
        return $response->withJson($results, 200);
    }








    







    //
    public function PostUpdateCustomerImage($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $customer_id = $ses_data['id'];


        //
        $results = array();


        //
        $title = Helper::safeVar($request->getParsedBody(), 'title');
        $file = Helper::safeVar($request->getParsedBody(), 'file');
        //echo "$title $file"; exit;
        //
        list($type, $data) = explode(';', $file);
        list(, $data) = explode(',', $data);
        $file_data = base64_decode($data);
        //echo $file_data; exit;



        // Get file mime type
        $finfo = finfo_open();
        $file_mime_type = finfo_buffer($finfo, $file_data, FILEINFO_MIME_TYPE);





        //
        $file_type_ext = null;
        //
        if($file_mime_type == 'image/jpeg' || $file_mime_type == 'image/jpg'){
            $file_type_ext = 'jpeg';
        }
        else if($file_mime_type == 'image/jpg'){
            $file_type_ext = 'jpg';
        }
        else if($file_mime_type == 'image/png'){
            $file_type_ext = 'png';
        }
        else if($file_mime_type == 'image/gif') {
            $file_type_ext = 'gif';
        }

        //
        $file_name = uniqid() . '.' . $file_type_ext;
        $tmp_file_path = PATH_PUBLIC.DS."tmp";

        //
        $main_file = $tmp_file_path.DS.$file_name;

        // Validate type of file
        if( !in_array($file_type_ext, ['jpeg', 'jpg', 'png', 'gif']) ){
            //
            $results['error'] = "Solo se permiten archivos Jpeg, Png o Gif";
            return $response->withJson($results, 200);
        }




        //
        $customer_profile_path = Customers::getCustomerSectionPath($customer_id, "profile");
        $customer_profile_url = FULL_DOMAIN."/files/customers/".$customer_id."/profile";
        //echo " $customer_profile_path --- $customer_profile_url "; exit;



        //
        if ( @file_put_contents($main_file, $file_data) ){


            //
            $update_results = Customers::UpdateImgExt($file_type_ext, $customer_id);
            //var_dump($update_results); exit;
            if ( isset($update_results['error']) && $update_results['error'] ){
                $results['error'] = $update_results['error'];
                return $response->withJson($results, 200);
            }


            //
            $new_img_name = "me.".$file_type_ext;
            $orig_img_name = "me-orig.".$file_type_ext;


            // debug image output
            //echo $customer_profile_path.DS.$new_img_name; exit;


            //
            if ( ImagesHandler::resizeImage(
                $main_file,
                250,
                250,
                $customer_profile_path.DS.$new_img_name
            )){
                //
                unlink($main_file);
                //
                $results['id'] = $customer_id;
                //
                return $response->withJson($results, 200);
            } else {
                //
                unlink($main_file);
                //
                $results['error'] = "No se pudo crear el archivo";
                return $response->withJson($results, 200);
            }
        }




        $results['error'] = "No se pudo crear el archivo";
        return $response->withJson($results, 200);
    }










    public static function getImage($file_type_ext, $imagePath){
        if ($file_type_ext === 'jpeg' || $file_type_ext === 'jpg') {
            return imagecreatefromjpeg($imagePath);
        } elseif ($file_type_ext === 'png') {
            return imagecreatefrompng($imagePath);
        } elseif ($file_type_ext === 'gif') {
            return imagecreatefromgif($imagePath);
        }
    }


    public static function image_fix_orientation(&$image, $filename){
        //
        $exif = exif_read_data($filename);;
        //
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, 90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, -90, 0);
                    break;
            }
        }
    }





    



}
