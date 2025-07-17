<?php
namespace Controllers\ChatMessages;


//
use App\AwsConfig;
use App\PartsRequests\PartsRequests;
use Aws\ApiGatewayManagementApi;
use Aws\Comprehend\ComprehendClient;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;
use Aws\S3\Exception\S3Exception;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;
use Helpers\ValidatorHelper;

use Aws\S3\S3Client;



//
class UsersChatMessages extends BaseController
{














    //
    public function postUploadImages($request, $response, $args) {

        //
        $results = [];

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $user_id = $ses_data['id'];
        $sale_type_id = $ses_data['sale_type_id'];


        //
        if ( !($sale_type_id === PROD_TYPE_CUSTOMER_ID || $sale_type_id === PROD_TYPE_STORE_ID) ){
            $results['error'] = "Provide valid type";
            return $response->withJson($results, 200);
        }


        //
        $is_store = false;
        if ( $sale_type_id === PROD_TYPE_STORE_ID ){
            $is_store = true;
        }


        //
        $v = new ValidatorHelper();

        //
        $body_data = $request->getParsedBody();





        //
        $store_part_request_id = $v->safeVar($body_data, 'id');
        $action_type = $v->safeVar($body_data, 'action_type');
        //echo "$store_part_request_id"; exit;




        //
        $store_parts_request = Query::Single("select * from ViewStoresPartsRequests where id = ?", [
            $store_part_request_id
        ]);
        //dd($store_parts_request); exit;






        //
        if ( !($store_parts_request && isset($store_parts_request['id'])) ){
            //
            $results["error"] = 'No existe la solicitud';
            return $response->withJson($results, 200);
        }
        else if ( $store_parts_request && isset($store_parts_request['deal_datetime']) ){
            //
            if ( $is_store ){
                $results["error"] = 'Solicitud ya ha sido cerrada por el cliente';
            } else {
                $results["error"] = 'La Solicitud ya ha sido cerrada';
            }
            //
            return $response->withJson($results, 200);
        }

        //
        $the_img = null;
        $file_name = null;
        $file_size = 0;
        $file_type_ext = null;
        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;
        //
        if ( isset($uploadedFiles['chat_imgs']) && $uploadedFiles['chat_imgs'] && $uploadedFiles['chat_imgs']->getError() === UPLOAD_ERR_OK ) {

            //
            $the_img = $uploadedFiles['chat_imgs'];
            //dd($the_img); exit;

            //
            $exif = exif_read_data($the_img->file);
            //dd($exif); exit;
            //
            $file_size = $exif['FileSize'];
            $orig_file_name = $exif['FileName'];
            $orig_file_media_type = $exif['MimeType'];
            $orig_file_extension  = Helper::getExtByMime($orig_file_media_type);
            //echo " $file_size $orig_file_name $file_media_type $orig_file_extension"; exit;


            // Validate type of file
            if( !in_array($orig_file_extension, ['jpeg', 'jpg', 'png', 'gif']) ){
                //
                $results['error'] = "Solo se permiten archivos Jpeg, Png o Gif";
                return $response->withJson($results, 200);
            }
            //echo "Ok"; exit;
        } else {
            $results['error'] = "Se require una imagen valida";
            return $response->withJson($results, 200);
        }
        //dd($the_img); exit;
        //echo " $file_name $file_type_ext "; exit;





        //
        $store_id = $store_parts_request['store_id'];
        $customer_id = $store_parts_request['customer_id'];
        $store_name = $store_parts_request['store_name'];
        $customer_name = $store_parts_request['customer_name'];
        //echo "$store_id $customer_id $store_name $customer_name"; exit;








        //
        $is_valid = AwsConfig::checkImagenApropiada($the_img->file);
        //
        if ($is_valid && isset($is_valid['error'])){
            $results['error'] = $is_valid['error'];
            return $response->withJson($results, 200);
        }
        //
        else if ($is_valid && isset($is_valid['label'])){
            $results['error'] = "imagen inapropiada: " . $is_valid['label'];
            return $response->withJson($results, 200);
        }











        // Instantiate an Amazon S3 client
        $s3 = new S3Client([
            'version' => AwsConfig::$version,
            'region'  => AwsConfig::$region,
            'credentials' => new Credentials(AwsConfig::$access_key_id, AwsConfig::$secret_access_key)
        ]);
        //dd($s3); exit;



        //
        $s3_file_link = null;

        // Upload file to S3 bucket
        try {

            /*
             * File new path & name
             * */
            //
            $imgs_save_path = PATH_PUBLIC.DS.'files'.DS.'chat';
            $new_file_name = uniqid("img-");



            //
            $new_corrected_img = Helper::corregirRotacionImagen($the_img, $imgs_save_path);
            //echo $new_corrected_img; exit;
            //
            $new_resized_img = Helper::resizeImage($new_corrected_img, $file_size, $orig_file_extension, $imgs_save_path);
            //echo $new_resized_img; exit;


            //
            if ($new_resized_img){


                //
                $result = $s3->putObject([
                    'Bucket' => AwsConfig::$bucket_name,
                    'Key'    => $new_file_name,
                    'SourceFile' => $new_resized_img,
                    'ContentType' => $orig_file_media_type,
                    'ContentDisposition' => 'inline'
                    //'MetaData'    => $metadata,
                ]);

                //
                unlink($new_resized_img);

            } else {

                //
                $result = $s3->putObject([
                    'Bucket' => AwsConfig::$bucket_name,
                    'Key'    => $new_file_name,
                    'SourceFile' => $new_corrected_img,
                    'ContentType' => $orig_file_media_type,
                    'ContentDisposition' => 'inline'
                    //'MetaData'    => $metadata,
                ]);

                //
                unlink($new_corrected_img);
            }



            $result_arr = $result->toArray();
            //dd($result_arr); exit;


            //
            if(!empty($result_arr['ObjectURL'])) {
                $s3_file_link = $result_arr['ObjectURL'];
            } else {
                //
                $results["error"] = 'No se pudo subir el archivo: Upload Failed! S3 Object URL not found';
                return $response->withJson($results, 200);
            }


        } catch (S3Exception $e) {
            //
            $api_error = $e->getMessage();
            $results["error"] = 'No se pudo subir el archivo: ' . $api_error;
            return $response->withJson($results, 200);
        }
        //
        //echo $s3_file_link; exit;




        //
        $the_message = $s3_file_link;
        //
        if ($is_store){
            $the_res = PartsRequests::insertNotific($customer_id, $store_id, null, 1, 1, 1, 1, $the_message, null, $store_part_request_id);
        } else {
            $the_res = PartsRequests::insertNotific($customer_id, $store_id, 1, null, 1, 1, 1, $the_message, null, $store_part_request_id);
        }
        //dd($the_res); exit;
        //
        if ($the_res && isset($the_res['error'])){
            $results["error"] = $the_res['error'];
            return $response->withJson($results, 200);
        }

        //
        $notific_res = Query::Single("select * from ViewNotifications where id = ?", [
            $the_res['id']
        ]);
        //dd($notific_res); exit;





        //
        $api = new ApiGatewayManagementApi\ApiGatewayManagementApiClient([
            'endpoint' => AwsConfig::$api_gwy_ws_endpoint,
            'version' => AwsConfig::$version,
            'region'  => AwsConfig::$region,
            'credentials' => new Credentials(AwsConfig::$access_key_id, AwsConfig::$secret_access_key)
        ]);
        //dd($api); exit;

        //
        if ($is_store){

            //
            $user_connections = Query::Multiple("select Top 2 connection_id from customers_websockets_ids where customer_id = ? order by last_updated_datetime desc", [
                $customer_id
            ]);
            //
            $the_msg = $store_name . " te ha enviado una imagen";

        } else {
            //
            $user_connections = Query::Multiple("select Top 2 connection_id from stores_websockets_ids where store_id = ? order by last_updated_datetime desc", [
                $store_id
            ]);
            //
            $the_msg = $customer_name . " te ha enviado una imagen";
        }




        //dd($user_connections); exit;
        if ($user_connections && is_array($user_connections)){
            foreach($user_connections as $item){

                //
                $connectionId = $item['connection_id'];
                //echo $connectionId; exit;


                //
                try {

                    /*
                    //
                    $conn_res = $api->getConnection([
                        'ConnectionId' => $connectionId
                    ]);
                    //dd($conn_res);
                    */

                    //
                    $store_msg_data = [
                        "action_type" => $action_type,
                        "msg"=> $the_msg,
                        "new_item"=> $notific_res
                    ];
                    //
                    $json_data = json_encode($store_msg_data);
                    //echo $json_data; exit;

                    //
                    $val = $api->postToConnection(array('ConnectionId' => $connectionId, 'Data' =>  $json_data));
                    //dd($val);

                } catch (AwsException $e) {
                    // Maneja la excepción aquí
                    //echo "Error: " . $e->getMessage();
                }

            }

        }
        //echo "end"; exit;





        /*
         * PARA LEER UN BUCKET
         * */
        /*
        //
        $objects = $s3->listObjects([
            'Bucket' => $bucket_name
        ]);
        //
        $object_link = "";
        //echo "********* "; dd($objects); exit;//
        foreach($objects['Contents'] as $object){
            //dd($object); exit;

            // Bucket publico lo abre sin firma
            $results['the_link'] = $s3->getObjectUrl($bucket_name, $object['Key']);

            // con firma
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket_name,
                'Key' => $object['Key'],
                'ContentDisposition' => 'inline'
            ]);
            //dd($cmd); exit;
            $request = $s3->createPresignedRequest($cmd, '+20 minutes');
            // Get the actual presigned-url
            $results['the_link'] = (string)$request->getUri();
        }
        */


        //
        $results['id'] = $store_part_request_id;
        $results['new_item'] = $notific_res;


        //
        return $response->withJson($results, 200);
    }








    //
    public function postSendMessage($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $user_id = $ses_data['id'];
        $sale_type_id = $ses_data['sale_type_id'];


        //
        if ( !($sale_type_id === PROD_TYPE_CUSTOMER_ID || $sale_type_id === PROD_TYPE_STORE_ID) ){
            $results['error'] = "Provide valid type";
            return $response->withJson($results, 200);
        }


        //
        $is_store = false;
        if ( $sale_type_id === PROD_TYPE_STORE_ID ){
            $is_store = true;
        }


        //
        $v = new ValidatorHelper();
        //
        $body_data = $request->getParsedBody();




        //
        $store_part_request_id = $v->safeVar($body_data, 'id');
        $action_type = $v->safeVar($body_data, 'action_type');
        $the_message = $v->safeVar($body_data, 'message');
        //echo " $store_part_request_id $the_message "; exit;



        //
        if ( !is_numeric($store_part_request_id) ){
            $results["error"] = 'Provide part request id';
            return $response->withJson($results, 200);
        }
        if ( !$action_type ){
            $results["error"] = 'Provide action type';
            return $response->withJson($results, 200);
        }
        if ( !$the_message ){
            $results["error"] = 'Provide the message';
            return $response->withJson($results, 200);
        }






        //
        $store_parts_request = Query::Single("select * from ViewStoresPartsRequests where id = ?", [
            $store_part_request_id
        ]);
        //dd($store_parts_request); exit;


        //
        if ( !($store_parts_request && isset($store_parts_request['id'])) ){
            //
            $results["error"] = 'No existe la solicitud';
            return $response->withJson($results, 200);
        }
        else if ( $store_parts_request && isset($store_parts_request['deal_datetime']) ){
            //
            if ( $is_store ){
                $results["error"] = 'Solicitud ya ha sido cerrada por el cliente';
            } else {
                $results["error"] = 'La Solicitud ya ha sido cerrada';
            }
            //
            return $response->withJson($results, 200);
        }



        //
        $store_id = $store_parts_request['store_id'];
        $customer_id = $store_parts_request['customer_id'];
        $store_name = $store_parts_request['store_name'];
        $customer_name = $store_parts_request['customer_name'];
        //echo "$store_id $customer_id $store_name $customer_name"; exit;



        //
        $results = [];




        //
        $is_valid = AwsConfig::checkTextoInapropiado($the_message);
        //
        if ($is_valid && isset($is_valid['error'])){
            $results['error'] = $is_valid['error'];
            return $response->withJson($results, 200);
        }
        //
        else if ($is_valid && isset($is_valid['text'])){
            $results['error'] = $is_valid['text'];
            return $response->withJson($results, 200);
        }
        //echo "Ok"; exit;






        //
        $encontrada = false;
        //
        foreach (AwsConfig::$arr_bad_words as $bad_word) {
            //
            if (stripos($the_message, $bad_word) !== false) {
                $encontrada = true;
                break;
            }
        }
        //
        if ($encontrada) {
            $results['error'] = "prohibido";
            return $response->withJson($results, 200);
        }






        //
        if ($is_store){
            $the_res = PartsRequests::insertNotific($customer_id, $store_id, null, 1, 1, 1, 1, $the_message, null, $store_part_request_id);
        } else {
            $the_res = PartsRequests::insertNotific($customer_id, $store_id, 1, null, 1, 1, 1, $the_message, null, $store_part_request_id);
        }
        //dd($the_res); exit;
        //
        if ($the_res && isset($the_res['error'])){
            $results["error"] = $the_res['error'];
            return $response->withJson($results, 200);
        }

        //
        $notific_res = Query::Single("select * from ViewNotifications where id = ?", [
            $the_res['id']
        ]);
        //dd($notific_res); exit;






        //
        $api = new ApiGatewayManagementApi\ApiGatewayManagementApiClient([
            'endpoint' => AwsConfig::$api_gwy_ws_endpoint,
            'version' => AwsConfig::$version,
            'region'  => AwsConfig::$region,
            'credentials' => new Credentials(AwsConfig::$access_key_id, AwsConfig::$secret_access_key)
        ]);
        //dd($api); exit;


        //
        if ($is_store){
            //
            $user_connections = Query::Multiple("select Top 2 connection_id from customers_websockets_ids where customer_id = ? order by last_updated_datetime desc", [
                $customer_id
            ]);
        } else {
            //
            $user_connections = Query::Multiple("select Top 2 connection_id from stores_websockets_ids where store_id = ? order by last_updated_datetime desc", [
                $store_id
            ]);
        }





        //dd($user_connections); exit;
        if ($user_connections && is_array($user_connections)){
            foreach($user_connections as $item){

                //
                $connectionId = $item['connection_id'];
                //echo $connectionId; exit;


                //
                try {

                    /*
                    //
                    $conn_res = $api->getConnection([
                        'ConnectionId' => $connectionId
                    ]);
                    //dd($conn_res);
                    */

                    //
                    if ($is_store){
                        $msg_data = [
                            "action_type" => $action_type,
                            "msg"=> $store_name . ": " . $the_message,
                            "new_item"=> $notific_res
                        ];
                    } else {
                        $msg_data = [
                            "action_type" => $action_type,
                            "msg"=> $customer_name . ": " . $the_message,
                            "new_item"=> $notific_res
                        ];
                    }


                    //
                    $json_data = json_encode($msg_data);
                    //echo $json_data; exit;

                    //
                    $val = $api->postToConnection(array('ConnectionId' => $connectionId, 'Data' =>  $json_data));
                    //dd($val);



                } catch (AwsException $e) {
                    // Maneja la excepción aquí
                    //echo "Error: " . $e->getMessage();
                }

            }

        }
        //echo "end"; exit;






        //
        $results['id'] = $store_part_request_id;
        $results['new_item'] = $notific_res;


        //
        return $response->withJson($results, 200);
    }
















}
