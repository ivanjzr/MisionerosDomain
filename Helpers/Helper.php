<?php
namespace Helpers;
use mikehaertl\wkhtmlto\Pdf;

//

use App\Config\ConfigTwilio\ConfigTwilio;
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;
use Twilio\Rest\Client;


class Helper {

    //
    public static $valid_img_types = array("jpeg", "jpg", "png");

    //
    public static function str_contains($str_msg, $sarch_text){
        if (strpos($str_msg, $sarch_text) !== false) {
            return true;
        }
        return false;
    }


    //
    public static function getFirstTextOnly($the_str){
        if ($the_str){
            $nombreArray = explode(" ", trim($the_str));
            if ( $nombreArray && isset($nombreArray[0]) ){
                return $nombreArray[0];
            }
        }
        return trim($the_str);
    }



    public static function valid_lat($latitude){
        return preg_match("/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/", $latitude) ? true : false;
    }

    public static function valid_lng($longitude) {
        return preg_match("/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/", $longitude) ? true : false;
    }


    public static function converToValidUrl($the_string){
        //
        $str_wo_symbols = trim(preg_replace("/[^A-Za-z0-9 ]/", '', strtolower($the_string)));
        $str_to_dash = str_replace(" ", "-", $str_wo_symbols);
        $clean_str = str_replace("--", "-", $str_to_dash);
        return urlencode($clean_str);
    }

    //
    public static function getOrderInfo($order, $columns){
        //
        $arr = [];
        //
        $arr['field'] = "id";
        $arr['direction'] = "desc";
        if (
            isset($order[0]) && isset($order[0]['column']) && is_numeric($order[0]['column']) && isset($order[0]['dir']) &&
            $columns && isset($columns[ $order[0]['column'] ]) && isset($columns[ $order[0]['column']]['name'])
        ){
            $arr['field'] = $columns[$order[0]['column']]["name"];
            $arr['direction'] = $order[0]['dir'];
        }
        //var_dump($arr);
        return $arr;
    }


    //
    public static function appendLeadingZero($number){
        return (is_numeric($number) && (strlen($number) === 1)) ? "0".$number : $number ;
    }


    public static function cratePlaceholderImage($width, $height, $savePath, $r, $g, $b){
        //
        $imagen = imagecreatetruecolor($width, $height);
        $colorAzulPale = imagecolorallocate($imagen, $r, $g, $b);
        //
        imagefill($imagen, 0, 0, $colorAzulPale);
        imagepng($imagen, $savePath);
        imagedestroy($imagen);
    }


    public static function getPeriodoText($cant_meses){
        switch ($cant_meses) {
            case 1:
                return "Mensual";
            case 3:
                return "Trimestral";
            case 6:
                return "Semestral";
            case 12:
                return "Anual";
            default:
                return null;
        }
    }


    public static function calcularMontoAndDiscounts($cant_meses, $precio){

        // cantidad meses
        $cm = intval($cant_meses);

        // original price
        $op = (floatval($precio) * $cm);
        //echo $op; exit;

        // discount percent
        $dp = 0;

        //
        if ($cm === 3) {
            $dp = 15;
        } else if ($cm === 6) {
            $dp = 20;
        } else if ($cm === 12) {
            $dp = 25;
        }

        // discount amount
        $da = ($op * $dp / 100);

        // original price menos discount amount
        $fp = ($op - $da);

        //
        return array(
            'orig_price' => $op,
            'disc_perc' => $dp,
            'disc_amt' => $da,
            'final_price' => $fp
        );
    }

    //
    public static function printFull($content){
        echo "<pre>";
        print_r($content);
        echo "</pre>";
    }


    //
    public static function previewXml($str_xml){
        //
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($str_xml);
        $out = $dom->saveXML();
        echo $out; exit;
    }





    //
    public static function GetMontoDescuento($monto, $descuento){
        $monto_descuento = ( $monto * $descuento / 100 );
        return ( $monto - $monto_descuento );
    }

    public static function amtDisplay($amount, $hide_sign = false){
        if (is_numeric($amount)){
            //
            $sign = "$";
            if ($hide_sign){
                $sign = "";
            }
            //
            return $sign . number_format((float)$amount, 2, '.', ',');
        }
        return "";
    }


    public static function amountDisplayUsd($amount, $hide_sign = false){
        if (is_numeric($amount)){
            //
            $sign = "$";
            if ($hide_sign){
                $sign = "";
            }
            //
            return $sign . number_format((float)$amount, 2, '.', ',') . " usd";
        }
        return "";
    }






    //
    public static function GetDescuentoMonto($monto, $descuento){
        $monto_descuento = ( $monto * $descuento / 100 );
        return ( $monto - $monto_descuento );
    }





    public static function safeVar($arr, $var_name){
        if (isset($arr[$var_name])){
            return $arr[$var_name];
        }
        return null;
    }



    //
    public static function str_contains_alphabet($str_msg){
        if(preg_match("/[a-z]/i", $str_msg)){
            return true;
        }
        return false;
    }




    //
    public static function str_contains_number($str_msg){
        if(1 === preg_match('~[0-9]~', $str_msg)){
            return true;
        }
        return false;
    }





    /*
     * Obtiene Ids separados por comma de un array de arrays
     * */
    public static function RetrieveArrKeyIds($arr, $keyname = "id"){
        return implode(",", array_column($arr, $keyname));
    }



    /*
     * Obtiene Ids separados por comma de un array
     * */
    public static function RetrieveArrIds($arr){
        return implode(",", $arr);
    }




    public static function SetPrecioMXN($usd_monto, $tipo_cambio_usd, $factor_descuento){
        //
        $mxn_tdc_monto = ( $usd_monto * $tipo_cambio_usd );
        $descuento_monto = ( $mxn_tdc_monto * $factor_descuento / 100 );
        return ($mxn_tdc_monto - $descuento_monto);
    }




    public static function SetPrecioFinalSegunMoneda(&$concepto, $tipo_moneda){
        //echo $tipo_moneda; var_dump($concepto); exit;

        /*
        if ($tipo_moneda=="usd"){
            //
            $concepto['precio'] = $concepto['precio_usd'];
            $concepto['precio_final'] = $concepto['precio_usd_final'];
        }
        //
        else if ($tipo_moneda=="mxn"){
            //
            $concepto['precio'] = $concepto['precio_mxn'];
            $concepto['precio_final'] = $concepto['precio_mxn_final'];
        }
        */



        // set for display
        $concepto['precio_display'] = Helper::amtDisplay($concepto['precio']);
        $concepto['precio_final_display'] = Helper::amtDisplay($concepto['precio_final']);

    }



    //
    

    //
    public static function siteURL() {
        $domainName = $_SERVER['HTTP_HOST'];
        return getProtocol().$domainName;
    }


    //
    public static function is_valid_date($str_date, $format = "Y-m-d H:i:s", $ret_formmated = "Y-m-d H:i:s"){
        //
        $date_obj = \DateTime::createFromFormat($format, $str_date);
        //
        if ($date_obj){
            // return formatted
            if ($ret_formmated){
                return $date_obj->format($ret_formmated);
            }
            // return obj
            else {
                return $date_obj;
            }
        }
        return false;
    }



    //
    public static function getAccountByAppId($app_id){
        $res = Query::Single("select * from accounts_apps t where t.id = ?", [$app_id]);
        //Helper::printFull($res); exit;
        if ($res && isset($res['account_id'])){
            return $res['account_id'];
        }
        return null;
    }


    //
    public static function getAppByAccountId($account_id){
        $res = Query::Single("select top 1 t.id from accounts_apps t where t.account_id = ?", [$account_id]);
        //Helper::printFull($res); exit;
        if ($res && isset($res['id'])){
            return $res['id'];
        }
        return null;
    }



    //
    public static function SendEmail($account_id, $app_id, $maqueta_id, $customer_name, $customer_email, $send_to_copies, $callback){

        //
        $maqueta_info = MaquetasMensajes::GetMaquetaInfo($account_id, $maqueta_id, false, true);
        //dd($maqueta_info); exit;
        //
        if ( isset($maqueta_info['error']) && $maqueta_info['error'] ){
            $results['send_sms_err'] = $maqueta_info['error'];
            return $results;
        }

        //
        if ( isset($maqueta_info['id']) && $maqueta_info['id'] && $maqueta_info['email_msg'] && $maqueta_info['email_active']){

            //
            $parsed_msg = $callback($maqueta_info['email_msg']);
            $parsed_subject = $callback($maqueta_info['email_subject']);
            //echo $parsed_msg; exit;
            //
            $arr_results = [];



            //
            $config_mail = SendMail::getMailConfig($account_id, $app_id);
            //dd($config_mail); exit;



            //
            $attachments = [];
            //
            $recipients = array();
            //
            array_push($recipients, array(
                "name" => $customer_name,
                "email" => $customer_email
            ));
            $arr_results['main_msg'] = SendMail::Send($config_mail, $recipients, $attachments, $parsed_subject, $parsed_msg, $parsed_msg);
            //Helper::printFull($arr_results); exit;


            //
            if ($send_to_copies){
                //
                if ( isset($maqueta_info['copias_emails']) && count($maqueta_info['copias_emails']) > 0 ){
                    //
                    $arr_results['copies'] = [];
                    $recipients_copies = array();
                    //
                    foreach($maqueta_info['copias_emails'] as $item_email){
                        //Helper::printFull($item_email); exit;
                        //
                        array_push($recipients_copies, array(
                            "name" => $item_email['email'],
                            "email" => $item_email['email']
                        ));
                    }
                    //
                    array_push($arr_results['copies'], SendMail::Send($config_mail, $recipients_copies, $attachments, "COPY: ". $parsed_subject, $parsed_msg, $parsed_msg));
                }
            }

            //
            return $arr_results;
        }
        //
        else {
            //
            $results['send_email_err'] = "Template not found or inactive";
            return $results;
        }

    }





    public static function SendSMS($account_id, $app_id, $maqueta_id, $phone_number, $callback){
        //echo "$account_id, $app_id, $maqueta_id"; exit;
        //
        $results = array();

        //
        // #1 - Get Twilio Config
        //
        $twilio_config = ConfigTwilio::GetRecord($account_id);
        //Helper::printFull($twilio_config); exit;
        if ( isset($twilio_config['error']) && $twilio_config['error'] ){
            $results['send_sms_err'] = $twilio_config['error'];
            return $results;
        }
        //
        if ( isset($twilio_config['id']) && $twilio_config['id'] && $twilio_config['active'] ){

            //
            if ($maqueta_id){
                return self::SendMessageWithTemplate($account_id, $twilio_config, $phone_number, $maqueta_id, $callback);

            } else {
                $parsed_msg = $callback(null);
                //echo $parsed_msg; exit;
                return self::SendMessageRaw($twilio_config, $phone_number, $parsed_msg);
            }
        }
        //
        else {
            //
            $results['send_sms_err'] = "Twilio config not found";
            return $results;
        }
    }





    //
    public static function SendMessageWithTemplate($account_id, $twilio_config, $phone_number, $maqueta_id, $callback){
        //
        $maqueta_info = MaquetasMensajes::GetMaquetaInfo($account_id, $maqueta_id, true);
        //Helper::printFull($maqueta_info); exit;
        //
        if ( isset($maqueta_info['error']) && $maqueta_info['error'] ){
            $results['send_sms_err'] = $maqueta_info['error'];
            return $results;
        }
        //
        if ( isset($maqueta_info['id']) && $maqueta_info['id'] && $maqueta_info['sms_msg'] && $maqueta_info['sms_active']){

            //
            $parsed_msg = $callback($maqueta_info['sms_msg']);
            //echo $parsed_msg; exit;

            //
            $arr_msgs = [];
            $arr_msgs['copies'] = [];
            //
            if ( isset($maqueta_info['copias_phones']) && count($maqueta_info['copias_phones']) > 0 ){
                foreach($maqueta_info['copias_phones'] as $item_phone){
                    //Helper::printFull($item_phone); exit;
                    array_push($arr_msgs['copies'], self::SendMessageRaw($twilio_config, $item_phone['phone_cc']."".$item_phone['phone_number'], "COPY: ".$parsed_msg));
                }
            }
            //
            $arr_msgs['main_msg'] = self::SendMessageRaw($twilio_config, $phone_number, $parsed_msg);
            return $arr_msgs;
        }
        //
        else {
            //
            $results['send_sms_err'] = "Template not found or inactive";
            return $results;
        }
    }





    //
    public static function SendMessageRaw($twilio_config, $phone_number, $msg){
        //
        $send_results = self::SendTwilio($twilio_config, $phone_number, $msg);
        //var_dump($the_record); exit;
        if ( $send_results && isset($send_results['error'])){
            //
            $str_err_msg = "";
            if (strpos($send_results['error'], 'is not a mobile number') !== false) {
                $str_err_msg = "Telefono " . $phone_number . " no es un numero valido o no admite mensajes de texto";
            } else {
                $str_err_msg = $send_results['error'];
            }
            //
            $results['send_sms_err'] = $str_err_msg;
            return $results;
        }
        //
        return $send_results;
    }



    //
    public static function SendTwilio($twilio_config, $customer_phone, $sms_body){
        //
        $results = array();
        //
        try{



            //var_dump($twilio_config); exit;
            $phone_number = null;
            $account_sid = null;
            $auth_token = null;
            //
            if ($twilio_config['is_prod']){
                //
                $account_sid = $twilio_config['account_sid'];
                $auth_token = $twilio_config['auth_token'];
                $phone_number = $twilio_config['phone_number'];
            } else {
                //
                $account_sid = $twilio_config['account_sid_test'];
                $auth_token = $twilio_config['auth_token_test'];
                $phone_number = $twilio_config['phone_number_test'];
            }


            //
            $client = new Client($account_sid, $auth_token);
            //var_dump($client); exit;

            /*
             * GET EFICIENTO PHONE NUMBER
             * (915) 233-3664
             * 915) 233-3664
             * */
            // obtener telefono mediante el sid del telefono (obtenido en la lista o al ser creado)
            //$selected_phone_number = $client->incomingPhoneNumbers("PNd517c3f52534ee39987a2581007c121d")->fetch();
            //var_dump($selected_phone_number->phoneNumber); exit;

            //
            $res = $client->messages->create(
                $customer_phone,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => $phone_number,
                    // the body of the text message you'd like to send
                    'body' => $sms_body
                )
            );
            $results['mode'] = ($twilio_config['is_prod']) ? "prod" : "dev";
            $results['id'] = $res->sid;

        }
        catch (\Exception $exception){
            $results["error"] = "Error: {$exception->getMessage()}, code: {$exception->getCode()}, file: {$exception->getFile()}, line: {$exception->getLine()}";
        }
        //
        return $results;
    }





    public static function validateGetExpirationMonthYear($expiration_month_year){
        //
        $exploded = explode("/", $expiration_month_year);
        //var_dump($exploded); exit;
        if (isset($exploded[0]) && isset($exploded[1])){
            //
            $str_date = "20".$exploded[1] . "/" . $exploded[0] . "/01";
            $expiration_date_obj = \DateTime::createFromFormat("!Y/m/d", $str_date);
            //var_dump($expiration_date_obj);
            if ($expiration_date_obj){
                return $expiration_date_obj;
            }
        }
        return false;
    }



    public static function getExtByMime($mime) {
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/vnd.wap.wbmp' => 'wbmp',
            'image/jp2' => 'jp2',
            'image/x-xbitmap' => 'xbm',
            'image/x-portable-anymap' => 'pnm',
            'image/x-portable-bitmap' => 'pbm',
            'image/x-portable-graymap' => 'pgm',
            'image/x-portable-pixmap' => 'ppm',
            'image/x-xpixmap' => 'xpm',
            'image/x-xwindowdump' => 'xwd',
            'image/vnd.djvu' => 'djvu',
            // Agrega más tipos MIME y extensiones según sea necesario
        ];

        if (isset($mimeToExtension[$mime])) {
            return strtolower($mimeToExtension[$mime]);
        } else {
            return null;
        }
    }






    public static function corregirRotacionImagen($the_img, $save_path) {

        //
        $exif = exif_read_data($the_img->file);
        //Helper::printFull($exif); exit;

        //
        $file_extension = self::getExtByMime($exif['MimeType']);
        $corrected_img = false;

        //
        if ($exif && isset($exif['Orientation'])) {
            //
            $orientation = $exif['Orientation'];
            //
            if ($orientation != 1) {
                //
                $img = imagecreatefromjpeg($the_img->file);
                $deg = 0;
                switch ($orientation) {
                    case 3:
                        $deg = 180;
                        break;
                    case 6:
                        $deg = 270;
                        break;
                    case 8:
                        $deg = 90;
                        break;
                }
                //
                if ($deg) {
                    $img = imagerotate($img, $deg, 0);
                }



                //
                $path_and_name = $save_path.DS.uniqid("corrected-");

                //
                if ($file_extension === 'gif') {
                    $new_img_path = $path_and_name.".gif";
                    imagegif($img, $new_img_path);
                } else if ($file_extension === 'jpg') {
                    $new_img_path = $path_and_name.".jpg";
                    imagejpeg($img, $new_img_path, 90);
                } else if ($file_extension === 'png') {
                    $new_img_path = $path_and_name.".png";
                    imagepng($img, $new_img_path, 90);
                } else {
                    $new_img_path = $path_and_name.".".$file_extension;
                    copy($the_img->file, $new_img_path);
                }

                //
                $corrected_img = true;
                return $new_img_path;
            }
        }
        //
        if (!$corrected_img){
            //
            $path_and_name = $save_path.DS.uniqid("not-corrected-");
            //
            $new_img_path = $path_and_name.".".$file_extension;
            copy($the_img->file, $new_img_path);
            //
            return $new_img_path;
        }
    }




    //
    public static function resizeImage($file_path_and_name, $file_size, $file_extension, $save_path, $maxFileSize = 1572864, $maxWidth = 800){


        // 1 mb = 1048576 (Esta en esta en 1572864 1mb y medio)

        //
        if ($file_size > $maxFileSize) {


            // Crea una nueva imagen redimensionada
            list($width, $height) = getimagesize($file_path_and_name);
            //echo " $width $height "; exit;


            $newWidth = $maxWidth;
            $newHeight = ($newWidth / $width) * $height;
            $imageResized = imagecreatetruecolor($newWidth, $newHeight);
            $imageSource = imagecreatefromstring(file_get_contents($file_path_and_name));

            // Redimensiona la imagen
            imagecopyresized($imageResized, $imageSource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);


            //
            $path_and_name = $save_path.DS.uniqid("resized-");
            $new_img_path = "";

            // Guarda la imagen redimensionada en el archivo temporal
            if ($file_extension === 'gif') {
                $new_img_path = $path_and_name.".gif";
                imagegif($imageResized, $new_img_path);
            } else if ($file_extension === 'jpg') {
                $new_img_path = $path_and_name.".jpg";
                imagejpeg($imageResized, $new_img_path, 90);
            } else if ($file_extension === 'png') {
                $new_img_path = $path_and_name.".png";
                imagepng($imageResized, $new_img_path, 90);
            }

            // Libera la memoria de las imágenes
            imagedestroy($imageResized);
            imagedestroy($imageSource);


            //
            unlink($file_path_and_name);

            //
            return $new_img_path;

        }
        //
        return false;
    }






    public static function wkhtmlToPdf($options = []){
    
        // Configuración por defecto
        $default_options = [
            'no-outline',         // Make Chrome not complain
            
            'margin-top'    => 5,
            'margin-right'  => 5,
            'margin-bottom' => 5,
            'margin-left'   => 5,
            
            'commandOptions' => [
                'useExec' => true
            ],
            
            'viewport-size' => "1280x1024",
            'header-html' => "header data",
            'footer-font-size' => 8,
            'footer-left' => "[webpage]",
            'footer-center' => "[date] - [time]",
            'footer-right' => "[page]/[toPage]",
            
            /* Portrait  Landscape */
            'orientation'   => "Portrait",
            
            /*'disable-smart-shrinking',*/
            /*'footer-html' => $footer_html_data,*/
            /*,'user-style-sheet' => $pdf_css_path,*/
        ];
        
        // Merge de opciones: las pasadas por parámetro sobrescriben las default
        $pdf_options = array_merge($default_options, $options);
        
        $pdf = new Pdf($pdf_options);
        
        $pdf->setOptions(['ignoreWarnings'=>true]);
        $pdf->binary = 'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe';
        
        return $pdf;
    }



    //
     public static function requireAdmin($ses_data, $response, $redirect_url = '/admin/home', $custom_message = null) {
        // Verificar si el usuario es administrador
        if (!isset($ses_data['is_admin']) || !$ses_data['is_admin']) {
            
            // Mensaje por defecto o personalizado
            $message = $custom_message ?: "Only admins can access this section";
            
            // Crear mensaje HTML con enlace de retorno
            $html_message = "{$message}, <a href='{$redirect_url}'>Return to Home</a>";
            
            // Retornar respuesta de error
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'text/html')
                ->write($html_message);
        }
        
        // Si es admin, retornar null (continuar con el flujo normal)
        return null;
    }






}

