<?php
namespace Controllers\Customers;

//
use App\App;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;
use Helpers\ValidatorHelper;
use Helpers\PHPMicroParser;


//
class CustomersHelper
{
    //
    private $app_id;
    private $customer_id;
    private $customer_person_id;
    private $customer_data;
    private $relative_type;
    public $results;
    private $page_content;

    // Constructor
    public function __construct($app_id, $customer_id, $customer_person_id = null) {
        $this->app_id = $app_id;
        $this->customer_id = $customer_id;
        $this->customer_person_id = $customer_person_id;
        
        // Inicializar datos del cliente
        $this->initializeCustomerData();
    }

    // Inicializar datos del cliente
    private function initializeCustomerData() {
        //echo $this->app_id . " - " . $this->customer_id; exit;

        //
        $str_where_customer_relative = "";
        //
        if ($this->customer_person_id){
            $str_where_customer_relative = " And customer_relative_id = " . $this->customer_person_id;
        } else {
            $str_where_customer_relative = " And customer_relative_id is Null";
        }
        //echo $str_where_customer_relative; exit;

        $stmt = "SELECT top 1 * FROM v_clinical_records_dental where app_id = ? and customer_id = ? {$str_where_customer_relative} Order By id Desc";
        //echo $stmt; exit;
        
        // Obtener resultados clínicos
        $this->results = Query::Single($stmt, 
            [
                $this->app_id, $this->customer_id
            ], 
            function(&$row) use($str_where_customer_relative){
                $clinical_record_id = $row['id'];
                $row['last_records'] = Query::Multiple("SELECT top 3 * FROM v_clinical_records_dental where app_id = ? and customer_id = ? {$str_where_customer_relative} And id != ? Order By id Desc", [$this->app_id, $this->customer_id, $clinical_record_id]);
        });        
        //
        //dd($this->results);
    }

    // Generar contenido del PDF
    public function generateExpedienteHtml() {

        //
        $current_date = date('d/m/Y H:i');
        $revision_date = $this->results['revision_date']->format('d/m/Y H:i');

        // Función helper para generar historial
        $generateHistory = function($field_name, $formatter = null) {
            if (empty($this->results['last_records'])) return '';
            
            $history_html = "<div class='history-container'><div class='history-label'>Historial:</div>";
            foreach ($this->results['last_records'] as $record) {

                //
                $date = $record['revision_date']->format('d/m/y');
                $value = $record[$field_name];
                
                
                if ($formatter && is_callable($formatter)) {
                    $value = $formatter($value, $record);
                } else {
                    $value = $value ?: '--';
                    if (strlen($value) > 25) {
                        $value = substr($value, 0, 25) . '...';
                    }
                }
                
                $history_html .= "<span class='history-item'>{$date}: {$value}</span>";
            }
            $history_html .= "</div>";
            return $history_html;
        };

        //
        $str_relative_type = "";
        if ($this->results['customer_relative_id']) {
            $str_relative_type = $this->results['relative_type'];
        }

        //
        if ($this->results['birth_date']) {
            $str_birth_date = $this->results['birth_date']->format("d M Y") . " / " . $this->results['edad_years'];
        }

        
        //
        $logo_path = "https://dentablix.com/logos/dentablix.png";


        //
        $customer_info = "
            <div class='custom-col custom-col-4'>
                <div class='info-field'>
                    <span class='field-label'><i class='fas fa-user me-1'></i>Nombre Completo</span>
                    <span class='field-value'><strong> " . $this->results['customer_name'] . "{$str_relative_type}</strong></span>
                </div>
            </div>
            <div class='custom-col custom-col-4'>
                <div class='info-field'>
                    <span class='field-label'><i class='fas fa-envelope me-1'></i>Email</span>
                    <span class='field-value'>" . $this->results['email'] . "</span>
                </div>
            </div>
            <div class='custom-col custom-col-4'>
                <div class='info-field'>
                    <span class='field-label'><i class='fas fa-phone me-1'></i>Teléfono</span>
                    <span class='field-value'>" . $this->results['phone_number'] . "</span>
                </div>
            </div>
            <div class='custom-col custom-col-4'>
                <div class='info-field'>
                    <span class='field-label'><i class='fas fa-calendar me-1'></i>Fecha Nacimiento / Edad</span>
                    <span class='field-value'>" . $str_birth_date . "</span>
                </div>
            </div>";

            //echo $this->results['customer_name']; exit;

$this->page_content = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Expediente Clínico - " . $this->results['customer_name'] . " " . $this->relative_type . "</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            font-size: 16px; 
            line-height: 1.5;
            margin: 0;
            padding: 8px;
        }
        
        /* Header con logo */
        .header-logo {
            display: table;
            width: 100%;
            margin-top: 20px;
            padding-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
        }
        
        /* Header de 3 columnas */
        .header-column-left {
            display: table-cell;
            vertical-align: middle;
            width: 25%;
            text-align: left;
        }
        .header-column-center {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
            text-align: center;
        }
        .header-column-right {
            display: table-cell;
            vertical-align: middle;
            width: 25%;
            text-align: right;
        }
        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .header-subtitle {
            font-size: 14px;
            font-weight: normal;
            color: #495057;
        }

        .logo-placeholder {
            border: 2px dashed #ccc;
            background: #f8f9fa;
            display: inline-block;
            line-height: 50px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        .header-info {
            font-size: 11px;
            color: #6c757d;
            line-height: 1.4;
        }
        .header-info strong {
            color: #495057;
        }
        
        /* Secciones */
        .section-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 15px 18px;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 1px solid #dee2e6;
            display: table;
            width: 100%;
        }
        
        .section-title {
            display: table-cell;
            vertical-align: middle;
        }
        
        .section-date {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-size: 16px;
            font-weight: 600;
        }
        
        .section-patient { background-color: #e3f2fd; color: #1565c0; }
        .section-dental { background-color: #fff3e0; color: #ef6c00; }
        .section-medical { background-color: #f3e5f5; color: #7b1fa2; }
        
        .section-body { padding: 18px; background: white; }
        
        /* Sistema de columnas personalizado para wkhtmltopdf */
        .custom-row { 
            display: table; 
            width: 100%; 
            margin-bottom: 15px;
        }
        .custom-col { 
            display: table-cell; 
            vertical-align: top; 
            padding-right: 18px;
        }
        .custom-col:last-child { padding-right: 0; }
        .custom-col-2 { width: 50%; }
        .custom-col-3 { width: 33.33%; }
        .custom-col-4 { width: 25%; }
        .custom-col-6 { width: 16.66%; }
        
        /* Campos de información */
        .info-field {
            margin-bottom: 12px;
        }
        .field-label {
            font-weight: 600;
            color: #495057;
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .field-value {
            color: #212529;
            font-size: 16px;
            display: block;
        }
        
        /* Badges y estados */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        
        /* Historial */
        .history-container {
            margin-top: 6px;
        }
        .history-label {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 3px;
        }
        .history-item {
            display: inline-block;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 3px 8px;
            margin: 2px 3px 2px 0;
            border-radius: 3px;
            font-size: 10px;
            color: #495057;
        }
        
        /* Footer */
        .footer-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
        
        /* Notas */
        .notes-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 18px;
            margin: 15px 0;
        }
        .notes-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #495057;
        }
        
        @media print { 
            body { margin: 0; padding: 5px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    
    <!-- Header de 3 columnas -->
    <div class='header-logo'>
        <div class='header-column-left'>
            <img src='{$logo_path}' alt='Logotipo' style='width: 170px; height: 52px;'>
        </div>
        <div class='header-column-center'>
            <div class='header-title'>
                <i class='fas fa-file-medical me-2'></i>
                EXPEDIENTE CLÍNICO DENTAL
            </div>
            <div class='header-subtitle'>
                Fecha de Revisión: {$revision_date}
            </div>
        </div>
        <div class='header-column-right'>
            <div class='header-info'>
                <strong><i class='fas fa-hashtag me-1'></i>Folio del Expediente:</strong> {$this->results['id']}<br>
                <strong><i class='fas fa-calendar-plus me-1'></i>Fecha de Registro:</strong> " . $this->results['datetime_created']->format('d/m/Y H:i') . "<br>
                <strong><i class='fas fa-user-md me-1'></i>Usuario Creador:</strong> ID-{$this->results['created_user_id']}
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN DEL PACIENTE -->
    <div class='section-card'>
        <div class='section-header section-patient'>
            <div class='section-title'>
                <i class='fas fa-user-circle me-2'></i>Información del Paciente
            </div>            
        </div>
        <div class='section-body'>
            <div class='custom-row'>
                {$customer_info}
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN DENTAL -->
    <div class='section-card'>
        <div class='section-header section-dental'>
            <i class='fas fa-tooth me-2'></i>Información Dental
        </div>
        <div class='section-body'>
            <!-- Motivo y Dolor -->
            <div class='custom-row'>
                <div class='custom-col custom-col-2'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-comment-medical me-1'></i>Motivo de Consulta</span>
                        <span class='field-value'>" . ($this->results['motivo_consulta'] ?: 'Sin especificar') . "</span>
                        " . $generateHistory('motivo_consulta') . "
                    </div>
                </div>
                <div class='custom-col custom-col-2'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-thermometer-full me-1'></i>Nivel de Dolor (1-10)</span>
                        <span class='field-value'>" . ($this->results['dolor_nivel_1_10'] ? $this->results['dolor_nivel_1_10'] . '/10' : 'Sin evaluar') . "</span>
                        " . $generateHistory('dolor_nivel_1_10', function($value) { 
                            return $value ? $value . '/10' : '--'; 
                        }) . "
                    </div>
                </div>
            </div>

            <!-- Síntomas y Hábitos -->
            <div class='custom-row'>
                <div class='custom-col custom-col-4'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-tint me-1'></i>Sangrado de Encías</span>
                        <span class='status-badge " . ($this->results['sangrado_encias'] ? 'badge-danger' : 'badge-success') . "'>" . ($this->results['sangrado_encias'] ? 'Presente' : 'Ausente') . "</span>
                        " . $generateHistory('sangrado_encias', function($value) { 
                            return $value ? 'Sí' : 'No'; 
                        }) . "
                    </div>
                </div>
                <div class='custom-col custom-col-4'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-snowflake me-1'></i>Sensibilidad Dental</span>
                        <span class='status-badge " . ($this->results['sensibilidad_dental'] ? 'badge-warning' : 'badge-success') . "'>" . ($this->results['sensibilidad_dental'] ? 'Presente' : 'Normal') . "</span>
                        " . $generateHistory('sensibilidad_dental', function($value) { 
                            return $value ? 'Sí' : 'No'; 
                        }) . "
                    </div>
                </div>
                <div class='custom-col custom-col-4'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-wind me-1'></i>Mal Aliento</span>
                        <span class='status-badge " . ($this->results['mal_aliento'] ? 'badge-warning' : 'badge-success') . "'>" . ($this->results['mal_aliento'] ? 'Presente' : 'Ausente') . "</span>
                        " . $generateHistory('mal_aliento', function($value) { 
                            return $value ? 'Sí' : 'No'; 
                        }) . "
                    </div>
                </div>
                <div class='custom-col custom-col-4'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-brush me-1'></i>Cepillado Diario</span>
                        <span class='field-value'>" . ($this->results['cepillado_diario_veces'] ? $this->results['cepillado_diario_veces'] . ' veces/día' : 'Sin especificar') . "</span>
                        " . $generateHistory('cepillado_diario_veces', function($value) { 
                            return $value ? $value . 'x' : '--'; 
                        }) . "
                    </div>
                </div>
            </div>

            <!-- Hábitos de Higiene -->
            <div class='custom-row'>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-teeth me-1'></i>Uso de Hilo Dental</span>
                        <span class='status-badge " . ($this->results['uso_hilo_dental'] ? 'badge-success' : 'badge-warning') . "'>" . ($this->results['uso_hilo_dental'] ? 'Sí usa' : 'No usa') . "</span>
                        " . $generateHistory('uso_hilo_dental', function($value) { 
                            return $value ? 'Sí' : 'No'; 
                        }) . "
                    </div>
                </div>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-history me-1'></i>Última Limpieza</span>
                        <span class='field-value'>" . ($this->results['ultima_limpieza_meses'] ? $this->results['ultima_limpieza_meses'] . ' meses' : 'Sin especificar') . "</span>
                        " . $generateHistory('ultima_limpieza_meses', function($value) { 
                            return $value ? $value . 'm' : '--'; 
                        }) . "
                    </div>
                </div>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-align-center me-1'></i>Ortodoncia Previa</span>
                        <span class='status-badge " . ($this->results['ortodoncia_previa'] ? 'badge-info' : 'badge-secondary') . "'>" . ($this->results['ortodoncia_previa'] ? 'Sí tuvo' : 'No tuvo') . "</span>
                        " . $generateHistory('ortodoncia_previa', function($value) { 
                            return $value ? 'Sí' : 'No'; 
                        }) . "
                    </div>
                </div>
            </div>

            <!-- Diagnóstico y Tratamiento -->
            <div class='custom-row'>
                <div class='custom-col custom-col-2'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-stethoscope me-1'></i>Diagnóstico</span>
                        <span class='field-value'>" . ($this->results['diagnostico'] ?: 'Pendiente de evaluación') . "</span>
                        " . $generateHistory('diagnostico') . "
                    </div>
                </div>
                <div class='custom-col custom-col-2'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-prescription me-1'></i>Tratamiento Recomendado</span>
                        <span class='field-value'>" . ($this->results['tratamiento_recomendado'] ?: 'Por definir') . "</span>
                        " . $generateHistory('tratamiento_recomendado') . "
                    </div>
                </div>
            </div>

            <!-- Odontograma -->
            <div class='custom-row'>
                <div class='custom-col'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-chart-line me-1'></i>Odontograma</span>
                        <span class='field-value'>" . ($this->results['odontograma'] ?: 'Sin registro odontográfico') . "</span>
                        " . $generateHistory('odontograma') . "
                    </div>
                </div>
            </div>
        </div>
    </div>";

    //echo "test"; exit;

// Solo mostrar información médica básica si hay datos
if ($this->results['peso_kg'] || $this->results['estatura_cm'] || $this->results['temperatura_celsius'] || $this->results['alergias'] || $this->results['medicamentos_actuales']) {

    $this->page_content .= "
    <!-- INFORMACIÓN MÉDICA BÁSICA -->
    <div class='section-card'>
        <div class='section-header section-medical'>
            <i class='fas fa-heartbeat me-2'></i>Información Médica Básica
        </div>
        <div class='section-body'>

            <!-- Signos Vitales -->
            <div class='custom-row'>

                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-weight me-1'></i>Peso (Kg)</span>
                        <span class='field-value'>" . ($this->results['peso_kg'] ?: 'Sin registro') . "</span>
                        " . $generateHistory('peso_kg') . "
                    </div>
                </div>

                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-ruler-vertical me-1'></i>Estatura (m)</span>
                        <span class='field-value'>" . ($this->results['estatura_cm'] ?: 'Sin registro') . "</span>
                        " . $generateHistory('estatura_cm') . "
                    </div>
                </div>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-thermometer-half me-1'></i>Temperatura (°C)</span>
                        <span class='field-value'>" . ($this->results['temperatura_celsius'] ?: 'Sin registro') . "</span>
                        " . $generateHistory('temperatura_celsius') . "
                    </div>
                </div>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-heart me-1'></i>Frecuencia Cardíaca</span>
                        <span class='field-value'>" . ($this->results['frecuencia_cardiaca'] ?: 'Sin registro') . "</span>
                        " . $generateHistory('frecuencia_cardiaca') . "
                    </div>
                </div>
            </div>

            <!-- Presión y Glucosa -->
            <div class='custom-row'>
                <div class='custom-col custom-col-2'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-tint me-1'></i>Presión Arterial (mmHg)</span>
                        <span class='field-value'>" . (($this->results['presion_sistolica'] && $this->results['presion_diastolica']) ? $this->results['presion_sistolica'] . ' / ' . $this->results['presion_diastolica'] : 'Sin registro') . "</span>
                        " . $generateHistory('presion_sistolica', function($value, $record) { 
                            return ($record['presion_sistolica'] && $record['presion_diastolica']) ? 
                                $record['presion_sistolica'] . '/' . $record['presion_diastolica'] : '--'; 
                        }) . "
                    </div>
                </div>
                <div class='custom-col custom-col-2'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-vial me-1'></i>Glucosa (mg/dl)</span>
                        <span class='field-value'>" . ($this->results['glucosa_mg_dl'] ?: 'Sin registro') . "</span>
                        " . $generateHistory('glucosa_mg_dl') . "
                    </div>
                </div>
            </div>

            <!-- Antecedentes Médicos -->
            <div class='custom-row'>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-exclamation-triangle me-1'></i>Alergias</span>
                        <span class='field-value'>" . ($this->results['alergias'] ?: 'Sin alergias conocidas') . "</span>
                        " . $generateHistory('alergias') . "
                    </div>
                </div>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-pills me-1'></i>Medicamentos Actuales</span>
                        <span class='field-value'>" . ($this->results['medicamentos_actuales'] ?: 'Sin medicamentos') . "</span>
                        " . $generateHistory('medicamentos_actuales') . "
                    </div>
                </div>
                <div class='custom-col custom-col-3'>
                    <div class='info-field'>
                        <span class='field-label'><i class='fas fa-users me-1'></i>Antecedentes Familiares</span>
                        <span class='field-value'>" . ($this->results['antecedentes_familiares'] ?: 'Sin antecedentes') . "</span>
                        " . $generateHistory('antecedentes_familiares') . "
                    </div>
                </div>
            </div>
        </div>
    </div>";
}

$this->page_content .= "
    <!-- NOTAS GENERALES -->
    <div class='notes-section'>
        <div class='notes-title'>
            <i class='fas fa-sticky-note me-2'></i>Notas Generales de la Consulta
        </div>
        <div>" . ($this->results['clinical_notes'] ?: 'Sin observaciones adicionales registradas.') . "</div>
    </div>

    <!-- INFORMACIÓN DEL EXPEDIENTE EN FOOTER CENTRADO -->
    <div class='footer-info'>
        <div style='text-align: center; margin-bottom: 10px;'>
            <strong><i class='fas fa-hospital me-1'></i>Sistema:</strong> DENTABLIX &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong><i class='fas fa-shield-alt me-1'></i>Confidencialidad:</strong> Expediente Médico &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong><i class='fas fa-lock me-1'></i>Estado:</strong> " . ucfirst($this->results['clinical_status']) . "
        </div>
        <div style='text-align: center; font-size: 11px; color: #6c757d;'>
            <em>Este documento es confidencial y contiene información médica protegida</em>
        </div>
    </div>
</body>
</html>";

        //echo "generate"; exit;
        return $this;
    }

    







    // Generar y enviar PDF
    public function getExpedientePdfPath() {
        //
        $clinical_records_files_path = PATH_STORAGE.DS.'clinical_records';
        // Crear directorio si no existe
        if (!is_dir($clinical_records_files_path)) {
            mkdir($clinical_records_files_path, 0755, true);
        }
        //
        $file_name = "exp-{$this->customer_id}";
        if ($this->customer_person_id) {
            $file_name = "exp-{$this->customer_id}-{$this->customer_person_id}";
        }
        //
        return $clinical_records_files_path.DS.$file_name.'.pdf';
    }






    // generar pdf
    public function saveExpedientePdf() {
        //echo $this->page_content; exit;

        //
        $pdf = Helper::wkhtmlToPdf();
        $pdf->addPage($this->page_content);

        /**
         * Siempre guardamos con nombre técnico
         */
        //
        $pdf_file_path = $this->getExpedientePdfPath();
        //echo "save on pdf path: $pdf_file_path"; exit;
        //
        if (!$pdf->saveAs($pdf_file_path)) {
            $error = $pdf->getError();
            echo $error; exit;
        }
        //
        return true;
    }






    //
    public function createClinicalRecordFileName() {
        $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $this->results['customer_name']);
        $safe_name = preg_replace('/\s+/', '_', $safe_name); // Reemplaza uno o más espacios con un solo underscore
        return "Expediente_Clinico_{$safe_name}_" . date('Y-m-d_H-i') . ".pdf";        
    }




    //
    public function downloadExpedientePdf($is_preview = false) {

        //
        $pdf_file_path = $this->getExpedientePdfPath();
        //echo "download/preview on pdf path: $pdf_file_path"; exit;
        
        //
        $filename = $this->createClinicalRecordFileName();
        //echo "pdf file name: $filename"; exit;

        // Decidir qué hacer con el archivo
        if ($is_preview){

            //
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($pdf_file_path));
            readfile($pdf_file_path);

        } else {

            //
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($pdf_file_path));
            readfile($pdf_file_path);

        }

        exit;
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