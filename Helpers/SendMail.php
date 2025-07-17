<?php
namespace Helpers;


use Helpers\Query;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



//
class SendMail {


    // 
    public static function getMailConfig($account_id, $app_id){


        /***
         * //
        $correo_config = [];

        //$correo_config['debug'] = 1;
        $correo_config['host'] = "email-smtp.us-west-2.amazonaws.com";


        if ( (int)$account_id === 5 ){
            
            $correo_config['sender_name'] = "Plabuz";
            $correo_config['email'] = "info@plabuz.com";
            
        } else if ( (int)$account_id === 12 ){

            $correo_config['sender_name'] = "MissionExpress";
            $correo_config['email'] = "info@missionexpress.us";
            

        } else if ( (int)$account_id === 14 ){

            $correo_config['email'] = "info@tickets4buses.com";
            $correo_config['sender_name'] = "Tickets4Buses";

        }

        //
        $correo_config['username'] = "AKIAWTDTEW33YQQQEXND";
        $correo_config['password'] = "BG56/CbkYI/qt/2fzB8KOBLOvsh6oH4UQj5P2Vf5oGUb";
        $correo_config['security_type'] = "tls";

        $correo_config['port'] = "2587";

        */
        return Query::Single("Select * from mail_config where account_id = ? And app_id = ?", [$account_id, $app_id]);
    }


    
    //
    public static function Send($mail_config, $recipients, $attachments, $subject, $message_html, $message_no_html){

        // Crear nueva instancia de PHPMailer con manejo de excepciones habilitado
        $mail = new PHPMailer(true);
        
        try {
            // ================================================
            // CONFIGURACIÓN DEL SERVIDOR SMTP
            // ================================================
            
            // Habilitar debug si está configurado (opcional)
            if (isset($mail_config["debug"]) && $mail_config["debug"] > 0) {
                $mail->SMTPDebug = $mail_config["debug"];
            }

            // Configurar para usar SMTP
            $mail->isSMTP();
            
            // Configurar servidor SMTP - usar 'host' si no existe usar valores por defecto
            $mail->Host = isset($mail_config["host"]) ? $mail_config["host"] : 'localhost';
            
            // Habilitar autenticación SMTP
            $mail->SMTPAuth = true;
            
            // Credenciales SMTP - usar los campos de nuestra tabla mail_config
            $mail->Username = $mail_config["username"];    // username de la tabla
            $mail->Password = $mail_config["password"];    // password de la tabla
            
            // Configurar tipo de seguridad (TLS, SSL, o ninguno)
            if ($mail_config["security_type"] !== 'none') {
                $mail->SMTPSecure = $mail_config["security_type"];  // security_type de la tabla
            }
            
            // Puerto SMTP
            $mail->Port = (int)$mail_config["port"];       // port de la tabla

            // ================================================
            // CONFIGURACIÓN DEL REMITENTE
            // ================================================
            
            // Establecer remitente usando los campos de nuestra tabla
            $append_to_name = "";
            if (isset($mail_config["append_to_name"]) && $mail_config["append_to_name"]){
                $append_to_name = " - " . $mail_config["append_to_name"];
            }
            //
            $mail->setFrom(
                $mail_config["email"],
                $mail_config["sender_name"] . $append_to_name
            );

            // ================================================
            // CONFIGURACIÓN DE DESTINATARIOS
            // ================================================
            
            // Agregar destinatarios principales
            foreach($recipients as $recipient) {
                // Verificar si se debe agregar como CC (copia)
                if (isset($mail_config['add_cc']) && $mail_config['add_cc']) {
                    // Agregar como copia (CC)
                    if (isset($recipient['name']) && !empty($recipient['name'])) {
                        $mail->AddCC($recipient['email'], $recipient['name']);
                    } else {
                        $mail->AddCC($recipient['email']);
                    }
                } else {
                    // Agregar como destinatario principal
                    if (isset($recipient['name']) && !empty($recipient['name'])) {
                        $mail->addAddress($recipient['email'], $recipient['name']);
                    } else {
                        $mail->addAddress($recipient['email']);
                    }
                }
            }

            // ================================================
            // CONFIGURACIÓN DE RESPUESTAS Y COPIAS (OPCIONALES)
            // ================================================
            
            // Agregar dirección de respuesta (Reply-To) si está configurada
            if (isset($mail_config["email_repply_to"]) && !empty($mail_config["email_repply_to"])) {
                $reply_name = isset($mail_config["name_repply_to"]) ? $mail_config["name_repply_to"] : '';
                $mail->addReplyTo($mail_config["email_repply_to"], $reply_name);
            }

            // Agregar copia (CC) adicional si está configurada
            if (isset($mail_config["email_cc"]) && !empty($mail_config["email_cc"])) {
                $cc_name = isset($mail_config["name_cc"]) ? $mail_config["name_cc"] : '';
                $mail->addCC($mail_config["email_cc"], $cc_name);
            }

            // Agregar copia oculta (BCC) si está configurada
            if (isset($mail_config["email_bcc"]) && !empty($mail_config["email_bcc"])) {
                $bcc_name = isset($mail_config["name_bcc"]) ? $mail_config["name_bcc"] : '';
                $mail->addBCC($mail_config["email_bcc"], $bcc_name);
            }

            // ================================================
            // AGREGAR ARCHIVOS ADJUNTOS
            // ================================================
            
            foreach($attachments as $attachment) {
                // Verificar si el archivo adjunto tiene nombre personalizado
                if (isset($attachment['name']) && !empty($attachment['name'])) {
                    $mail->addAttachment($attachment['filepath'], $attachment['name']);
                } else {
                    // Agregar archivo sin nombre personalizado
                    $mail->addAttachment($attachment['filepath']);
                }
            }

            // ================================================
            // CONFIGURACIÓN DEL CONTENIDO DEL EMAIL
            // ================================================
            
            // Configurar formato HTML
            $mail->isHTML(true);
            
            // Establecer asunto del email
            $mail->Subject = $subject;
            
            // Establecer contenido HTML del email
            $mail->Body = $message_html;
            
            // Establecer contenido alternativo en texto plano
            $mail->AltBody = $message_no_html;

            // ================================================
            // ENVIAR EMAIL
            // ================================================
            
            // Intentar enviar el email
            if (!$mail->send()) {
                // Si falla el envío, retornar error
                return array("error" => $mail->ErrorInfo);
            }

            // Si el envío es exitoso, retornar confirmación
            return array("success" => true, "message" => "Email enviado correctamente");

        } catch (Exception $e) {
            // Capturar cualquier excepción y retornar el error
            return array("error" => $e->getMessage());
        }
    }




}
