<?php
namespace Controllers\Pos;

//
use App\App;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;
use Helpers\ValidatorHelper;
use Helpers\PHPMicroParser;
use Helpers\SendMail;
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;


//
class PosHelper
{





    /**
     * 
     * 
     * Para evitar llenar el almacenaje de la carpeta con muchos tickets
     * se tomo la Decicion de crear "on the fly", utilizar (visualizar, enviar, etc) 
     * y luego Eliminar el archivo
     */
    public static function sendTicketEmail($account_id, $app_id, $sale_id, $company_name, $customer_name, $customer_email, $ticketForSend) {
        
        //
        $temp_path = PATH_STORAGE.DS.'temp';
        
        //
        $temp_filename = 'ticket_' . $sale_id . '_' . rand(1000, 9999) . '.pdf';
        $temp_path = $temp_path . $temp_filename;
        //echo $temp_path; exit;


        //
        try {
            

            //
            $pdf_options = [
                'page-width' => '80mm',        // Un poco más ancho que ticket estándar (80mm)
                'page-height' => '400mm',       // Altura automática según contenido
                'margin-top' => 3,
                'margin-right' => 3,
                'margin-bottom' => 3,
                'margin-left' => 3,
                'encoding' => 'UTF-8',          // Para caracteres especiales
            ];


            //
            $pdf = Helper::wkhtmlToPdf($pdf_options);
            $pdf->addPage($ticketForSend);

        
            //
            if (!$pdf->saveAs($temp_path)) {
                $error = $pdf->getError();
                echo $error; exit;
            }



            $filename = self::getTicketFileName($sale_id, $customer_name);

            /*
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($temp_path));
            readfile($temp_path);
            exit;
            */



            //
            $config_mail = SendMail::getMailConfig($account_id, $app_id);
            //dd($config_mail);

            $tmpl_res = MaquetasMensajes::GetMaquetaInfo($account_id, MAQUETA_ID_ENVIO_TICKET, false, true);
            //dd($tmpl_res);
            if ( isset($tmpl_res['error']) && $tmpl_res['error'] ){
                return $response->withJson($tmpl_res, 200);
            }
            //
            if ( !(isset($tmpl_res['id']) && $tmpl_res['id'] && $tmpl_res['email_msg'] && $tmpl_res['email_active']) ){
                $results['error'] = "Maqueta de correo no encontrada o inactiva"; 
                return $response->withJson($results, 200);
            }

            //
            $template_info = [
                "sale_id" => $sale_id,
                "customer_name" => $customer_name,
                "customer_email" => $customer_email,
            ];

            //
            $parsed_subject = self::parseExpedinteClinicoEmailContent($template_info, $tmpl_res['email_subject']);
            $parsed_msg = self::parseExpedinteClinicoEmailContent($template_info, $tmpl_res['email_msg']);
            //echo "$parsed_subject <br /> $parsed_msg "; exit;
            
            
            //
            $recipients = array();
            array_push($recipients, array(
                "name" => $customer_name,
                "email" => $customer_email
            ));


            //
            $attachments = [];
            array_push($attachments, array(
                "name" => $filename,
                "filepath" => $temp_path
            ));

            
            //
            $config_mail['append_to_name'] = $company_name;
            //
            $send_res = SendMail::Send($config_mail, $recipients, $attachments, $parsed_subject, $parsed_msg, $parsed_msg);
            //dd($send_res);
        


            
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            echo $message; exit;
        } finally {
            // Eliminar archivo temporal
            if (file_exists($temp_path)) {
                unlink($temp_path);
            }
        }
        
    }



    //
    public static function getTicketFileName($sale_id, $customer_name) {
        $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $customer_name);
        $safe_name = preg_replace('/\s+/', '_', $safe_name); // Reemplaza uno o más espacios con un solo underscore
        return "Ticket_Venta_#{$sale_id}_{$safe_name}_" . date('Y-m-d_H-i') . ".pdf";        
    }



    
    //
    public static function parseExpedinteClinicoEmailContent($data, $template_content){
        //dd($template_content);
        //
        $php_microparser = new PHPMicroParser();
        //
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        
        //
        if (isset($data['link'])){
            $php_microparser->setVariable("link", $data['link']);
        }
        if (isset($data['secret_code'])){
            $php_microparser->setVariable("secret_code", $data['secret_code']);
        }
        
        //
        return $php_microparser->parseVariables($template_content);
    }




 
    
}