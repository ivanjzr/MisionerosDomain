<?php
namespace Controllers\MailConfig;

//
use App\Config\ConfigMail\ConfigMail;
use Controllers\BaseController;
use App\App;
use Helpers\Helper;
use Helpers\SendMail;
use Helpers\Query;

//
class MailConfigController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");


        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;


        //
        return $this->container->php_view->render($response, 'admin/mail_config/index.phtml', [
            "App" => new App(null, $ses_data)
        ]);
    }














    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];


        //
        $results = Query::Single("Select * from mail_config where account_id = ? And app_id = ?", [$account_id, $app_id]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





















    //
    public function UpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;


        

        //
        $results = array();


        //
        $host = Helper::safeVar($request->getParsedBody(), 'host');
        $sender_name = Helper::safeVar($request->getParsedBody(), 'sender_name');
        $email = Helper::safeVar($request->getParsedBody(), 'email');
        $username = Helper::safeVar($request->getParsedBody(), 'username');
        $password = Helper::safeVar($request->getParsedBody(), 'password');
        $security_type = Helper::safeVar($request->getParsedBody(), 'security_type');
        $port = Helper::safeVar($request->getParsedBody(), 'port');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$host ){
            $results['error'] = "proporciona el host";
            return $response->withJson($results, 200);
        }
        if ( !$sender_name ){
            $results['error'] = "proporciona el nombre del remitente";
            return $response->withJson($results, 200);
        }
        if ( !$email ){
            $results['error'] = "proporciona el email del remitente";
            return $response->withJson($results, 200);
        }
        if ( !$username ){
            $results['error'] = "proporciona el usuario SMTP";
            return $response->withJson($results, 200);
        }
        if ( !$password ){
            $results['error'] = "proporciona la contraseña SMTP";
            return $response->withJson($results, 200);
        }
        if ( !$security_type ){
            $results['error'] = "proporciona el tipo de seguridad";
            return $response->withJson($results, 200);
        }
        if ( !$port ){
            $results['error'] = "proporciona el puerto SMTP";
            return $response->withJson($results, 200);
        }



        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigEmail(?,?,?,?,?,?,?,?,?,?,?)}";
            },
            "params" => function() use($account_id, $app_id, &$param_record_id, $host, $sender_name, $email, $username, $password, $security_type, $port, $active){
                return [
                    //
                    array($host, SQLSRV_PARAM_IN),
                    array($sender_name, SQLSRV_PARAM_IN),
                    array($email, SQLSRV_PARAM_IN),
                    array($username, SQLSRV_PARAM_IN),
                    array($password, SQLSRV_PARAM_IN),
                    array($security_type, SQLSRV_PARAM_IN),
                    array($port, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }
        //
        $update_results['id'] = $param_record_id;



        //
        return $response->withJson($update_results, 200);
    }










    //
    public function PostSendEmailTest($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //
        $app_name = $ses_data['app_name'];
        $sucursal = $ses_data['sucursal'];





        //
        $results = array();


        //
        $to_email = trim(Helper::safeVar($request->getParsedBody(), 'to_email'));
        $subject = trim(Helper::safeVar($request->getParsedBody(), 'subject'));
        $message = Helper::safeVar($request->getParsedBody(), 'message');
        //echo " $to_email $subject $message"; exit;


        //
        if ( !$to_email ){
            $results['error'] = "proporciona el email de destino";
            return $response->withJson($results, 200);
        }
        if ( !$subject ){
            $results['error'] = "proporciona el asunto del email";
            return $response->withJson($results, 200);
        }
        if ( !$message ){
            $results['error'] = "proporciona el mensaje";
            return $response->withJson($results, 200);
        }


        /*
        $options = [];
        $options['smtp_host'] = "smtp.gmail.com";
        $options['smtp_port'] = "587";
        $options['smtp_user'] = "test@gmail.com";
        $options['smtp_pass'] = "password123";
        */
        /*
        $customer_info = [];
        $customer_info['name'] = "Ivan";
        $customer_info['activation_code'] = "1234";
        $customer_info['email'] = "test@example.com";
        */

        //
        // Obtener configuración de correo para la cuenta y app
        $mail_config = SendMail::getMailConfig($account_id, $app_id);
        //dd($mail_config);

        //$mail_config['debug'] = 1;

        // ================================================
        // DATOS DE PRUEBA PARA ENVÍO DE EMAIL
        // ================================================

        // Configurar destinatarios de prueba
        $recipients = [
            [
                'name' => 'Usuario de Prueba',
                'email' => $to_email  // Este viene del formulario del modal
            ]
        ];

        // Configurar archivos adjuntos (vacío para prueba)
        $attachments = [];

        // Asunto del email (viene del formulario del modal)
        $subject = $subject;  // Este viene del formulario

        // Contenido HTML del email
        $message_html = "
        <html>
        <head>
            <title>DentaBLIX - {$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fa; }
                .footer { padding: 15px; text-align: center; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🚀 {$app_name} - Email de Prueba</h2>
                </div>
                <div class='content'>
                    <h3>¡Hola!</h3>
                    <p>Este es un <strong>email de prueba</strong> enviado desde el sistema de configuración de correo.</p>
                    <p><strong>Mensaje:</strong></p>
                    <div style='background: white; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                    <p>Si recibiste este email, significa que la configuración SMTP está funcionando correctamente.</p>
                    <p style='margin-top: 30px;'>
                        <a href='#' class='btn'>✅ Configuración Exitosa</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>📧 Email enviado el " . date('d/m/Y H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>";

        // Contenido en texto plano (alternativo)
        $message_no_html = "
        === {$app_name} EMAIL DE PRUEBA ===

        ¡Hola!

        Este es un email de prueba enviado desde el sistema de configuración de correo.

        MENSAJE:
        " . $message . "

        Si recibiste este email, significa que la configuración SMTP está funcionando correctamente.

        ---
        Email enviado el " . date('d/m/Y H:i:s') . "
        ";

        // ================================================
        // ENVIAR EMAIL DE PRUEBA
        // ================================================

        //
        $mail_config['append_to_name'] = $sucursal;
        //
        $results = SendMail::Send($mail_config, $recipients, $attachments, $subject, $message_html, $message_no_html);
        //dd($results);


        //
        return $response->withJson($results, 200);
    }










}