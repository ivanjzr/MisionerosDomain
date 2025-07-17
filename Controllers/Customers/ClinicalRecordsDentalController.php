<?php
namespace Controllers\Customers;

//
use App\App;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\SendMail;
use Helpers\Query;
use App\Customers\Customers;
use Helpers\ValidatorHelper;
use Controllers\Customers\CustomersHelper;
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;


//
class ClinicalRecordsDentalController extends BaseController
{

    //
    public function ViewIndex($request, $response, $args) {
        //
        $view_data = [
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['customer_id']
        ];
        //
        return $this->container->php_view->render($response, 'admin/customers/clinical-record.phtml', $view_data);
    }

    
    
    
    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";

        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.name like '%$search_value%' ) Or 
                        ( t.clinical_notes like '%$search_value%' ) Or
                        ( t.motivo_consulta like '%$search_value%' ) Or
                        ( t.diagnostico like '%$search_value%' )
                    )";
        }
        //
        return $search_clause;
    }




    
    //
    public function PaginateRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];




        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //echo $search_clause; exit;

        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];
        //echo $order_direction; exit;




        
        
        //
        $customer_id = $args['customer_id'];
        $customer_person_id = null;
        
        // 
        $str_where_customer_person_id = "";
        if (isset($args['customer_person_id']) && $args['customer_person_id']){
            //
            $customer_person_id = $args['customer_person_id'];
            $str_where_customer_person_id = "And customer_relative_id = $customer_person_id";
        } else {
            //
            $str_where_customer_person_id = "And customer_relative_id is Null ";
        }
        //echo $str_where_customer_person_id; exit;

        //
        $table_name = "v_clinical_records_dental";
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
                "count_stmt" => function() use ($table_name, $search_clause, $str_where_customer_person_id){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where app_id = ? And customer_id = ? {$str_where_customer_person_id} {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause, $str_where_customer_person_id){
                    //echo $where_row_clause; exit;
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
                                      
                                           
                                            Where app_id = ?
                                            And customer_id = ?

                                            {$str_where_customer_person_id}
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $customer_id
                ),

                //
                "parseRows" => function(&$row){
                    //
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }



    


    //
    public function GetLastClinicalRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];


        // 
        $customer_id = $args['customer_id'];
        $customer_person_id = (isset($args['customer_person_id']) && $args['customer_person_id']) ? $args['customer_person_id'] : null;
        //echo "$customer_id, $customer_person_id"; exit;

        //
        $str_where_customer_person_id = "";
        if ($customer_person_id){
            $str_where_customer_person_id = "And customer_relative_id = $customer_person_id";
        } else {
            $str_where_customer_person_id = "And customer_relative_id Is Null";
        }

        $stmt = "SELECT top 1 * FROM v_clinical_records_dental where app_id = ? and customer_id = ? {$str_where_customer_person_id} Order By id Desc";
        //echo $stmt; exit;

        //
        $clinical_record_res = Query::Single($stmt, 
            [
                $app_id, $customer_id
            ], 
            function(&$row) use($app_id, $customer_id, $str_where_customer_person_id){
                //echo "$app_id, $customer_id"; exit;
                $clinical_record_id = $row['id'];
                //echo $clinical_record_id; exit;
                //
                $row['arr_history'] = Query::Multiple("SELECT top 5 * FROM v_clinical_records_dental where app_id = ? and customer_id = ? {$str_where_customer_person_id} And id != ? Order By id Desc", [$app_id, $customer_id, $clinical_record_id]);
        });
        //dd($clinical_record_res);

            
        //
        return $response->withJson($clinical_record_res, 200);
    }






    //
    public function GetPrintLastClinicalRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];
        
        
        //
        $customer_id = $args['customer_id'];
        $customer_person_id = null;
        
        // 
        if (isset($args['customer_person_id']) && $args['customer_person_id']){
            $customer_person_id = $args['customer_person_id'];
        }
        //echo $customer_person_id; exit;
        //
        //$action = $_GET['action'] ?? 'preview';
        //echo $action; exit;
        

        // Uso simple (preview)
        $helper = new CustomersHelper($app_id, $customer_id, $customer_person_id);

        // si tenemos el registro continuamos
        if (isset($helper->results['id']) && $helper->results['id']){
            //
            $helper->generateExpedienteHtml();
            //        
            $helper->saveExpedientePdf();
            $helper->downloadExpedientePdf();            
        } else {
            echo "No Expediente Found"; exit;
        }
        
    }




    //
    public function PostSendLastClinicalRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $customer_id = $args['customer_id'];        
        $customer_person_id = null;
        

        // 
        if (isset($args['customer_person_id']) && $args['customer_person_id']){
            $customer_person_id = $args['customer_person_id'];
        }
        //echo $customer_person_id; exit;

        // Uso simple (preview)
        $helper = new CustomersHelper($app_id, $customer_id, $customer_person_id);


        // si tenemos el registro continuamos
        if ( !(isset($helper->results['id']) && $helper->results['id']) ){
            $results['error'] = "El paciente aun no cuenta con expediente clinico"; 
            return $response->withJson($results, 200);
        }


       /**
         * 
         * Genera y guarda el expediente
         */
        $helper->generateExpedienteHtml();
        $helper->saveExpedientePdf();


        

        /**
         * 
         * Obtenemos el path y nombre del archivo en base al CustomersHelper el cual ahi los crea
         */
        $pdf_file_path = $helper->getExpedientePdfPath();        
        $filename = $helper->createClinicalRecordFileName();
        //echo "download/preview on pdf path: $pdf_file_path"; exit;
        //echo "pdf file name: $filename"; exit;



        
        //
        $customer_info = $helper->results;
        //dd($customer_info);

        //
        $str_relative = "";
        if ($customer_info['customer_relative_id']){
            $str_relative = " (" . $customer_info['relative_type'] . ")";
        }
        
        //
        $customer_name = $customer_info['customer_name'] . $str_relative;
        $customer_email = $customer_info['email'];

        //
        $config_mail = SendMail::getMailConfig($account_id, $app_id);
        //dd($config_mail); exit;

        $tmpl_res = MaquetasMensajes::GetMaquetaInfo($account_id, MAQUETA_ID_CLINICAL_RECORD, false, true);
        //dd($tmpl_res); exit;
        if ( isset($tmpl_res['error']) && $tmpl_res['error'] ){
            return $response->withJson($tmpl_res, 200);
        }
        //
        if ( !(isset($tmpl_res['id']) && $tmpl_res['id'] && $tmpl_res['email_msg'] && $tmpl_res['email_active']) ){
            $results['error'] = "Maqueta de correo no encontrada o inactiva"; 
            return $response->withJson($results, 200);
        }

        //
        $parsed_subject = $helper::parseExpedinteClinicoEmailContent($customer_info, $tmpl_res['email_subject']);
        $parsed_msg = $helper::parseExpedinteClinicoEmailContent($customer_info, $tmpl_res['email_msg']);
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
            "filepath" => $pdf_file_path
        ));


        //
        $send_res = SendMail::Send($config_mail, $recipients, $attachments, $parsed_subject, $parsed_msg, $parsed_msg);
        //dd($send_res);
        
        //
        return $response->withJson($send_res, 200);
    }






    


    //
    public function GetRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];

        //
        $customer_id = $args['customer_id'];
        $clinical_record_id = $args['id'];
        
        //
        $results = Query::Single("SELECT * FROM v_clinical_records_dental where app_id = ? and customer_id = ? and clinical_record_id = ?", 
            [$app_id, $customer_id, $clinical_record_id]);
        //
        return $response->withJson($results, 200);
    }



    //
    public function AddRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        $results = array();
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();

        //
        $customer_id = $args['customer_id'];

        // Validar que el customer existe
        $customer_exists = Query::Single("SELECT id from v_customers where app_id = ? AND id = ? AND active = 1", 
            [$app_id, $customer_id]);
        if (!$customer_exists) {
            $results['error'] = "El paciente especificado no existe o está inactivo"; 
            return $response->withJson($results, 200);
        }

        // Campos principales del registro clínico
        $revision_date = Helper::safeVar($body, 'revision_date');
        $notes = Helper::safeVar($body, 'notes');
        //
        $customer_relative_id_val = Helper::safeVar($body, 'customer_relative_id');
        $customer_relative_id = ($customer_relative_id_val) ? $customer_relative_id_val : null;
        
        

        // Campos básicos
        $peso_kg = Helper::safeVar($body, 'peso_kg');
        $estatura_cm = Helper::safeVar($body, 'estatura_cm');
        $presion_sistolica = Helper::safeVar($body, 'presion_sistolica');
        $presion_diastolica = Helper::safeVar($body, 'presion_diastolica');
        $glucosa_mg_dl = Helper::safeVar($body, 'glucosa_mg_dl');
        $temperatura_celsius = Helper::safeVar($body, 'temperatura_celsius');
        $frecuencia_cardiaca = Helper::safeVar($body, 'frecuencia_cardiaca');
        $alergias = Helper::safeVar($body, 'alergias');
        $medicamentos_actuales = Helper::safeVar($body, 'medicamentos_actuales');
        $antecedentes_familiares = Helper::safeVar($body, 'antecedentes_familiares');

        // Campos específicos dentales
        $motivo_consulta = Helper::safeVar($body, 'motivo_consulta');
        $dolor_nivel_1_10 = Helper::safeVar($body, 'dolor_nivel_1_10');
        $sangrado_encias = Helper::safeVar($body, 'sangrado_encias') ? 1 : 0;
        $sensibilidad_dental = Helper::safeVar($body, 'sensibilidad_dental') ? 1 : 0;
        $mal_aliento = Helper::safeVar($body, 'mal_aliento') ? 1 : 0;
        $ortodoncia_previa = Helper::safeVar($body, 'ortodoncia_previa') ? 1 : 0;
        $cepillado_diario_veces = Helper::safeVar($body, 'cepillado_diario_veces');
        $uso_hilo_dental = Helper::safeVar($body, 'uso_hilo_dental') ? 1 : 0;
        $ultima_limpieza_meses = Helper::safeVar($body, 'ultima_limpieza_meses');
        $odontograma = Helper::safeVar($body, 'odontograma');
        $diagnostico = Helper::safeVar($body, 'diagnostico');
        $tratamiento_recomendado = Helper::safeVar($body, 'tratamiento_recomendado');

        // Validaciones obligatorias
        if (!$revision_date) {
            $results['error'] = "La fecha de revisión es obligatoria"; 
            return $response->withJson($results, 200);
        }
        if (!$motivo_consulta) {
            $results['error'] = "El motivo de consulta es obligatorio"; 
            return $response->withJson($results, 200);
        }

        // Validar formato de fecha
        //echo $revision_date; exit;
        $revision_date_obj = \DateTime::createFromFormat('Y-m-d H:i', $revision_date);
        if (!$revision_date_obj) {
            $results['error'] = "Formato de fecha de revisión inválido"; 
            return $response->withJson($results, 200);
        }
        //dd($revision_date_obj);




        // Validar rangos numéricos
        if ($dolor_nivel_1_10 && ($dolor_nivel_1_10 < 1 || $dolor_nivel_1_10 > 10)) {
            $results['error'] = "El nivel de dolor debe estar entre 1 y 10"; 
            return $response->withJson($results, 200);
        }

        if ($peso_kg && ($peso_kg < 0 || $peso_kg > 999.99)) {
            $results['error'] = "El peso debe ser un valor válido"; 
            return $response->withJson($results, 200);
        }

        if ($estatura_cm && ($estatura_cm < 0 || $estatura_cm > 999.99)) {
            $results['error'] = "La estatura debe ser un valor válido"; 
            return $response->withJson($results, 200);
        }

        if ($temperatura_celsius && ($temperatura_celsius < 30 || $temperatura_celsius > 45)) {
            $results['error'] = "La temperatura debe estar entre 30 y 45 grados Celsius"; 
            return $response->withJson($results, 200);
        }

        // Insertar registro principal
        $insert_results = Query::DoTask([
            "task" => "add",
            "stmt" => "
                Insert Into clinical_records
                ( app_id, customer_id, customer_relative_id, revision_date, created_user_id, notes, status, datetime_created )
                Values
                ( ?, ?, ?, ?, ?, ?, 'active', GETDATE() )
                ;SELECT SCOPE_IDENTITY()   
            ",
            "params" => [
                $app_id,
                $customer_id,
                $customer_relative_id,
                $revision_date,
                $user_id,
                $notes
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);

        if (isset($insert_results['error']) && $insert_results['error']) {
            return $response->withJson($insert_results, 200);
        }

        $clinical_record_id = $insert_results['id'];
        //echo "$clinical_record_id, $motivo_consulta"; exit;



        // Insertar datos básicos si se proporcionaron
        if ( $peso_kg || $estatura_cm || $presion_sistolica || $presion_diastolica || $alergias || $medicamentos_actuales || $antecedentes_familiares ) {
            //
            $params = [
                $app_id, 
                $clinical_record_id, 
                cleanNumericValue($peso_kg),
                cleanNumericValue($estatura_cm), 
                cleanNumericValue($presion_sistolica),
                cleanNumericValue($presion_diastolica), 
                cleanNumericValue($glucosa_mg_dl),
                cleanNumericValue($temperatura_celsius), 
                cleanNumericValue($frecuencia_cardiaca),
                $alergias ?: null,
                $medicamentos_actuales ?: null, 
                $antecedentes_familiares ?: null
            ];
            //dd($params);
            //
            $insert_results['basic_res'] = Query::DoTask([
                "task" => "add",
                "debug" => false,
                "stmt" => "
                    Insert Into clinical_records_basic
                    ( app_id, clinical_record_id, peso_kg, 
                      estatura_cm, presion_sistolica, presion_diastolica, glucosa_mg_dl, 
                      temperatura_celsius, frecuencia_cardiaca, alergias, medicamentos_actuales, 
                      antecedentes_familiares, datetime_created )
                    Values
                    ( ?, ?, ?, 
                      ?, ?, ?, ?, 
                      ?, ?, ?, ?, 
                      ?, GETDATE() );
                    ;SELECT SCOPE_IDENTITY();
                ",
                "params" => $params
            ]);
            //dd($insert_results);
        }

        //
        $params_2 = [
            $app_id, 
            $clinical_record_id, 
            $motivo_consulta ?: null,
            cleanNumericValue($dolor_nivel_1_10),
            $sangrado_encias ?: null,
            $sensibilidad_dental ?: null,
            $mal_aliento ?: null,
            $ortodoncia_previa ?: null,
            cleanNumericValue($cepillado_diario_veces),
            $uso_hilo_dental ?: null,
            cleanNumericValue($ultima_limpieza_meses),
            $odontograma ?: null,
            $diagnostico ?: null,
            $tratamiento_recomendado ?: null
        ];

        // Insertar datos dentales
        $insert_results['dental_res'] = Query::DoTask([
            "task" => "add", 
            "debug" => false,
            "stmt" => "
                Insert Into clinical_records_dental
                ( app_id, clinical_record_id, motivo_consulta, dolor_nivel_1_10, sangrado_encias, 
                  sensibilidad_dental, mal_aliento, ortodoncia_previa, cepillado_diario_veces, 
                  uso_hilo_dental, ultima_limpieza_meses, odontograma, diagnostico, 
                  tratamiento_recomendado, datetime_created )
                Values
                ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE() );
                ;SELECT SCOPE_IDENTITY();
            ",
            "params" => $params_2
        ]);
        //dd($insert_results);

        //
        return $response->withJson($insert_results, 200);
    }

    //
    public function UpdateRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        $results = array();
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();

        //
        $customer_id = $args['customer_id'];
        $clinical_record_id = $args['id'];

        // Validar que el registro existe
        $record_exists = Query::Single("SELECT * FROM clinical_records WHERE app_id = ? AND id = ? AND customer_id = ?", 
            [$app_id, $clinical_record_id, $customer_id]);
        //dd($record_exists);
        if ( !($record_exists && isset($record_exists['id']) && $record_exists['id']) ) {
            $results['error'] = "El registro clínico especificado no existe"; 
            return $response->withJson($results, 200);
        }

        // Campos principales del registro clínico
        $revision_date = Helper::safeVar($body, 'revision_date');
        $notes = Helper::safeVar($body, 'notes');

        // Campos básicos
        $peso_kg = Helper::safeVar($body, 'peso_kg');
        $estatura_cm = Helper::safeVar($body, 'estatura_cm');
        $presion_sistolica = Helper::safeVar($body, 'presion_sistolica');
        $presion_diastolica = Helper::safeVar($body, 'presion_diastolica');
        $glucosa_mg_dl = Helper::safeVar($body, 'glucosa_mg_dl');
        $temperatura_celsius = Helper::safeVar($body, 'temperatura_celsius');
        $frecuencia_cardiaca = Helper::safeVar($body, 'frecuencia_cardiaca');
        $alergias = Helper::safeVar($body, 'alergias');
        $medicamentos_actuales = Helper::safeVar($body, 'medicamentos_actuales');
        $antecedentes_familiares = Helper::safeVar($body, 'antecedentes_familiares');

        // Campos específicos dentales
        $motivo_consulta = Helper::safeVar($body, 'motivo_consulta');
        $dolor_nivel_1_10 = Helper::safeVar($body, 'dolor_nivel_1_10');
        $sangrado_encias = Helper::safeVar($body, 'sangrado_encias') ? 1 : 0;
        $sensibilidad_dental = Helper::safeVar($body, 'sensibilidad_dental') ? 1 : 0;
        $mal_aliento = Helper::safeVar($body, 'mal_aliento') ? 1 : 0;
        $ortodoncia_previa = Helper::safeVar($body, 'ortodoncia_previa') ? 1 : 0;
        $cepillado_diario_veces = Helper::safeVar($body, 'cepillado_diario_veces');
        $uso_hilo_dental = Helper::safeVar($body, 'uso_hilo_dental') ? 1 : 0;
        $ultima_limpieza_meses = Helper::safeVar($body, 'ultima_limpieza_meses');
        $odontograma = Helper::safeVar($body, 'odontograma');
        $diagnostico = Helper::safeVar($body, 'diagnostico');
        $tratamiento_recomendado = Helper::safeVar($body, 'tratamiento_recomendado');

        // Validaciones obligatorias
        if (!$revision_date) {
            $results['error'] = "La fecha de revisión es obligatoria"; 
            return $response->withJson($results, 200);
        }

        if (!$motivo_consulta) {
            $results['error'] = "El motivo de consulta es obligatorio"; 
            return $response->withJson($results, 200);
        }

        // Validaciones de rangos (mismas que en AddRecord)
        if ($dolor_nivel_1_10 && ($dolor_nivel_1_10 < 1 || $dolor_nivel_1_10 > 10)) {
            $results['error'] = "El nivel de dolor debe estar entre 1 y 10"; 
            return $response->withJson($results, 200);
        }

        if ($peso_kg && ($peso_kg < 0 || $peso_kg > 999.99)) {
            $results['error'] = "El peso debe ser un valor válido"; 
            return $response->withJson($results, 200);
        }

        if ($temperatura_celsius && ($temperatura_celsius < 30 || $temperatura_celsius > 45)) {
            $results['error'] = "La temperatura debe estar entre 30 y 45 grados Celsius"; 
            return $response->withJson($results, 200);
        }


        // Validar formato de fecha
        //echo $revision_date; exit;
        $revision_date_obj = \DateTime::createFromFormat('Y-m-d H:i', $revision_date);
        if (!$revision_date_obj) {
            $results['error'] = "Formato de fecha de revisión inválido"; 
            return $response->withJson($results, 200);
        }
        //dd($revision_date_obj);




        // Actualizar registro principal
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                Update clinical_records
                Set
                    revision_date = ?,
                    notes = ?
                Where app_id = ?
                And customer_id = ?
                And id = ?
                ;SELECT @@ROWCOUNT
            ",
            "params" => [
                $revision_date,
                $notes,
                $app_id,
                $customer_id,
                $clinical_record_id
            ], 
            "parse" => function($updated_rows, &$query_results) use($app_id, $clinical_record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$clinical_record_id;
            }
        ]);
        //dd($update_results);

        // Actualizar o insertar datos básicos
        $basic_exists = Query::Single("SELECT id FROM clinical_records_basic WHERE app_id = ? AND clinical_record_id = ?", 
            [$app_id, $clinical_record_id]);

        if ($basic_exists) {

            $params = [
                cleanNumericValue($peso_kg),
                cleanNumericValue($estatura_cm), 
                cleanNumericValue($presion_sistolica),
                cleanNumericValue($presion_diastolica), 
                cleanNumericValue($glucosa_mg_dl),
                cleanNumericValue($temperatura_celsius), 
                cleanNumericValue($frecuencia_cardiaca),
                $alergias ?: null,
                $medicamentos_actuales ?: null, 
                $antecedentes_familiares ?: null,
                $app_id, 
                $clinical_record_id
            ];
            //dd($params);

            // Actualizar
            $update_results['updt_basic'] = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                    Update clinical_records_basic
                    Set
                        peso_kg = ?, estatura_cm = ?, presion_sistolica = ?, presion_diastolica = ?,
                        glucosa_mg_dl = ?, temperatura_celsius = ?, frecuencia_cardiaca = ?,
                        alergias = ?, medicamentos_actuales = ?, antecedentes_familiares = ?
                    Where app_id = ? And clinical_record_id = ?;
                    ;SELECT @@ROWCOUNT
                ",
                "params" => $params
            ]);
            //dd($update_res);

        } else if ($peso_kg || $estatura_cm || $presion_sistolica || $alergias || $medicamentos_actuales || $antecedentes_familiares) {
            // Insertar
            Query::DoTask([
                "task" => "add",
                "stmt" => "
                    Insert Into clinical_records_basic
                    ( app_id, clinical_record_id, peso_kg, estatura_cm, presion_sistolica, presion_diastolica, 
                      glucosa_mg_dl, temperatura_celsius, frecuencia_cardiaca, alergias, medicamentos_actuales, 
                      antecedentes_familiares, datetime_created )
                    Values
                    ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE() );
                     ;SELECT SCOPE_IDENTITY()   
                ",
                "params" => [
                    $app_id, $clinical_record_id, $peso_kg, $estatura_cm, $presion_sistolica, 
                    $presion_diastolica, $glucosa_mg_dl, $temperatura_celsius, $frecuencia_cardiaca,
                    $alergias, $medicamentos_actuales, $antecedentes_familiares
                ]
            ]);
        }

        // Actualizar o insertar datos dentales
        $dental_exists = Query::Single("SELECT id FROM clinical_records_dental WHERE app_id = ? AND clinical_record_id = ?", 
            [$app_id, $clinical_record_id]);

        if ($dental_exists) {


            //
            $params_2 = [                
                $motivo_consulta ?: null,
                cleanNumericValue($dolor_nivel_1_10),
                $sangrado_encias ?: null,
                $sensibilidad_dental ?: null,
                $mal_aliento ?: null,
                $ortodoncia_previa ?: null,
                cleanNumericValue($cepillado_diario_veces),
                $uso_hilo_dental ?: null,
                cleanNumericValue($ultima_limpieza_meses),
                $odontograma ?: null,
                $diagnostico ?: null,
                $tratamiento_recomendado ?: null,
                //
                $app_id, 
                $clinical_record_id
            ];
            //dd($params_2);

            // Actualizar
            $update_results['updt_dental'] = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                    Update clinical_records_dental
                    Set
                        motivo_consulta = ?, dolor_nivel_1_10 = ?, sangrado_encias = ?, 
                        sensibilidad_dental = ?, mal_aliento = ?, ortodoncia_previa = ?, 
                        cepillado_diario_veces = ?, uso_hilo_dental = ?, ultima_limpieza_meses = ?,
                        odontograma = ?, diagnostico = ?, tratamiento_recomendado = ?
                    Where app_id = ? And clinical_record_id = ?;
                    ;SELECT @@ROWCOUNT
                ",
                "params" => $params_2
            ]);
            //dd($update_res);
        } else {
            // Insertar
            Query::DoTask([
                "task" => "add", 
                "stmt" => "
                    Insert Into clinical_records_dental
                    ( app_id, clinical_record_id, motivo_consulta, dolor_nivel_1_10, sangrado_encias, 
                      sensibilidad_dental, mal_aliento, ortodoncia_previa, cepillado_diario_veces, 
                      uso_hilo_dental, ultima_limpieza_meses, odontograma, diagnostico, 
                      tratamiento_recomendado, datetime_created )
                    Values
                    ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE() )
                     ;SELECT SCOPE_IDENTITY()   
                ",
                "params" => [
                    $app_id, $clinical_record_id, $motivo_consulta, $dolor_nivel_1_10, $sangrado_encias,
                    $sensibilidad_dental, $mal_aliento, $ortodoncia_previa, $cepillado_diario_veces,
                    $uso_hilo_dental, $ultima_limpieza_meses, $odontograma, $diagnostico, 
                    $tratamiento_recomendado
                ]
            ]);
        }

        //
        return $response->withJson($update_results, 200);
    }


    

    //
    public function DeleteRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];

        //
        $results = array();
        $body = $request->getParsedBody();

        // 
        $customer_id = $args['customer_id'];
        $clinical_record_id = Helper::safeVar($body, 'id');
        //echo "$customer_id, $clinical_record_id"; exit;

        if (!$clinical_record_id) {
            $results['error'] = "ID del registro clínico es requerido"; 
            return $response->withJson($results, 200);
        }

        // Validar que el registro existe
        $record_exists = Query::Single("SELECT id FROM clinical_records WHERE app_id = ? AND id = ? AND customer_id = ?", 
            [$app_id, $clinical_record_id, $customer_id]);
        //dd($record_exists);
        if (!$record_exists) {
            $results['error'] = "El registro clínico especificado no existe"; 
            return $response->withJson($results, 200);
        }

        //
        $delete_results = Query::DoTask([
            "task" => "delete",
            "debug" => true,
            "stmt" => "Delete from clinical_records where app_id = ? And customer_id = ? And id = ?; SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $customer_id,
                $clinical_record_id,
            ],
            "parse" => function($updated_rows, &$query_results) use($clinical_record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$clinical_record_id;
            }
        ]);
        //dd($delete_results);

        //
        return $response->withJson($delete_results, 200);
    }

}