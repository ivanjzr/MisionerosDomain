<?php
namespace Controllers\Pos;

use App\App;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;

use App\Empleados\Empleados;
use App\Empleados\EmpleadosSucursalesPermisos;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;



class PosSalesController extends BaseController
{
    
    
    
    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/pos_sales/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }



    // 
    public function PaginateRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];




        //
        $table_name = "v_sales";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));


        $results = [];
        $results['data'] = [];

        //
        $search_value = trim($request->getQueryParam("search")['value']);
        //
        $pos_id = $request->getQueryParam("pid");
        $pos_register_id = $request->getQueryParam("rid");
        $created_user_id = $request->getQueryParam("uid");

        // dates
        $start_date = $request->getQueryParam("sd");
        $end_date = $request->getQueryParam("ed");
        //echo "$start_date, $end_date"; exit;
        



        /**
         * 
         * Con solo establecer la variable results asi:
         * $results = [];
         * $results['data'] = [];
         * es suficiente para no mostrar errores de length en el DataTables
         * ademas al establecer la variable error dentro del objeto asi:
         * $results['error'] = "error message";
         * permite mostrar el mensaje de error en DataTables sin hacer nada adicional en el codigo de datatables
         */
        if ( !(\DateTime::createFromFormat("Y-m-d", $start_date) && \DateTime::createFromFormat("Y-m-d", $end_date)) ){
            $results['error'] = "no se encontro fecha inicio/fin de consulta";
            return $response->withJson($results, 200);
        }
        //
        $str_where_date = " AND ( 
            CAST(opened_datetime AS DATE) >= '$start_date' AND CAST(opened_datetime AS DATE) <= '$end_date'
        )";
        //echo $str_where_date; exit;




        //
        $search_clause = "";
        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.pos_name like '%$search_value%' )
                    )";
        }
        
        //
        if ( is_numeric($pos_id) ){
            $search_clause .= " And t.pos_id = $pos_id ";
        }

        //
        if ( is_numeric($pos_register_id) ){
            $search_clause .= " And t.pos_register_id = $pos_register_id ";
        }

        //
        if ( is_numeric($created_user_id) ){
            $search_clause .= " And t.created_user_id = $created_user_id ";
        }
        //echo "$str_where_date $search_clause "; exit;


        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        
        


        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "deb" => true,
                "count_stmt" => function() use ($table_name, $str_where_date, $search_clause){
                    //
                    return "Select 
                            
                            COUNT(*) total,
                            COALESCE(SUM(t.grand_total), 0) AS sum_grand_total,
                            COALESCE(SUM(t.total_comissions), 0) AS sum_total_comissions,
                            COALESCE(SUM(t.total_paid_efectivo), 0) AS sum_total_paid_efectivo,
                            COALESCE(SUM(t.total_paid_tarjeta), 0) AS sum_total_paid_tarjeta,
                            COALESCE(SUM(t.total_paid_usd_amount), 0) AS sum_total_paid_usd_amount,
                            COALESCE(SUM(t.change_amount), 0) AS sum_change_amount
                            
                            From {$table_name} t Where t.app_id = ? {$str_where_date} {$search_clause}";
                },
                //
                "cnt_params" => array(
                    $app_id,
                ),

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $str_where_date, $search_clause){
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
                                      
                                            Where t.app_id = ?

                                            {$str_where_date}                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                //
                "params" => array(
                    $app_id
                ),

                //
                "parseRows" => function(&$row) use($app_id){
                    //dd($row); exit;
                }

            ]
        );

        
        //dd($results); exit;
        return $response->withJson($results, 200);
    }




    
    
    
    



    
    
    

    // Función auxiliar para agregar headers de ventas
    private function addSalesHeaders($sheet, $row_number) {
        $sheet->SetCellValue('A' . $row_number, 'VENTA #');
        $sheet->SetCellValue('B' . $row_number, 'FECHA/HORA');
        $sheet->SetCellValue('C' . $row_number, 'USUARIO');
        $sheet->SetCellValue('D' . $row_number, 'POS');
        $sheet->SetCellValue('E' . $row_number, 'CLIENTE');
        $sheet->SetCellValue('F' . $row_number, 'SUBTOTAL');
        $sheet->SetCellValue('G' . $row_number, 'DESCUENTO');
        $sheet->SetCellValue('H' . $row_number, 'TOTAL');
        $sheet->SetCellValue('I' . $row_number, 'PAGADO');
        $sheet->SetCellValue('J' . $row_number, 'CAMBIO');
        $sheet->SetCellValue('K' . $row_number, 'MÉTODO PAGO');

        $sheet->getStyle('A' . $row_number . ':K' . $row_number)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row_number . ':K' . $row_number)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => "fffed0",
                ]
            ]
        ]);
    }

    // Función auxiliar para agregar una fila de venta
    private function addSaleRow($sheet, $row_number, $sale) {
        $fecha_venta = $sale['datetime_created']->format('d/m H:i');
        $cliente = !empty($sale['customer_name']) ? $sale['customer_name'] : '-';
        
        // Determinar método de pago
        $metodo_pago = [];
        if (floatval($sale['total_paid_efectivo']) > 0) $metodo_pago[] = 'Efectivo';
        if (floatval($sale['total_paid_tarjeta']) > 0) $metodo_pago[] = 'Tarjeta';
        if (floatval($sale['total_paid_usd_amount']) > 0) $metodo_pago[] = 'USD';
        $metodo_pago_str = !empty($metodo_pago) ? implode('+', $metodo_pago) : $sale['payment_method_type'];

        $sheet->SetCellValue('A' . $row_number, $sale['id']);
        $sheet->getStyle('A' . $row_number)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->SetCellValue('B' . $row_number, $fecha_venta);
        $sheet->SetCellValue('C' . $row_number, $sale['created_user_name']);
        $sheet->SetCellValue('D' . $row_number, $sale['pos_name']);
        $sheet->SetCellValue('E' . $row_number, $cliente);
        $sheet->SetCellValue('F' . $row_number, '$' . number_format($sale['sub_total'], 2));
        $sheet->SetCellValue('G' . $row_number, '$' . number_format($sale['discount_amount'], 2));
        $sheet->SetCellValue('H' . $row_number, '$' . number_format($sale['grand_total'], 2));
        $sheet->SetCellValue('I' . $row_number, '$' . number_format($sale['total_paid'], 2));
        $sheet->SetCellValue('J' . $row_number, '$' . number_format($sale['change_amount'], 2));
        $sheet->SetCellValue('K' . $row_number, $metodo_pago_str);
    }





 
    //
    public function GetSalesReportXls($request, $response, $args) {
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $sucursal_name = $ses_data['sucursal'];
        $user_id = $ses_data['id'];

        //
        $pos_id = $request->getQueryParam("pid");
        $pos_register_id = $request->getQueryParam("rid");
        $created_user_id = $request->getQueryParam("uid");
        
        
        // parámetros para el reporte
        $grouped_by_users = $request->getQueryParam("g", false);
        $show_details = $request->getQueryParam("d", false);
        

        // dates
        $start_date = $request->getQueryParam("sd");
        $end_date = $request->getQueryParam("ed");

        //
        if ( !(\DateTime::createFromFormat("Y-m-d", $start_date) && \DateTime::createFromFormat("Y-m-d", $end_date)) ){
            echo "fecha inicio fin invalida"; exit;
        }

        //
        $search_clause = "";
        
        //
        if ( is_numeric($pos_id) ){
            $search_clause .= " And t.pos_id = $pos_id ";
        }

        //
        if ( is_numeric($pos_register_id) ){
            $search_clause .= " And t.pos_register_id = $pos_register_id ";
        }

        //
        if ( is_numeric($created_user_id) ){
            $search_clause .= " And t.created_user_id = $created_user_id ";
        }

        //
        $str_where_date = " AND ( 
            CAST(t.datetime_created AS DATE) >= '$start_date' AND CAST(t.datetime_created AS DATE) <= '$end_date'
        )";

        // Filename
        $filename = "Reporte-Ventas-" . date('Y-m-d-H-i-s') . ".xlsx";

        // Crear Excel
        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        ini_set("memory_limit","512M");
        ini_set("max_execution_time","300");

        //
        $stmt = "
            Select 
                t.*
            from v_sales t
            Where t.app_id = ? 
            And t.sucursal_id = ? 
            {$str_where_date}
            {$search_clause}
            ORDER BY t.datetime_created ASC
            ";
        $arr_sales = Query::Multiple($stmt, [$app_id, $sucursal_id]);

        if (empty($arr_sales)) {
            echo "No se encontraron ventas en el periodo seleccionado"; exit;
        }

        // TÍTULO PRINCIPAL
        $sheet->SetCellValue('G1', 'REPORTE DE VENTAS ' . strtoupper($sucursal_name));
        $sheet->getStyle('G1')->getFont()->setBold(true);
        $sheet->getStyle('G1')->getFont()->setSize(16);

        // FECHAS - Solo mostrar rango si son diferentes
        $fecha_row = 3;
        if ($start_date == $end_date) {
            $fecha_texto = "Del " . date('d/m/Y', strtotime($start_date));
        } else {
            $fecha_inicio = date('d/m/Y', strtotime($start_date));
            $fecha_fin = date('d/m/Y', strtotime($end_date));
            $fecha_texto = "Del $fecha_inicio al $fecha_fin";
        }
        $sheet->SetCellValue('G' . $fecha_row, $fecha_texto);
        $sheet->getStyle('G' . $fecha_row)->getFont()->setBold(true);

        $row_number = $fecha_row + 2;

        // Calcular totales generales
        $total_ventas = 0;
        $total_efectivo = 0;
        $total_tarjeta = 0;
        $total_usd = 0;
        $total_cambio = 0;
        $total_descuentos = 0;
        $total_impuestos = 0;
        $total_transacciones = count($arr_sales);

        foreach($arr_sales as $sale) {
            $total_ventas += floatval($sale['grand_total']);
            $total_efectivo += floatval($sale['total_paid_efectivo']);
            $total_tarjeta += floatval($sale['total_paid_tarjeta']);
            $total_usd += floatval($sale['total_paid_usd_amount']);
            $total_cambio += floatval($sale['change_amount']);
            $total_descuentos += floatval($sale['discount_amount']);
            $total_impuestos += floatval($sale['tax_amount']);
        }

        // CASO 3: Solo totales (sin detalle)
        if (!$show_details) {
            $sheet->SetCellValue('G' . $row_number, 'RESUMEN EJECUTIVO DE VENTAS');
            $sheet->getStyle('G' . $row_number)->getFont()->setBold(true);
            $sheet->getStyle('G' . $row_number)->getFont()->setSize(14);
            $row_number += 2;

            $sheet->SetCellValue('A' . $row_number, 'TOTAL DE VENTAS:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_ventas, 2));
            $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
            $sheet->getStyle('C' . $row_number)->getFont()->setBold(true);
            $row_number++;

            $sheet->SetCellValue('A' . $row_number, 'CANTIDAD DE TRANSACCIONES:');
            $sheet->SetCellValue('C' . $row_number, $total_transacciones);
            $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
            $row_number++;

            $ticket_promedio = $total_transacciones > 0 ? $total_ventas / $total_transacciones : 0;
            $sheet->SetCellValue('A' . $row_number, 'TICKET PROMEDIO:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($ticket_promedio, 2));
            $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
            $row_number += 2;

            $sheet->SetCellValue('A' . $row_number, 'FORMAS DE PAGO:');
            $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
            $row_number++;

            $pct_efectivo = $total_ventas > 0 ? ($total_efectivo / $total_ventas) * 100 : 0;
            $sheet->SetCellValue('A' . $row_number, '- Efectivo:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_efectivo, 2) . ' (' . number_format($pct_efectivo, 1) . '%)');
            $row_number++;

            $pct_tarjeta = $total_ventas > 0 ? ($total_tarjeta / $total_ventas) * 100 : 0;
            $sheet->SetCellValue('A' . $row_number, '- Tarjeta:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_tarjeta, 2) . ' (' . number_format($pct_tarjeta, 1) . '%)');
            $row_number++;

            $pct_usd = $total_ventas > 0 ? ($total_usd / $total_ventas) * 100 : 0;
            $sheet->SetCellValue('A' . $row_number, '- Dólares (MXN):');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_usd, 2) . ' (' . number_format($pct_usd, 1) . '%)');
            $row_number += 2;

            $sheet->SetCellValue('A' . $row_number, 'OTROS DATOS:');
            $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
            $row_number++;

            $sheet->SetCellValue('A' . $row_number, '- Total Descuentos:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_descuentos, 2));
            $row_number++;

            $sheet->SetCellValue('A' . $row_number, '- Total Impuestos:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_impuestos, 2));
            $row_number++;

            $sheet->SetCellValue('A' . $row_number, '- Total Cambio Entregado:');
            $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_cambio, 2));
            $row_number += 2;

            // Usuarios que vendieron
            $users_sales = [];
            foreach($arr_sales as $sale) {
                $user_name = $sale['created_user_name'];
                if (!isset($users_sales[$user_name])) {
                    $users_sales[$user_name] = ['count' => 0, 'total' => 0];
                }
                $users_sales[$user_name]['count']++;
                $users_sales[$user_name]['total'] += floatval($sale['grand_total']);
            }

            $sheet->SetCellValue('A' . $row_number, 'USUARIOS QUE VENDIERON:');
            $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
            $row_number++;

            foreach($users_sales as $user_name => $user_data) {
                $sheet->SetCellValue('A' . $row_number, "- $user_name:");
                $sheet->SetCellValue('C' . $row_number, $user_data['count'] . ' ventas ($' . number_format($user_data['total'], 2) . ')');
                $row_number++;
            }

        } else {
            // CASO 1 y 2: Con detalle de ventas
            
            if ($grouped_by_users) {
                // CASO 2: Agrupado por usuarios
                $sales_by_user = [];
                foreach($arr_sales as $sale) {
                    $user_name = $sale['created_user_name'];
                    if (!isset($sales_by_user[$user_name])) {
                        $sales_by_user[$user_name] = [];
                    }
                    $sales_by_user[$user_name][] = $sale;
                }

                foreach($sales_by_user as $user_name => $user_sales) {
                    // Header del usuario
                    $sheet->SetCellValue('A' . $row_number, 'USUARIO: ' . strtoupper($user_name));
                    $sheet->getStyle('A' . $row_number . ':K' . $row_number)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $row_number . ':K' . $row_number)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => "E6F3FF",
                            ]
                        ]
                    ]);
                    $row_number++;

                    // Headers de columnas
                    $this->addSalesHeaders($sheet, $row_number);
                    $row_number++;

                    // Datos del usuario
                    $user_total_ventas = 0;
                    $user_total_efectivo = 0;
                    $user_total_tarjeta = 0;
                    $user_total_usd = 0;
                    $user_total_cambio = 0;
                    $user_count = 0;

                    foreach($user_sales as $sale) {
                        $this->addSaleRow($sheet, $row_number, $sale);
                        
                        $user_total_ventas += floatval($sale['grand_total']);
                        $user_total_efectivo += floatval($sale['total_paid_efectivo']);
                        $user_total_tarjeta += floatval($sale['total_paid_tarjeta']);
                        $user_total_usd += floatval($sale['total_paid_usd_amount']);
                        $user_total_cambio += floatval($sale['change_amount']);
                        $user_count++;
                        
                        $row_number++;
                    }

                    // Subtotal del usuario
                    $row_number++;
                    $sheet->SetCellValue('A' . $row_number, 'SUBTOTAL ' . strtoupper($user_name) . ' (' . $user_count . ' ventas):');
                    $sheet->SetCellValue('H' . $row_number, '$' . number_format($user_total_ventas, 2));
                    $sheet->SetCellValue('I' . $row_number, 'Efvo: $' . number_format($user_total_efectivo, 2));
                    $sheet->SetCellValue('J' . $row_number, 'Tarj: $' . number_format($user_total_tarjeta, 2));
                    $sheet->SetCellValue('K' . $row_number, 'USD: $' . number_format($user_total_usd, 2));

                    $sheet->getStyle('A' . $row_number . ':K' . $row_number)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $row_number . ':K' . $row_number)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => "E6FFE6",
                            ]
                        ]
                    ]);
                    $row_number += 2;
                }

            } else {
                // CASO 1: Registros corridos (no agrupados)
                
                // Headers de columnas
                $this->addSalesHeaders($sheet, $row_number);
                $row_number++;

                // Datos de ventas
                foreach($arr_sales as $sale) {
                    $this->addSaleRow($sheet, $row_number, $sale);
                    $row_number++;
                }
            }

            // TOTALES GENERALES
            $row_number += 2;
            $sheet->SetCellValue('A' . $row_number, 'TOTALES GENERALES:');
            $sheet->SetCellValue('H' . $row_number, '$' . number_format($total_ventas, 2));
            $sheet->SetCellValue('I' . $row_number, 'Efvo: $' . number_format($total_efectivo, 2));
            $sheet->SetCellValue('J' . $row_number, 'Tarj: $' . number_format($total_tarjeta, 2));
            $sheet->SetCellValue('K' . $row_number, 'USD: $' . number_format($total_usd, 2));

            $sheet->getStyle('A' . $row_number . ':K' . $row_number)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_number . ':K' . $row_number)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => "CCE5FF",
                    ]
                ]
            ]);
        }

        // AUTO WIDTH COLUMNS
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Headers para descarga directa
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Crear writer y enviar directamente al navegador
        $writer = new Xlsx($objPHPExcel);
        $writer->save('php://output');
        exit;
    }










    //
public function GetComissionsReportXls($request, $response, $args) {
    //
    $ses_data = $request->getAttribute("ses_data");
    $app_id = $ses_data['app_id'];
    $sucursal_id = $ses_data['sucursal_id'];
    $sucursal_name = $ses_data['sucursal'];
    $user_id = $ses_data['id'];

    // parámetros para el reporte
    $grouped_by_employees = $request->getQueryParam("g", false);
    $show_details = $request->getQueryParam("d", false);

    // dates
    $start_date = $request->getQueryParam("sd");
    $end_date = $request->getQueryParam("ed");

    //
    if ( !(\DateTime::createFromFormat("Y-m-d", $start_date) && \DateTime::createFromFormat("Y-m-d", $end_date)) ){
        echo "fecha inicio fin invalida"; exit;
    }

    //
    $search_clause = "";
    //
    $str_where_date = " AND ( 
        CAST(t.datetime_created AS DATE) >= '$start_date' AND CAST(t.datetime_created AS DATE) <= '$end_date'
    )";

    // Filename
    $filename = "Reporte-Comisiones-" . date('Y-m-d-H-i-s') . ".xlsx";

    // Crear Excel
    $objPHPExcel = new Spreadsheet();
    $objPHPExcel->setActiveSheetIndex(0);
    $sheet = $objPHPExcel->getActiveSheet();

    ini_set("memory_limit","512M");
    ini_set("max_execution_time","300");

    //
    $stmt = "
        Select 
            t.*,
            ts.customer_name,
            ts.sale_code
        from sales_items t
            Left Join v_sales ts On ts.app_id = t.app_id And ts.id = t.sale_id
        Where t.app_id = ? 
        And ts.sucursal_id = ? 
        {$str_where_date}
        {$search_clause}
        ORDER BY t.datetime_created ASC
        ";
    $arr_sales_items = Query::Multiple($stmt, [$app_id, $sucursal_id]);

    if (empty($arr_sales_items)) {
        echo "No se encontraron ventas en el periodo seleccionado"; exit;
    }

    // TÍTULO PRINCIPAL 
    $sheet->SetCellValue('G1', 'REPORTE DE COMISIONES ' . strtoupper($sucursal_name));
    $sheet->getStyle('G1')->getFont()->setBold(true);
    $sheet->getStyle('G1')->getFont()->setSize(16);

    // FECHAS - Solo mostrar rango si son diferentes
    $fecha_row = 3;
    if ($start_date == $end_date) {
        $fecha_texto = "Del " . date('d/m/Y', strtotime($start_date));
    } else {
        $fecha_inicio = date('d/m/Y', strtotime($start_date));
        $fecha_fin = date('d/m/Y', strtotime($end_date));
        $fecha_texto = "Del $fecha_inicio al $fecha_fin";
    }
    $sheet->SetCellValue('G' . $fecha_row, $fecha_texto);
    $sheet->getStyle('G' . $fecha_row)->getFont()->setBold(true);

    $row_number = $fecha_row + 2;

    // Calcular totales generales
    $total_ventas = 0;
    $total_comisiones = 0;
    $total_items = count($arr_sales_items);

    foreach($arr_sales_items as $item) {
        $total_ventas += floatval($item['final_price']);
        $total_comisiones += floatval($item['employee_commission_amount']);
    }

    // CASO 1: Solo totales (sin detalle)
    if (!$show_details) {
        $sheet->SetCellValue('G' . $row_number, 'RESUMEN EJECUTIVO DE COMISIONES');
        $sheet->getStyle('G' . $row_number)->getFont()->setBold(true);
        $sheet->getStyle('G' . $row_number)->getFont()->setSize(14);
        $row_number += 2;

        $sheet->SetCellValue('A' . $row_number, 'TOTAL DE VENTAS:');
        $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_ventas, 2));
        $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row_number)->getFont()->setBold(true);
        $row_number++;

        $sheet->SetCellValue('A' . $row_number, 'TOTAL DE COMISIONES:');
        $sheet->SetCellValue('C' . $row_number, '$' . number_format($total_comisiones, 2));
        $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row_number)->getFont()->setBold(true);
        $row_number++;

        $sheet->SetCellValue('A' . $row_number, 'CANTIDAD DE ITEMS:');
        $sheet->SetCellValue('C' . $row_number, $total_items);
        $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
        $row_number += 2;

        // Empleados con comisiones
        $employees_commissions = [];
        foreach($arr_sales_items as $item) {
            if ($item['employee_id'] && $item['employee_info']) {
                $employee_key = $item['employee_id'] . '_' . $item['employee_info'];
                if (!isset($employees_commissions[$employee_key])) {
                    $employees_commissions[$employee_key] = [
                        'employee_id' => $item['employee_id'],
                        'employee_info' => $item['employee_info'],
                        'commission_rate' => $item['employee_commission_rate'],
                        'items_count' => 0,
                        'total_ventas' => 0,
                        'total_comisiones' => 0
                    ];
                }
                $employees_commissions[$employee_key]['items_count']++;
                $employees_commissions[$employee_key]['total_ventas'] += floatval($item['final_price']);
                $employees_commissions[$employee_key]['total_comisiones'] += floatval($item['employee_commission_amount']);
            }
        }

        $sheet->SetCellValue('A' . $row_number, 'EMPLEADOS CON COMISIONES:');
        $sheet->getStyle('A' . $row_number)->getFont()->setBold(true);
        $row_number++;

        foreach($employees_commissions as $emp_data) {
            $sheet->SetCellValue('A' . $row_number, "- {$emp_data['employee_info']} ({$emp_data['commission_rate']}%):");
            $sheet->SetCellValue('C' . $row_number, $emp_data['items_count'] . ' items - Ventas: $' . number_format($emp_data['total_ventas'], 2) . ' - Comisiones: $' . number_format($emp_data['total_comisiones'], 2));
            $row_number++;
        }

    } else {
        // CASO 2: Con detalle de comisiones

        
        
        if ($grouped_by_employees) {
            // Agrupado por empleados
            $items_by_employee = [];
            foreach($arr_sales_items as $item) {
                if ($item['employee_id'] && $item['employee_info']) {
                    $employee_key = $item['employee_id'] . '_' . $item['employee_info'];
                    if (!isset($items_by_employee[$employee_key])) {
                        $items_by_employee[$employee_key] = [];
                    }
                    $items_by_employee[$employee_key][] = $item;
                }
            }

            foreach($items_by_employee as $employee_key => $employee_items) {
                $first_item = $employee_items[0];
                
                // Header del empleado
                $sheet->SetCellValue('A' . $row_number, 'EMPLEADO: ' . strtoupper($first_item['employee_info']) . ' - COMISIÓN: ' . $first_item['employee_commission_rate'] . '%');
                $sheet->getStyle('A' . $row_number . ':J' . $row_number)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_number . ':J' . $row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "E6F3FF",
                        ]
                    ]
                ]);
                $row_number++;

                // Headers de columnas
                $this->addCommissionHeaders($sheet, $row_number);
                $row_number++;

                // Datos del empleado
                $emp_total_ventas = 0;
                $emp_total_comisiones = 0;
                $emp_items_count = 0;

                foreach($employee_items as $item) {
                    $this->addCommissionRow($sheet, $row_number, $item);
                    
                    $emp_total_ventas += floatval($item['final_price']);
                    $emp_total_comisiones += floatval($item['employee_commission_amount']);
                    $emp_items_count++;
                    
                    $row_number++;
                }

                // Subtotal del empleado
                $row_number++;
                $sheet->SetCellValue('A' . $row_number, 'SUBTOTAL ' . strtoupper($first_item['employee_info']) . ' (' . $emp_items_count . ' items):');
                $sheet->SetCellValue('H' . $row_number, '$' . number_format($emp_total_ventas, 2));
                $sheet->SetCellValue('I' . $row_number, '$' . number_format($emp_total_comisiones, 2));

                $sheet->getStyle('A' . $row_number . ':J' . $row_number)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_number . ':J' . $row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "E6FFE6",
                        ]
                    ]
                ]);
                $row_number += 2;
            }

        } else {
            // Registros corridos (no agrupados)
            
            // Headers de columnas
            $this->addCommissionHeaders($sheet, $row_number);
            $row_number++;

            // Datos de items
            foreach($arr_sales_items as $item) {
                $this->addCommissionRow($sheet, $row_number, $item);
                $row_number++;
            }
        }

        

        // TOTALES GENERALES
        $row_number += 2;
        $sheet->SetCellValue('A' . $row_number, 'TOTALES GENERALES:');
        $sheet->SetCellValue('H' . $row_number, '$' . number_format($total_ventas, 2));
        $sheet->SetCellValue('I' . $row_number, '$' . number_format($total_comisiones, 2));

        $sheet->getStyle('A' . $row_number . ':J' . $row_number)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row_number . ':J' . $row_number)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => "CCE5FF",
                ]
            ]
        ]);
    }

    // AUTO WIDTH COLUMNS
    foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }

    // Headers para descarga directa
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Crear writer y enviar directamente al navegador
    $writer = new Xlsx($objPHPExcel);
    $writer->save('php://output');
    exit;
}

// Función para agregar headers de comisiones
private function addCommissionHeaders($sheet, $row_number) {
    $sheet->SetCellValue('A' . $row_number, 'FECHA');
    $sheet->SetCellValue('B' . $row_number, 'VENTA #');
    $sheet->SetCellValue('C' . $row_number, 'CLIENTE');
    $sheet->SetCellValue('D' . $row_number, 'PRODUCTO');
    $sheet->SetCellValue('E' . $row_number, 'CANTIDAD');
    $sheet->SetCellValue('F' . $row_number, 'PRECIO');
    $sheet->SetCellValue('G' . $row_number, 'EMPLEADO');
    $sheet->SetCellValue('H' . $row_number, 'TOTAL VENTA');
    $sheet->SetCellValue('I' . $row_number, 'COMISIÓN');
    $sheet->SetCellValue('J' . $row_number, 'TASA %');

    $sheet->getStyle('A' . $row_number . ':J' . $row_number)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row_number . ':J' . $row_number)->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => "D9D9D9",
            ]
        ]
    ]);
}

// Función para agregar fila de comisión
private function addCommissionRow($sheet, $row_number, $item) {
    
    $fecha_formateada = $item['datetime_created']->format('d/m/Y H:i');
    //echo "test 1"; exit;

    $sheet->SetCellValue('A' . $row_number, $fecha_formateada);
    $sheet->SetCellValue('B' . $row_number, $item['sale_id']);
    $sheet->SetCellValue('C' . $row_number, $item['customer_name'] ?: '--');
    $sheet->SetCellValue('D' . $row_number, $item['item_info']);
    $sheet->SetCellValue('E' . $row_number, $item['qty']);
    $sheet->SetCellValue('F' . $row_number, '$' . number_format($item['price'], 2));
    $sheet->SetCellValue('G' . $row_number, $item['employee_info'] ?: '--');
    $sheet->SetCellValue('H' . $row_number, '$' . number_format($item['final_price'], 2));
    $sheet->SetCellValue('I' . $row_number, '$' . number_format($item['employee_commission_amount'], 2));
    $sheet->SetCellValue('J' . $row_number, $item['employee_commission_rate'] . '%');
}




}