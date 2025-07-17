<?php
namespace Controllers\Invoices;

//
use App\Locations\CatPaises;
use App\Paths;

//
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;
use App\Maquetas\MaquetasDocumentos;
use Controllers\BaseController;
use App\App;
use Helpers\CodigoQR;
use Helpers\PHPMicroParser;
use App\Customers\Customers;
use App\Ventas\Ventas;
use App\Invoices\Invoices;
use Helpers\SendMail;

use Helpers\Geolocation;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;




//
class InvoicesController extends BaseController
{





   


    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/invoices/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "user_session_data" => $request->getAttribute("ses_data"),
        ]);
    }





    




    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.name like '%$search_value%' ) Or 
                        ( t.username like '%$search_value%' ) Or
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $user_id = $ses_data['id'];




        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //
        $filter_sale_type_id = (int)$request->getQueryParam("filter_sale_type_id");
        //echo $filter_sale_type_id; exit;



        //
        $table_name = "ViewInvoices";
        //echo $table_name; exit;




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
                    return "Select COUNT(*) total From {$table_name} t Where 1 = 1 {$search_clause}";
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
                                      
                                           
                                            Where 1 = 1
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    //
                ),

                //
                "parseRows" => function(&$row){
                    
                    //
                    $row['arr_sales'] = Query::Multiple("SELECT id, customer_id, company_name, customer_name, email, grand_total, comisiones, new_total, comisiones_cbx, new_total2, datetime_created  FROM sales t Where t.invoice_id = ?", [$row['id']], function(&$row2){
                        //
                        $row2['salidas_ocupacion'] = Query::Multiple("SELECT t.* FROM salidas_ocupacion t Where t.sale_id = ?", [$row2['id']]);
                    });
                    

                    $invoice_path = $row['invoice_code'];
                    //echo $invoice_path;
                    //
                    $site_url = Helper::siteURL();
                    //echo $site_url; exit;
                    //
                    $row['invoice_url'] = $site_url."/public/invoices/" . $invoice_path;

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }





    // 
    public function PostUpdateStatus($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        



        //
        $invoice_id = $args['invoice_id'];
        //echo $invoice_id; exit;
        $body = $request->getParsedBody();

        
        //
        $new_status_id = Helper::safeVar($body, 'status_id');
        //
        if ( !is_numeric($new_status_id) ){
            $results['error'] = "Provide status id"; return $response->withJson($results, 200);
        }



        //
        $param_updated_rows = 0;
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "debug" => false,
            "stmt" => function(){
                return "{call usp_UpdateInvoiceStatus(?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_INVOICE_NOT_FOUND" => "No se encontro el invoice",
                "ERR_CANNOT_UPDATE_INVOICE_WITH_SAME_STATUS" => "no se puede actualizar invoice con mismo status",
                "ERR_CANNOT_UPDATE_ALREADY_PAID" => "no se puedem actualizar invoices ya pagados",
            ],
            "params" => function() use(
                $invoice_id,
                $new_status_id,
                &$param_updated_rows
            ){
                //
                return [
                    array($invoice_id, SQLSRV_PARAM_IN),
                    array($new_status_id, SQLSRV_PARAM_IN),
                    array(&$param_updated_rows, SQLSRV_PARAM_OUT)
                ];
            },
        ]);
        //dd($sp_res); exit;
        if ( isset($sp_res['error']) && $sp_res['error'] ){
            $results['error'] = $sp_res['error'];
            return $response->withJson($results);
        }
        //
        $sp_res['id'] = $invoice_id;
        $sp_res['updated_rows'] = $param_updated_rows;

        //
        return $response->withJson($sp_res, 200);
    }



     
    






    // 
    public function PostSendInvoice($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_id = $ses_data['id'];
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //
        $invoice_id = $args['invoice_id'];
        //echo $invoice_id; exit;


         

        //
        $inv_res = Query::Single("Select id, customer_id, company_name, customer_name, email, phone_number, periodo_date, invoice_notes, sum_venta, sum_comisiones, sum_por_cobrar, invoice_code, invoice_status_id, invoice_status, status_datetime FROM ViewInvoices Where id = ?", [$invoice_id], function(&$row){
            //
            $row['arr_sales'] = Query::Multiple("SELECT id, customer_id, company_name, customer_name, email, grand_total, comisiones, new_total, comisiones_cbx, new_total2, datetime_created  FROM sales t Where t.invoice_id = ?", [$row['id']], function(&$row2){
                //
                $row2['salidas_ocupacion'] = Query::Multiple("SELECT t.* FROM salidas_ocupacion t Where t.sale_id = ?", [$row2['id']]);            
            });
        });
        //dd($inv_res); exit;
        if ( !(isset($inv_res['id']) && $inv_res['id']) ){
            $results['error'] = "invoice not found";
            return $response->withJson($results);
        }
        $company_name = $inv_res['company_name'];
        $customer_name = $inv_res['customer_name'];
        $invoice_code = $inv_res['invoice_code'];
        $invoice_status = $inv_res['invoice_status'];
        
        
        
        

        $file_customer_name = strtolower(str_replace(' ', '-', $company_name." ".$customer_name));
        //echo $file_customer_name; exit;
        

        //
        $invoices_path = PATH_PUBLIC.DS.'files'.DS.'invoices';
        $pdf_file_path = $invoices_path.DS.$invoice_id.'.pdf';
        $pdf_name = "invoice-{$file_customer_name}-{$invoice_id}.pdf";
        //echo " $pdf_file_path $pdf_name"; exit;

        //
        $inv_res['status_datetime'] = $inv_res['status_datetime']->format("d M Y");
        $inv_res['company_cobradora_name'] = "MissionExpress";
        $inv_res['periodo_info'] = " - " . $inv_res['periodo_date']->format("Y-m-d");
        $inv_res['email'] = 'ivanjzr@gmail.com';
        //dd($inv_res); exit;


          

        //
        $pdf = Helper::wkhtmlToPdf();



         //
         $site_url = Helper::siteURL();
         //echo $site_url; exit;
         $invoice_url = $site_url."/public/invoices/" . $invoice_code;
         //echo $invoice_url; exit;


         //
         $pdf->addPage($invoice_url);
         //$pdf->addPage('/path/to/page.html');
         //$pdf->addPage($page_content);
        

        

        //
        $pdf_debug = false;
        //
        if ($pdf_debug && !$pdf->send()){
            //
            $error = $pdf->getError();
            //
            $results['error'] = $error;
            return $response->withJson($results);
        }


        //echo "test"; exit;




        //
        if (!$pdf_file_path){
            $results['error'] = "no pdf file path provided";
            return $response->withJson($results);
        }
        //
        if (!$pdf->saveAs($pdf_file_path)) {
            $error = $pdf->getError();
            //
            $results['error'] = $error;
            return $response->withJson($results);
        }

        //echo "test 2"; exit;





        //
        $send_copy_to_emails = true;
        //
        $maqueta_info = MaquetasMensajes::GetMaquetaInfo($account_id, MAQUETA_ID_ENVIO_INVOICE, $send_copy_to_phones = false, $send_copy_to_emails);
        //dd($maqueta_info); exit;
        //
        if ( !(isset($maqueta_info['id']) && $maqueta_info['id']) ){
            $results['error'] = "maqueta not found";
            return $response->withJson($results);
        }
        $email_subject = $maqueta_info['email_subject'];
        $email_msg = $maqueta_info['email_msg'];
        //
        $parsed_subject = self::ParseTemplateSendInvoice($inv_res, $email_subject);
        $parsed_msg = self::ParseTemplateSendInvoice($inv_res, $email_msg);
        //echo "---- $parsed_subject ---- $parsed_msg ----"; exit;
        



        //
        $attachments = [];
        //
        array_push($attachments, array(
            "name" => $pdf_name,
            "filepath" => $pdf_file_path
        ));
        //
        $recipients = array();
        //
        array_push($recipients, array(
            "name" => $inv_res['customer_name'],
            "email" => $inv_res['email']
        ));
        $send_res = self::sendInvoice($account_id, $recipients, $attachments, $parsed_subject, $parsed_msg);
        //echo "done ok"; exit;
    
        
        

        //
        $send_res['id'] = $invoice_id;        
        //
        return $response->withJson($send_res);
    }




    //
    public static function ParseTemplateSendInvoice($data, $template_content){
        //
        $php_microparser = new PHPMicroParser();
        //
        $invoice_id = $data['id'];
        
        //
        $php_microparser->setVariable("company_cobradora_name", $data['company_cobradora_name']);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("periodo_info", $data['periodo_info']);
        $php_microparser->setVariable("invoice_status", ucfirst($data['invoice_status']));
        $php_microparser->setVariable("status_datetime", $data['status_datetime']);
        $php_microparser->setVariable("invoice_id", $invoice_id);
        $php_microparser->setVariable("id", $invoice_id);
        //
        return $php_microparser->parseVariables($template_content);
    }






    //
    public function generatePublicInvoiceUrl($request, $response, $args) {
        //
        $app_data = $request->getAttribute("app");
        //dd($app_data); exit;
        //
        $account_id = $app_data['account_id'];
        $app_id = $app_data['id'];        
        //echo " $account_id  $app_id "; exit;
        //
        $invoice_code = $args['invoice_code'];
        //echo $invoice_code; exit;

        $site_url = Helper::siteURL();


        //
        $display_pdf = $request->getQueryParam("pdf") ? true : false;
        //echo $display_pdf; exit;

        
        
        //
        $inv_info = Query::Single("Select id, customer_id, company_name, customer_name, email, phone_number, periodo_date, sum_venta, sum_comisiones, sum_por_cobrar, invoice_notes, invoice_code, invoice_status_id, invoice_status, status_datetime FROM ViewInvoices Where invoice_code = ?", [$invoice_code], function(&$row){
            //
            $row['arr_sales'] = Query::Multiple("SELECT id, customer_id, company_name, customer_name, email, grand_total, comisiones, new_total, comisiones_cbx, new_total2, datetime_created  FROM sales t Where t.invoice_id = ?", [$row['id']], function(&$row2){
                //
                $row2['salidas_ocupacion'] = Query::Multiple("SELECT t.* FROM salidas_ocupacion t Where t.sale_id = ?", [$row2['id']]);
            });
        });
        //dd($inv_info); exit;
        if ( !(isset($inv_info['id']) && $inv_info['id']) ){
            $results['error'] = "invoice not found";
            return $response->withJson($results);
        }
        
        //
        $invoice_id = $inv_info['id'];
        $customer_id = $inv_info['customer_id'];
        $company_name = $inv_info['company_name'];
        $customer_name = $inv_info['customer_name'];
        $customer_email = $inv_info['email'];
        $customer_phone_number = $inv_info['phone_number'];
        $periodo_info = $inv_info['periodo_date']->format("d/M/Y");
        $invoice_code = $inv_info['invoice_code'];
        //echo $invoice_id; exit;
        
        
        


        //
        $customer_info = null;
        //
        if ($customer_id){
            $customer_info = Query::Single("Select * FROM v_customers Where id = ?", [$customer_id]);
            //dd($customer_info); exit;
            if ( !(isset($customer_info['id']) && $customer_info['id']) ){
                $results['error'] = "customer not found";
                return $response->withJson($results);
            }
        }
        //echo "test"; exit;
        
            







        //
        $document_info = MaquetasDocumentos::GetMaquetaInfo($account_id, DOCUMENTO_ID_INVOICE_POR_COBRAR);
        //dd($document_info); exit;
        if ( !(isset($document_info['id']) && $document_info['id']) ){
            $results['error'] = "maqueta not found";
            return $response->withJson($results);
        }
        //
        $maqueta_name = $document_info['maqueta_name'];
        $maqueta_content = $document_info['maqueta_content'];
        //echo " $maqueta_name $maqueta_content "; exit;





        //
        $invoice_code_id = $invoice_code . "-" . $invoice_id;


        //
        $invoices_path  = PATH_PUBLIC.DS.'files'.DS.'invoices';
        $qrs_path  = PATH_PUBLIC.DS.'files'.DS.'qr';
        $qr_img_name = $invoice_code_id.'.png';
        //
        $qr_code_path  = $qrs_path.DS.$qr_img_name;
        $pdf_file_path = $invoices_path.DS.$invoice_id.'.pdf';
        //echo " $pdf_file_path"; exit;

        
        
        
        $arr_items = "";

        //dd($inv_info); exit;
        foreach($inv_info['arr_sales'] as $idx => $item){
            //dd($item); exit;
            
            //
            $sale_id = $item['id'];

            /*
            //
            $arr_items .= "<tr>";            
            
            $company_name = $item['company_name'];
            $customer_name = $item['customer_name'];
            $grand_total = $item['grand_total'];
            $comisiones = $item['comisiones'];
            $new_total = $item['new_total'];
            $datetime_created = $item['datetime_created']->format("d/M/Y h:i");

            //
            $arr_items .= "<td> #{$sale_id} - {$company_name} {$customer_name}</td>";
            $arr_items .= "<td colspan='4'>  </td>";
            $arr_items .= "<td> {$grand_total} </td>";
            $arr_items .= "<td> {$comisiones} </td>";
            $arr_items .= "<td> {$new_total} </td>";            

            //
            $arr_items .= "<tr>";
            */



            foreach($item['salidas_ocupacion'] as $idx => $item2){
                //dd($item2); exit;
                //
                $arr_items .= "<tr>";    
                //
                $sale_item_id = $item2['id'];
                $num_asiento = $item2['num_asiento'];
                $passanger_name = $item2['passanger_name'];
                $passanger_age = $item2['passanger_age'];
                //
                $origen_info = $item2['origen_info'];
                $destino_info = $item2['destino_info'];
                $fecha_hora_salida = $item2['fecha_hora_salida']->format("d/M/Y h:i");
                $fecha_hora_llegada = $item2['fecha_hora_llegada']->format("d/M/Y h:i");
                //
                $autobus_clave = $item2['autobus_clave'];
                $tipo_precio_descripcion = $item2['tipo_precio_descripcion'];
                $calc_info = $item2['calc_info'];
                //
                $costo_origen_destino = $item2['costo_origen_destino'];                
                $str_costo_ext_salida = ($item2['costo_ext_salida'] > 0) ? "Ext Salida: " . $item2['costo_ext_salida'] . "<br />" : '';
                $str_costo_ext_llegada = ($item2['costo_ext_llegada'] > 0) ? "Ext Llegada: " . $item2['costo_ext_llegada'] . "<br />" : ''; 
                $str_comisiones = $item2['comisiones'] > 0 ? "comisiones: " . $item2['comisiones'] . "<br />" : ''; 

                $sub_total = $item2['sub_total'];
                $total = $item2['total'];
                $comisiones = $item2['comisiones'];
                $new_total = $item2['new_total'];
                
    
                //
                $arr_items .= "<td> {$sale_id}-{$sale_item_id} </td>";
                $arr_items .= "<td> seat #{$num_asiento} <br /> {$passanger_name} ({$passanger_age})  </td>";
                $arr_items .= "<td> {$origen_info} - {$fecha_hora_salida} </td>";
                $arr_items .= "<td> {$destino_info} - {$fecha_hora_llegada} </td>";
                $arr_items .= "<td> {$autobus_clave} </td>";
                //
                $arr_items .= "<td><div> {$str_costo_ext_salida}{$str_costo_ext_llegada}{$total} <br /> {$costo_origen_destino} {$calc_info} </div></td>";
                $arr_items .= "<td><div> {$comisiones} </div></td>";
                $arr_items .= "<td><div> {$new_total} </div></td>";
    
                //
                $arr_items .= "<tr>";
            }



        }
        //echo $arr_items; exit;

        
        $invoice_status = strtoupper($inv_info['invoice_status']);
        //echo $str_cancel; exit;

        //
        $inv_info['comisiones_periodo'] = "<div class='text-cancel-paid'>{$invoice_status}</div> <hr /> Comisiones del perido: " . $inv_info['sum_comisiones'];
        $inv_info['monto_por_pagar'] = "Monto por Pagar: " . $inv_info['sum_por_cobrar'];
        //
        $inv_info['invoice_status'] = "Estatus Invoice: {$invoice_status}";
        $inv_info['arr_items'] = $arr_items;
        $inv_info['datetime_created'] = "Fecha/Hora: " . $periodo_info;
        $inv_info['footer'] = "<div>" . $inv_info['invoice_notes'] . "</div>";
        $inv_info['invoice_title'] = "invoice #<strong>" . $invoice_id . "</strong>";
        $inv_info['invoice_subtitle'] = "<small>created on $periodo_info </small>";
        //
        $inv_info['customer_name'] = "<h3><strong>" .  ucFirst($company_name) . " - " . ucFirst($customer_name) . "</strong> - lugar #1234 </h3>";
        $inv_info['contacto_info'] = "<h3>contact info</h3>";
        $inv_info['company_title'] = "<h3><strong> MissionExpress </h3>";
        $inv_info['company_info'] = "<h3>company info</h3>";
        

        
        //
        $qr_code_url = $site_url."/public/invoices/" . $invoice_code_id;
        $qr_img_url = $site_url . "/files/qr/" . $qr_img_name;        
        //
        CodigoQR::Generar($qrs_path.DS.$qr_img_name, $qr_code_url);
        //
        //echo $qr_img_url; exit;
        $inv_info['qr_img_url'] = "<img src='" . $qr_img_url . "' style='width:200px;height:200px;' alt='QR Code'>";

        


        //dd($inv_info); exit;
        //
        $parsed_content = self::ParseDocumentinvoice($inv_info, $maqueta_content);
        //echo $parsed_content; exit;




        //
        $inline_css = "";
        $page_content = self::setHtmlPageContent($parsed_content, $inline_css);
        

        //
        if ($display_pdf){
            //
            $pdf = Helper::wkhtmlToPdf();
            $pdf->addPage($page_content);
            //
            if (!$pdf->send()) {
                $error = $pdf->getError();
                echo $error; exit;
            }
        }


        
        //
        echo $page_content; exit;
    }





    //
    public static function ParseDocumentinvoice($data, $template_content){
        
        //
        $var_start = "<!--{";
        $var_end = "}-->";
        //
        $php_microparser = new PHPMicroParser($var_start, $var_end);

        //
        $inv_id = $data['id'];      
    
        
        //
        $php_microparser->setVariable("comisiones_periodo", $data['comisiones_periodo']);
        $php_microparser->setVariable("monto_por_pagar", $data['monto_por_pagar']);
        $php_microparser->setVariable("datetime_created", $data['datetime_created']);
        $php_microparser->setVariable("customer_name", $data['customer_name']);
        $php_microparser->setVariable("contacto_info", $data['contacto_info']);
        $php_microparser->setVariable("company_title", $data['company_title']);
        $php_microparser->setVariable("company_info", $data['company_info']);
        $php_microparser->setVariable("invoice_status", $data['invoice_status']);
        $php_microparser->setVariable("arr_items", $data['arr_items']);
        $php_microparser->setVariable("footer", $data['footer']);
        $php_microparser->setVariable("id", $inv_id);
        //
        return $php_microparser->parseVariables($template_content);
    }




    public static function setHtmlPageContent($body_content, $inline_css = ""){
        //
        $site_url = Helper::siteURL();
        // /assets/css/bootstrap.min.css | /assets/css/font-awesome.min.css       

        $font_awesome_min_css = $site_url."/adm/plugins/fontawesome-free/css/all.min.css";

        $bootstrap_min_css = $site_url."/css/bootstrap.min.css";
        //$bootstrap_min_css = $site_url."/adm/css/adminlte.css?v=1.2";

        //
        $page_style = $site_url."/adm/css/ticket-style.css";
        //echo $page_style; exit;
        //
        $additional_css_styles= <<<EOF
        <link rel="stylesheet" type="text/css" media="screen" href="$font_awesome_min_css">
        <link rel="stylesheet" type="text/css" media="screen" href="$bootstrap_min_css">
        <link rel="stylesheet" type="text/css" media="screen" href="$page_style">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
        EOF;


        
        
        //
        return <<<EOF
        <!DOCTYPE html>
        <html lang="en-us">
        <head>
            $additional_css_styles
        <style>
            $inline_css
        </style>
        </head>
        <body>
            $body_content
        </body>
        </html>
        EOF;
    }





    public static function sendInvoice($account_id, $recipients, $attachments, $parsed_subject, $parsed_msg){
        //
        $config_mail = SendMail::getMailConfig($account_id);
        //dd($config_mail); exit;
        //
        return SendMail::Send($config_mail, $recipients, $attachments, $parsed_subject, $parsed_msg, $parsed_msg);
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
        $results = Invoices::Search($search_text);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }





    
    
    





    //
    public function GetRecord($request, $response, $args) {

        //
        //$ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $invoice_id = $args['id'];

        //
        $results = Invoices::GetRecordById($invoice_id);


        //
        return $response->withJson($results, 200);
    }






    





    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;

        //
        $invoice_id = $args['id'];



        //
        $company_name = $v->safeVar($body_data, 'company_name');
        $name = $v->safeVar($body_data, 'name');
        $email  = $v->safeVar($body_data, 'email');

        //
        $phone_country_id = $v->safeVar($body_data, 'phone_country_id');
        $phone_number = $v->safeVar($body_data, 'phone_number');
        $password = $v->safeVar($body_data, 'password');
        //
        $notes = $v->safeVar($body_data, 'notes');
        $allow_credit = Helper::safeVar($body_data, 'allow_credit') ? 1 : 0;
        $active = $v->safeVar($body_data, 'active') ? 1 : 0;


        //
        if ( !$v->validateString([2, 256], $name) ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }


        //
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }
        //
        $countries_results = CatPaises::GetById($phone_country_id);
        //dd($countries_results); exit;
        if ( isset($countries_results['error']) && $countries_results['error'] ){
            $results['error'] = $countries_results["error"];
            return $response->withJson($results, 200);
        }
        //
        if ( !(isset($countries_results['id']) && $countries_results['id']) ){
            $results['error'] = "Country does not exists";
            return $response->withJson($results, 200);
        }

        //
        $phone_cc = $countries_results['phone_cc'];
        //
        if ( !$v->validateString([10, 10], $phone_number) ){
            $results['error'] = "Provide a valid phone number";
            return $response->withJson($results, 200);
        }
        $phone_number_1 = "+".$phone_cc . $phone_number;
        // DEBUG PHONES
        //echo $phone_number_1; exit;

        //
        $email = (filter_var($email, FILTER_VALIDATE_EMAIL) ) ? $email : null;
        $notes = ($notes) ? $notes : null;



        //
        $invoice_info = Query::Single("select * from invoices where id = ?", [$invoice_id]);
        //dd($invoice_info); exit;
        if ( !($invoice_info && isset($invoice_info['id'])) ){
            $results['error'] = "invoice error/not found"; return $response->withJson($results, 200);
        }



        
         


        
        $params = [
            $company_name,
            $name,
            $email,
            $phone_country_id,
            $phone_cc,
            $phone_number,
            $notes,
            $allow_credit,
            $active,
        ];



        


        //echo $str_where_password; exit;
        //dd($params); exit;


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    invoices
                  
                  Set
                    company_name = ?,
                    name = ?,
                    email = ?,
                    phone_country_id = ?,
                    phone_cc = ?,
                    phone_number = ?,
                    notes = ?,
                    allow_credit = ?,
                    active = ?
                    
                  Where id = {$invoice_id}
                  ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($invoice_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$invoice_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }





    //
    public function UpdateNotes($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;

        //
        $invoice_id = $args['id'];



        //
        $notes = $v->safeVar($body_data, 'notes');
        $notes = ($notes) ? $notes : null;
        //echo $notes; exit;




        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    invoices
                  
                  Set
                    invoice_notes = ?
                    
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $notes,
                $invoice_id
            ],
            "parse" => function($updated_rows, &$query_results) use($invoice_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$invoice_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }





    

    //
    public function PostSendEmail($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];

        //
        $invoice_id = $args['id'];
        $email_type = $args['type'];
        //echo " $invoice_id $email_type "; exit;

        //
        $invoice_info = $invoice_info = Query::Single("select * from  where id = ?", [$invoice_id]);
        $customer_email = $invoice_info['email'];
        $customer_name = $invoice_info['name'];


        //
        $send_email_type = null;
        if ($email_type==="register"){
            $send_email_type = MAQUETA_ID_CUST_REGISTRO;
        }
        else if ($email_type==="recup_acct"){
            $send_email_type = MAQUETA_ID_CUST_RECUP_CTA;
        }
        else if ($email_type==="cta_recup"){
            $send_email_type = MAQUETA_ID_CUST_CTA_RECUP;
        }
        else {
            $results['error'] = "proporciona un tipo de correo valido para cliente";
            return $response->withJson($results, 200);
        }
        //echo $send_email_type; exit;


        //
        $send_to_copies = true;
        //
        $send_email_results = Helper::SendEmail($account_id, $app_id, $send_email_type, $customer_name, $customer_email, $send_to_copies, function($maqueta_email_msg) use($invoice_info){
            return Invoices::ParseCustomerMessages($invoice_info, $maqueta_email_msg);
        });




        return $response->withJson($send_email_results, 200);
    }






    //
    public function PostUpdateComisionesRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;


        //
        $comision_tipo = $v->safeVar($body_data, 'comision_tipo');
        $comision_valor = $v->safeVar($body_data, 'comision_valor');

        //
        if ( !($comision_tipo === "p" || $comision_tipo === "m") ){
            $results['error'] = "Proprociona el tipo de comision"; return $response->withJson($results, 200);
        }
        if ( !is_numeric($comision_valor) ){
            $results['error'] = "Proprociona el valor de la comision"; return $response->withJson($results, 200);
        }

        //
        $record_id = $args['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    invoices
                  
                  Set
                    comision_tipo = ?,
                    comision_valor = ?
                    
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $comision_tipo,
                $comision_valor,
                //
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
    public function PostDeleteRecord($request, $response, $args) {



        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;




        //
        $results = array();


        //
        $invoice_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$invoice_id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }
        //echo $invoice_id; exit;

        //
        $results = Query::DoTask([
            "task" => "delete",
            "debug" => false,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "FK_sales_invoices" => "No se puede eliminar el registro por que tiene ventas asociadas"
            ],
            "stmt" => "delete from invoices where id = ?; SELECT @@ROWCOUNT",
            "params" => [
                $invoice_id
            ],
            "parse" => function($updated_rows, &$query_results) use($invoice_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$invoice_id;
            }
        ]);
        //var_dump($results); exit;








        //
        return $response->withJson($results, 200);
    }




    




    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();

        

        //
        $v = new ValidatorHelper();

        $body = $request->getParsedBody();

        //
        $customer_id = Helper::safeVar($body, 'customer_id');
        $company_name = Helper::safeVar($body, 'company_name');
        $customer_name = Helper::safeVar($body, 'customer_name');
        $email = Helper::safeVar($body, 'email');
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_number = Helper::safeVar($body, 'phone_number');
        $notes = Helper::safeVar($body, 'notes');


        //
        if ( !is_numeric($customer_id) ){
            $results['error'] = "Provide customer id"; return $response->withJson($results, 200);
        }
        //
        if ( !$company_name ){
            $results['error'] = "Provide company_name"; return $response->withJson($results, 200);
        }
        //
        if ( !$customer_name ){
            $results['error'] = "Provide customer_name"; return $response->withJson($results, 200);
        }
        //
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "Provide valid email"; return $response->withJson($results, 200);
        }
        

        /*
         * GET PH#1
         * */
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }

         //
         $countries_results = CatPaises::GetById($phone_country_id);
         //dd($countries_results); exit;
         if ( isset($countries_results['error']) && $countries_results['error'] ){
             $results['error'] = $countries_results["error"];
             return $response->withJson($results, 200);
         }
         //
         if ( !(isset($countries_results['id']) && $countries_results['id']) ){
             $results['error'] = "Country does not exists";
             return $response->withJson($results, 200);
         }
 
         //
         $phone_cc = $countries_results['phone_cc'];
         //
         if ( !$v->validateString([10, 10], $phone_number) ){
             $results['error'] = "Provide a valid phone number";
             return $response->withJson($results, 200);
         }
         $phone_number_1 = "+".$phone_cc . $phone_number;
         // DEBUG PHONES
         //echo $phone_number_1; exit;


         
        $customer_info = Customers::GetSellerById($customer_id);
        //dd($customer_info); exit;
        if ( !($customer_info && isset($customer_info['id'])) ){
            $results['error'] = "customer not found"; 
            return $response->withJson($results, 200);
        }
        if ( !($customer_info && isset($customer_info['por_cobrar']) && $customer_info['por_cobrar'] > 0) ){
            $results['error'] = "cliente no tiene ventas pendientes"; 
            return $response->withJson($results, 200);
        }
        
        //
        $param_record_id = 0;
        $param_invoice_code = 0;
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "debug" => false,
            "stmt" => function(){
                return "{call usp_CreateInvoice(?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "ERR_CUSTOMER_WITH_INVOICE_ALREADY_OPENED" => "Cliente ya tiene un invoice abierto, marcar como pagado o cancelar para crear uno nuevo",
            ],
            "params" => function() use(
                //
                $customer_id,
                $company_name,
                $customer_name,
                $email,
                $phone_number,
                $notes,
                //
                $account_id,
                $app_id,
                //
                &$param_record_id,
                &$param_invoice_code
            ){
                //
                return [
                    // 6
                    array($customer_id, SQLSRV_PARAM_IN),
                    array($company_name, SQLSRV_PARAM_IN),
                    array($customer_name, SQLSRV_PARAM_IN),
                    array($email, SQLSRV_PARAM_IN),
                    array($phone_number, SQLSRV_PARAM_IN),
                    array($notes, SQLSRV_PARAM_IN),
                    // 2
                    array($account_id, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    // 2
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                    array(&$param_invoice_code, SQLSRV_PARAM_OUT)
                ];
            },
        ]);
        //dd($sp_res); exit;
        //
        if ($param_record_id > 0){
            $sp_res['id'] = $param_record_id;
        }
        
        

        //
        return $response->withJson($sp_res, 200);
    }








    //
    public function UploadXlsFile($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
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
        $sucursal_id = 7;
        $product_sucursal_active = 7;
        $user_id = 14;


        //
        $the_res = array();
        //
        $the_res['inserts'] = array();

        //
        foreach( $rows as $row_idx => $row ){
            //dd($row); exit;
            //
            if ( $row_idx > 0 ){
                //dd($row); exit;

                //
                $email = null;
                $name = null;
                $phone = null;
                $address1 = null;
                $address2 = null;
                $city = null;
                $state = null;
                $country = null;
                $postal_code = null;
                $created_at = null;
                $last_updated_at = null;
                $last_activity_type = null;
                $last_activity_at = null;
                $worker = null;
                $member = null;
                $private_page_access = null;
                $subscriber = null;
                $blog_subscriber = null;
                $supressed = null;
                $supression_reazon = null;
                $supressed_at = null;
                $tracking_disabled = null;



                //
                foreach( $row as $col_idx => $col ){
                    //dd($col);
                    //
                    if ($col_idx === 0){$email = $col;}
                    if ($col_idx === 1){$name = $col;}
                    if ($col_idx === 2){$last_name = $col;}
                    if ($col_idx === 3){$phone = $col;}
                    if ($col_idx === 4){$address1 = $col;}
                    if ($col_idx === 5){$address2 = $col;}
                    if ($col_idx === 6){$city = $col;}
                    if ($col_idx === 7){$state = $col;}
                    if ($col_idx === 8){$country = $col;}
                    if ($col_idx === 9){$postal_code = $col;}
                    if ($col_idx === 10){$created_at = $col;}
                    if ($col_idx === 11){$last_updated_at = $col;}
                    if ($col_idx === 12){$last_activity_type = $col;}
                    if ($col_idx === 13){$last_activity_at = $col;}
                    if ($col_idx === 14){$worker = $col;}
                    if ($col_idx === 15){$member = $col;}
                    if ($col_idx === 16){$private_page_access = $col;}
                    if ($col_idx === 17){$subscriber = $col;}
                    if ($col_idx === 18){$blog_subscriber = $col;}
                    if ($col_idx === 19){$supressed = $col;}
                    if ($col_idx === 20){$supression_reazon = $col;}
                    if ($col_idx === 21){$supressed_at = $col;}
                    if ($col_idx === 22){$tracking_disabled = $col;}
                }
                //dd($arr_prod); exit;
                //echo "$email, $name, $last_name, $phone, $address1, $address2, $city, $state, $country, $postal_code, $created_at, $last_updated_at, $last_activity_type, $last_activity_at, $worker, $member, $private_page_access, $subscriber, $blog_subscriber, $supressed, $supression_reazon, $supressed_at, $tracking_disabled"; exit;


                //
                $worker_name = null;
                $phone_country_id = 1;
                $phone_cc = "+1";
                $active = 1;
                //
                $blog_subscriber = ( $blog_subscriber === "TRUE" ? 1 : 0 );

                //
                $username = substr($email, 0, strrpos($email, '@'));
                $phone = preg_replace('/\D+/', '', $phone);


                // SI NO ES UN PHONE VALIDO
                if ( !is_numeric($phone) ){
                    $phone = null;
                    $phone_country_id = null;
                    $phone_cc = null;
                }
                //echo $phone . " - " . $username; exit;



                //
                $insert_results = Query::DoTask([
                    "task" => "add",
                    "debug" => true,
                    "stmt" => "
                   Insert Into workers
                  ( worker_name, name, last_name, 
                    username, email, phone_country_id, phone_cc, 
                    phone_number, address, address2, city_code, 
                    state_code, country_code, postal_code, blog_subscribed, 
                    active, datetime_created )
                  Values
                  ( ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                ",
                    "params" => [
                        $worker_name,
                        $name,
                        $last_name,
                        $username,
                        $email,
                        $phone_country_id,
                        $phone_cc,
                        $phone,
                        $address1,
                        $address2,
                        $city,
                        $state,
                        $country,
                        $postal_code,
                        $blog_subscriber,
                        $active
                    ],
                    "parse" => function($insert_id, &$query_results){
                        $query_results['id'] = (int)$insert_id;
                    }
                ]);

                //
                if ( isset($insert_results['error']) && $insert_results['error'] ){
                    return $response->withJson($insert_results, 200);
                }

                //
                array_push($the_res['inserts'], $insert_results);
            }
        }


        //
        $the_res['success'] = true;


        //
        return $response->withJson($the_res, 200);
    }






}
