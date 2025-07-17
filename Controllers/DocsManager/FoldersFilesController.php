<?php
namespace Controllers\DocsManager;

//
use App\Accounts\Accounts;
use App\DocsManager\Folders;
use App\DocsManager\FoldersFiles;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;
use Stripe\Account;


//
class FoldersFilesController extends BaseController
{




    //
    public function GetAll($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $folder_id = $args['folder_id'];


        //$results = FoldersFiles::GetAll($app_id, $folder_id);
        //var_dump($results); exit;

        //
        $account_results = Accounts::GetRecordById($app_id);
        $folder_info = Folders::GetRecordById($app_id, $folder_id);


        //
        $account_path = PATH_PUBLIC.DS."sites".DS.$account_results['app_folder_name'];
        $folder_path = $account_path.DS.$folder_info['folder_name'];
        $short_folder_path = $folder_info['folder_name'];
        //echo $folder_path; exit;



        //
        $arr_files = array();


        if (is_dir($folder_path)){
            //
            $di = new \RecursiveDirectoryIterator($folder_path);
            foreach (new \RecursiveIteratorIterator($di) as $file_path => $file) {
                //var_dump($file->getPath()); exit;

                //
                $file_name_expl = explode('\\', $file_path);
                //var_dump($filename_expl); exit;
                $file_name = $file_name_expl[count($file_name_expl)-1];

                //
                if ( !($file_name === "." || $file_name === "..") ){
                    //
                    array_push($arr_files, [
                        "app_url" =>  "/sites/".$account_results['app_folder_name'],
                        "file_url" =>  $short_folder_path."/".$file_name,
                        "file_name" =>  $file_name,
                        "file_size" =>  $file->getSize() . " bytes",
                    ]);
                }
            }
            //var_dump($arr_files); exit;
        }


        //
        return $response->withJson($arr_files, 200);
    }















    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = FoldersFiles::GetRecordById( $app_id, $args['folder_id'], $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function UpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $folder_id = $args['folder_id'];




        //
        $uploadedFiles = $request->getUploadedFiles();



        //
        if ( isset($uploadedFiles['file']) &&
            ( $uploadedFile = $uploadedFiles['file'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {

            //
            $img_section = $uploadedFile;
            //
            $file_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $img_section->getClientFilename());
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
            //
            $file_name_ext = $file_name.".".$file_extension;
            //echo $file_name_ext; exit;


            //
            if ( !($img_section && in_array($file_extension, Helper::$valid_img_types)) ){
                //$results['error'] = "se requiere un formato de imagen valido";
                //return $response->withJson($results, 200);
            }



            //
            $folder_info = Folders::GetRecordById($app_id, $folder_id);
            $folder_path = Accounts::getPath($app_id).DS.$folder_info['folder_name'];
            //echo $folder_path; exit;


            //
            if ( !@move_uploaded_file($img_section->file, $folder_path.DS.$file_name_ext) ){
                //
                $error = error_get_last();
                //
                if (preg_match('/Directory not empty/', $error['message'])) {
                    $error = 'Directorio no esta vacio';
                }
                //
                $results['error'] = $error['message'];
                return $response->withJson($results, 400);
            }


            //
            $results['success'] = true;
            return $response->withJson($results, 200);
        }


        //
        $results['error'] = "Proporciona el archivo";
        return $response->withJson($results, 200);
    }










    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        $folder_id = $args['folder_id'];





        //
        $results = array();


        //
        $file_path = Helper::safeVar($request->getParsedBody(), 'file_path');
        //
        if ( !$file_path ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }


        //
        $acct_info = Accounts::GetRecordById($app_id);
        //var_dump($acct_info); exit;


        //
        $acct_folder_path = PATH_PUBLIC.DS."sites".DS.$acct_info['app_folder_name'];
        //echo $acct_folder_path.DS.$file_path; exit;


        //
        if ( !@unlink($acct_folder_path.DS.$file_path) ){
            //
            $error = error_get_last();
            //
            if (preg_match('/Directory not empty/', $error['message'])) {
                $error = 'Directorio no esta vacio';
            }
            //
            $results['error'] = $error['message'];
            return $response->withJson($results, 200);
        }
        //
        else {
            $results['success'] = "removed path " . $file_path;
            return $response->withJson($results, 200);
        }
    }




}