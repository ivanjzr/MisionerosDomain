<?php
namespace Controllers\Customers;


//
use App\AwsConfig;
use App\PartsRequests\PartsRequests;
use App\Paths;
use App\Stores\Stores;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sdk;
use Aws\Sqs\SqsClient;
use Controllers\BaseController;
use Google\Service\Compute\Help;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class CustomersPartsRequestsController extends BaseController
{







    //
    public function postRequestPart($request, $response, $args) {

        //
        $results = [];

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $customer_id = $ses_data['id'];




        //
        $v = new ValidatorHelper();
        //
        $body_data = $request->getParsedBody();



        //
        $visitor_id = $v->safeVar($body_data, 'vid');
        //
        $marca_id = $v->safeVar($body_data, 'marca_id');
        $modelo_anio = $v->safeVar($body_data, 'modelo_id');
        $submarca_id = $v->safeVar($body_data, 'submarca_id');
        $cilindraje_id = $v->safeVar($body_data, 'cilindraje_id');
        $parts_info = $v->safeVar($body_data, 'parts_info');



        //
        if ( !$visitor_id ){
            $results["error"] = 'Provide visitor'; return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($marca_id) ){
            $results["error"] = 'Provide marca'; return $response->withJson($results, 200);
        }
        if ( !is_numeric($modelo_anio) ){
            $results["error"] = 'Provide modelo anio'; return $response->withJson($results, 200);
        }
        if ( !is_numeric($submarca_id) ){
            $results["error"] = 'Provide submarca'; return $response->withJson($results, 200);
        }
        if ( !is_numeric($cilindraje_id) ){
            $results["error"] = 'Provide cilindraje'; return $response->withJson($results, 200);
        }
        if ( !$parts_info ){
            $results["error"] = 'Provide parts_info'; return $response->withJson($results, 200);
        }



        //
        $the_img = null;
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


        } else {
            /*
            $results['error'] = "Se require una imagen valida";
            return $response->withJson($results, 200);
            */
        }
        //dd($the_img); exit;
        //echo " $file_name $file_type_ext "; exit;







        //
        $encontrada = false;
        //
        foreach (AwsConfig::$arr_bad_words as $bad_word) {
            //
            if (stripos($parts_info, $bad_word) !== false) {
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
        $is_valid = AwsConfig::checkTextoInapropiado($parts_info);
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








        // Instantiate an Amazon S3 client
        $s3 = new S3Client([
            'version' => AwsConfig::$version,
            'region'  => AwsConfig::$region,
            'credentials' => new Credentials(AwsConfig::$access_key_id, AwsConfig::$secret_access_key)
        ]);
        //dd($s3); exit;



        //
        $s3_file_link = null;

        //
        if ($the_img && $the_img->file){
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
                    //
                    $s3_file_link = $result_arr['ObjectURL'];
                } else {
                    //
                    $results["error"] = 'No se pudo subir el archivo: S3 Object Url Not found';
                    return $response->withJson($results, 200);
                }


            } catch (S3Exception $e) {
                //
                $api_error = $e->getMessage();
                $results["error"] = 'No se pudo subir el archivo: ' . $api_error;
                return $response->withJson($results, 200);
            }

        }








        //
        $request_part = PartsRequests::RequestPart(
            $customer_id,
            $visitor_id,
            $marca_id,
            $modelo_anio,
            $submarca_id,
            $cilindraje_id,
            $parts_info,
            $s3_file_link
        );
        //dd($request_part); exit;
        if ( isset($request_part['error']) && $request_part['error'] ){
            $results['error'] = $request_part['error'];
            return $response->withJson($results, 200);
        }
        //
        $new_request_id = (isset($request_part['id']) && $request_part['id']) ? $request_part['id'] : null;






        //
        $action_type = "request-part";
        $MessageGroupId = "part-req-id-" . $new_request_id;


        //set_time_limit(300);
        //ini_set('memory_limit', '-1');

        //
        $queueUrl = 'https://sqs.us-west-2.amazonaws.com/453360531191/SqsApSendMsgQueue.fifo';
        //
        $arr_promises = [];
        $SqsSendResults = [];
        //
        $arr_stores = Query::Multiple("select t.id from stores_parts_requests t Where t.part_request_id = ?", [$new_request_id]);
        //dd($arr_stores); exit;
        //
        if ($arr_stores && is_array($arr_stores)){
            foreach($arr_stores as $item_store){
                //
                $store_part_request_id = $item_store['id'];
                //echo $store_id; exit;

                //
                try {

                    //
                    $client = new SqsClient([
                        'version' => AwsConfig::$version,
                        'region'  => AwsConfig::$region,
                        'credentials' => new Credentials(AwsConfig::$access_key_id, AwsConfig::$secret_access_key)
                    ]);
                    //
                    $queueParams = [
                        'QueueUrl' => $queueUrl,
                        'MessageGroupId' => $MessageGroupId,
                        'MessageBody' => json_encode([
                            'id' => $store_part_request_id,
                            'action_type' => $action_type
                        ]),
                    ];
                    //
                    $promise = $client->sendMessageAsync($queueParams)->then(function($response) use ($store_part_request_id, &$SqsSendResults){
                        //dd($response);
                        if (isset($response['MessageId'])) {
                            array_push($SqsSendResults, [
                                "StorePartRequestId" => $store_part_request_id,
                                "MessageId" => $response['MessageId']
                            ]);
                        }
                    });


                    /*
                     * HACEMOS PUSH DEL PROMISE
                     * */
                    array_push($arr_promises, $promise);



                } catch (AwsException $error) {
                    array_push($SqsSendResults, [
                        "StorePartRequestId" => $store_part_request_id,
                        "error" => $error->getMessage()
                    ]);
                }

            }
        }
        //
        foreach ($arr_promises as $promise) {
            $promise->wait();
        }

        //
        //dd($results); exit;






        //
        $cust_msg = "Tu solicitud #{$new_request_id} ha sido enviada esperando respuestas...";
        $cust_dest_url = "/account/searches/?rid={$new_request_id}";
        //
        $the_res = PartsRequests::insertNotific($customer_id, null, 1, null, 1, null, 1, $cust_msg, $cust_dest_url);
        //dd($the_res); exit;
        //
        if ($the_res && isset($the_res['error'])){
            $results["error"] = $the_res['error'];
            return $response->withJson($results, 200);
        }
        $new_notific_id = $the_res['id'];
        /*
        //
        $notific_res = Query::Single("select * from ViewNotifications where id = ?", [
            $new_notific_id
        ]);
        */

        //dd($notific_res); exit;
        //
        $ret_data = [
            "id" => $new_request_id,
            "msg" => $cust_msg,
            "dest_url" => $cust_dest_url,
            "SqsSendResults" => $SqsSendResults,
            "new_notific_id" => $new_notific_id,
        ];


        //
        return $response->withJson($ret_data, 200);
    }







    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";

        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.clave like '%$search_value%' ) Or 
                        ( t.descripcion like '%$search_value%' )
                    )";
        }
        //
        return $search_clause;
    }


    //
    public function PaginateRecords($request, $response, $args) {
        //
        $table_name = "ViewPartsRequests";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $customer_id = $ses_data['id'];



        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
                },

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
                                                 
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $customer_id
                ),

                //
                "parseRows" => function(&$row){
                    //dd($row); exit;
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function PaginateScrollRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];



        //
        $table_name = "ViewPartsRequests";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";


        // test
        //$search_clause .= " and 1=2 ";
        //sleep(1);




        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        $filter_type = $request->getQueryParam("t");
        //echo " $filter_type "; exit;




        //
        if ( $filter_type === "new" ){
            $search_clause .= " 
                And t.cancel_datetime is null
                And t.deal_datetime is null   
                And t.deal_with_store_id Is Null
            ";
        }
        //
        else if ( $filter_type === "others" ){
            $search_clause .= " And (
                ( t.cancel_datetime is not null ) Or
                ( t.deal_datetime is not null ) Or 
                ( t.deal_with_store_id is not null )
            )
            ";
        }




        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
                },
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
                                                 
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;

                    //
                    $qty_results = Query::Single("
                        Select count(*) cant_resp 
                            From ViewStoresPartsRequests t
                            -- 
                            Where t.part_request_id = ?
                            And t.customer_id = ?
                            --   
                            And EXISTS (
                                SELECT 1
                                FROM users_notifications k
                                WHERE k.store_part_request_id = t.id
                                And k.store_sent = 1
                            )
                    ", [
                        $row['id'],
                        $row['customer_id']
                    ]);
                    //dd($res); exit;
                    //
                    $row['cant_resp'] = ($qty_results && isset($qty_results['cant_resp'])) ? $qty_results['cant_resp'] : 0;

                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }











    //
    public function PaginateRecordsPrimeReact($request, $response, $args) {

        //
        $table_name = "ViewPartsRequests";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];
        //echo " $customer_id "; exit;

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        //echo " $start_record $num_records "; exit;


        //
        $selected_store_id = (int)$request->getQueryParam("sid");
        if ( is_numeric($selected_store_id) && $selected_store_id ){
            $search_clause .= " And t.store_id = {$selected_store_id}";
        }
        //echo $search_clause; exit;



        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
                },
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
                                                
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;

                    //
                    $row['arr_items'] = Query::Multiple("Select * from orders_items where order_id = ?", [
                        $row['id']
                    ], function(&$row2){
                        /**/
                    });

                    /*
                    $prod_name = "p-".$row['id'].".".$row['img_ext'];
                    //
                    $product_path = Stores::getStoreSectionPath($row['customer_id'], "products").DS.$prod_name;
                    $product_url = FULL_DOMAIN."/files/stores/".$row['customer_id']."/products/".$prod_name;
                    //
                    if (is_file($product_path)){
                        $row['prod_img'] = $product_url;
                    }
                    */

                }
            ]
        );


        //dd($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function PaginatePublicScrollRecords($request, $response, $args) {

        //
        $table_name = "ViewPartsRequests";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $customer_id = $args['customer_id'];

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");

        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.customer_id = ? {$search_clause}";
                },
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
                                                 
                                            Where t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }






    //
    public function GetListStores($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $customer_id = $ses_data['id'];
        //echo $customer_id; exit;


        //
        $stores_results = Query::Multiple("
            select 
                t.store_id as id, 
                ts.company_name,
                ts.store_title
            from orders t 
                Left Join ViewStores ts ON ts.id = t.store_id 
                    Where t.customer_id = ? 
            Group By t.store_id, ts.company_name, ts.store_title
            ",
            [$customer_id]
        );
        //dd($results); exit;


        //
        return $response->withJson($stores_results, 200);
    }






    //
    public function GetRecordById($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $customer_id = $ses_data['id'];


        //
        $record_id = $args['id'];
        //
        $results = Query::Single("Select * from ViewPartsRequests Where customer_id = ? And id = ?", [$customer_id, $record_id]);

        //
        return $response->withJson($results, 200);
    }





    //
    public function PostRequestOrder($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $customer_id = $ses_data['id'];







        //
        $results = array();

        //
        $sucursal_id = null;





        //
        $arr_order = Helper::safeVar($request->getParsedBody(), 'arr_order');
        $store_id = Helper::safeVar($request->getParsedBody(), 'store_id');
        $order_notes = Helper::safeVar($request->getParsedBody(), 'order_notes');
        //dd($arr_order); exit;
        //echo " $store_id $order_notes "; exit;



        //
        if ( !(is_array($arr_order) && count($arr_order) > 0) ){
            $results['error'] = "se requiren los productos de la orden";
            return $response->withJson($results, 200);
        }

        //
        if ( !is_numeric($store_id) ){
            $results['error'] = "se require la tienda de la orden";
            return $response->withJson($results, 200);
        }




        /*
         * Create Store Plan
         * */

        //
        $sale_results = Orders::RequestOrder(
            $customer_id,
            $store_id,
            $order_notes,
            $arr_order
        );
        //dd($sale_results); exit;
        if ( isset($sale_results['error']) && $sale_results['error'] ){
            $results['error'] = $sale_results['error'];
            return $response->withJson($results, 200);
        }
        //
        $new_order_id = (isset($sale_results['id']) && $sale_results['id']) ? $sale_results['id'] : null;







        /*
         * Get Order Info
         * */
        $order_info = Query::Single("Select * from ViewPartsRequests where id = ?", [$new_order_id]);
        //dd($order_info); exit;
        $store_name = $order_info['store_name'];
        $order_email = $order_info['email'];
        $order_phone_number = $order_info['phone_number'];

        //
        $str_order_details = Orders::getMailProductsInfo($new_order_id);
        //dd($str_order_details); exit;
        //echo $str_order_details; exit;

        //
        $template_info = [];
        $template_info['store_name'] = $store_name;
        $template_info['customer_name'] = $order_info['customer_name'];
        //
        $template_info['order_details'] = $str_order_details;
        $template_info['email'] = $order_email;
        $template_info['phone_number'] = $order_phone_number;
        $template_info['order_id'] = $new_order_id;
        //
        $template_info['grand_total'] = $order_info['grand_total'];
        $template_info['notes'] = $order_info['notes'];
        //dd($template_info); exit;



        /**
         *
         *  SOLO LOS PEDIDOS SE HACEN DE PEDIDERO X ESO PROPORCIONAMOS EL "APP_ID_PEDIDERO"
         *
         */
        //
        $notify_results = null;

        /*
        //
        $notify_results['sms'] = Helper::SendSMS(ACCT_ID_PEDIDERO, MAQUETA_ID_NEW_SALE, $order_phone_number, function($maqueta_sms_msg) use($template_info){
            return Orders::ParseCustomerMessages($template_info, $maqueta_sms_msg);
        });
        //
        $notify_results['email'] = Helper::SendEmail(ACCT_ID_PEDIDERO, MAQUETA_ID_NEW_SALE, $store_name, $order_email, true, function($maqueta_email_msg) use($template_info){
            return Orders::ParseCustomerMessages($template_info, $maqueta_email_msg);
        });
        */



        //
        return $response->withJson(array(
            "notify_results" => $notify_results,
            "id" => $new_order_id
        ), 200);
    }






}
