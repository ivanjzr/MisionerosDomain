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



class PosRegistersController extends BaseController
{
    
    
    
    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/pos_registers/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }



    //
    public function ViewCaja($request, $response, $args) {
        //
        $view_data = [
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['id']
        ];
        //
        return $this->container->php_view->render($response, 'admin/pos_registers/view.phtml', $view_data);
    }





    
    //
    public function GetPosRegistersReportXls($request, $response, $args) {
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $sucursal_name = $ses_data['sucursal'];
        $user_id = $ses_data['id'];

        //
        $filter_pos_id = $request->getQueryParam("pid");
        $start_opened_datetime = $request->getQueryParam("sd");
        $end_opened_datetime = $request->getQueryParam("ed");

        if (!($start_opened_datetime && $end_opened_datetime)) {
            return;
        }

        // Filename
        $filename = "Reporte-Ventas-" . date('Y-m-d-H-i-s') . ".xlsx";

        // Crear Excel
        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        ini_set("memory_limit","512M");
        ini_set("max_execution_time","300");

        //
        if (is_numeric($filter_pos_id)) {
            $arr_pos = Query::Multiple("Select * from v_pos Where app_id = ? And sucursal_id = ? And id = ?", [$app_id, $sucursal_id, $filter_pos_id]);            
        } else {
            $arr_pos = Query::Multiple("Select * from v_pos Where app_id = ? And sucursal_id = ?", [$app_id, $sucursal_id]);            
        }

        $search_clause = " AND ( 
            CAST(opened_datetime AS DATE) >= '$start_opened_datetime' AND CAST(opened_datetime AS DATE) <= '$end_opened_datetime'
        ) ";

        // TÍTULO PRINCIPAL
        $sheet->SetCellValue('G1', 'REPORTE DE VENTAS ' . strtoupper($sucursal_name));
        $sheet->getStyle('G1')->getFont()->setBold(true);
        $sheet->getStyle('G1')->getFont()->setSize(16); 

        // Si es punto de venta específico, agregar nombre del POS
        if (is_numeric($filter_pos_id) && count($arr_pos) > 0) {
            $sheet->SetCellValue('G2', strtoupper($arr_pos[0]['name']));
            $sheet->getStyle('G2')->getFont()->setBold(true);
            $sheet->getStyle('G2')->getFont()->setSize(14);
            $fecha_row = 4;
        } else {
            $fecha_row = 3;
        }

        // FECHAS
        $fecha_inicio = date('d/m/Y', strtotime($start_opened_datetime));
        $fecha_fin = date('d/m/Y', strtotime($end_opened_datetime));
        $sheet->SetCellValue('G' . $fecha_row, "Del $fecha_inicio al $fecha_fin");
        $sheet->getStyle('G' . $fecha_row)->getFont()->setBold(true);

        $row_number = $fecha_row + 2;
        
        // Variables para totales generales
        $total_general_ventas = 0;
        $total_general_efectivo = 0;
        $total_general_tarjetas = 0;
        $total_general_dolares = 0;
        $total_general_saldo_final = 0;
        $total_general_saldo_final_usd = 0;
        $total_general_diferencias = 0;
        $total_general_diferencias_usd = 0;
        $total_cajas_count = 0;

        /**
         * Iteramos en los puntos de venta
         */
        foreach($arr_pos as $pos_item) {
            $pos_id = $pos_item['id'];
            
            // Solo mostrar header de POS si hay múltiples puntos de venta
            if (count($arr_pos) > 1) {
                $sheet->SetCellValue('A' . $row_number, 'PUNTO DE VENTA: ' .strtoupper($pos_item['name']));
                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "E6F3FF",
                        ]
                    ]
                ]);
                $row_number++;
            }

            /**
             * Obtenemos las cajas del punto de venta
             */
            $arr_registers = Query::Multiple("Select * from v_pos_register_report Where app_id = ? And sucursal_id = ? And pos_id = ? and closed_user_id is Not Null {$search_clause} ORDER BY opened_datetime ASC", [$app_id, $sucursal_id, $pos_id]);
            
            // Verificar si tiene cajas
            if (empty($arr_registers)) {
                // No tiene cajas - mostrar mensaje
                $sheet->SetCellValue('A' . $row_number, '-- Sin cajas en el periodo seleccionado');
                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->getFont()->setItalic(true);
                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "FFE6E6",
                        ]
                    ]
                ]);
                $row_number += 2; // Espacio extra
                continue; // Continuar con el siguiente POS
            }

            // HEADERS DE COLUMNAS (solo si tiene cajas)
            $sheet->SetCellValue('A' . $row_number, 'CAJA #');
            $sheet->SetCellValue('B' . $row_number, 'APERTURA');
            $sheet->SetCellValue('C' . $row_number, 'CIERRE');
            $sheet->SetCellValue('D' . $row_number, 'USUARIO APERT');
            $sheet->SetCellValue('E' . $row_number, 'USUARIO CIERRE');
            $sheet->SetCellValue('F' . $row_number, 'SALDO INIC');
            $sheet->SetCellValue('G' . $row_number, 'SALDO INIC USD');
            $sheet->SetCellValue('H' . $row_number, 'VENTAS');
            $sheet->SetCellValue('I' . $row_number, 'EFECTIVO');
            $sheet->SetCellValue('J' . $row_number, 'TARJETAS');
            $sheet->SetCellValue('K' . $row_number, 'DOLARES');
            $sheet->SetCellValue('L' . $row_number, 'SALDO FINAL');
            $sheet->SetCellValue('M' . $row_number, 'SALDO FINAL USD');
            $sheet->SetCellValue('N' . $row_number, 'DIFERENCIA');
            $sheet->SetCellValue('O' . $row_number, 'DIFERENCIA USD');

            $sheet->getStyle('A' . $row_number . ':O' . $row_number)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_number . ':O' . $row_number)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => "fffed0",
                    ]
                ]
            ]);
            $row_number++;

            // Variables para subtotales por POS
            $subtotal_ventas = 0;
            $subtotal_efectivo = 0;
            $subtotal_tarjetas = 0;
            $subtotal_dolares = 0;
            $subtotal_saldo_final = 0;
            $subtotal_saldo_final_usd = 0;
            $subtotal_diferencias = 0;
            $subtotal_diferencias_usd = 0;
            $cajas_count = 0;
            
            foreach($arr_registers as $pos_register) {
                $cajas_count++;
                $total_cajas_count++;

                // Formatear fechas
                $fecha_apertura = $pos_register['opened_datetime']->format('d/m H:i');
                $fecha_cierre = $pos_register['closed_datetime']->format('d/m H:i');

                // Calcular totales
                $ventas = floatval($pos_register['ventas_total']);
                $efectivo = floatval($pos_register['efectivo_neto_mxn']);
                $tarjetas = floatval($pos_register['tarjetas_total']);
                $dolares = floatval($pos_register['dolares_vendidos_mxn']);
                $saldo_final = floatval($pos_register['efectivo_final_real_mxn']);
                $saldo_final_usd = floatval($pos_register['efectivo_final_real_usd']);
                $diferencia = floatval($pos_register['diferencia_mxn']);
                $diferencia_usd = floatval($pos_register['diferencia_usd']);

                $subtotal_ventas += $ventas;
                $subtotal_efectivo += $efectivo;
                $subtotal_tarjetas += $tarjetas;
                $subtotal_dolares += $dolares;
                $subtotal_saldo_final += $saldo_final;
                $subtotal_saldo_final_usd += $saldo_final_usd;
                $subtotal_diferencias += $diferencia;
                $subtotal_diferencias_usd += $diferencia_usd;

                // Llenar fila de datos
                $sheet->SetCellValue('A' . $row_number, $pos_register['id']);
                $sheet->getStyle('A' . $row_number)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->SetCellValue('B' . $row_number, $fecha_apertura);
                $sheet->SetCellValue('C' . $row_number, $fecha_cierre);
                $sheet->SetCellValue('D' . $row_number, $pos_register['opened_user_name']);
                $sheet->SetCellValue('E' . $row_number, $pos_register['closed_user_name']);
                $sheet->SetCellValue('F' . $row_number, '$' . number_format($pos_register['balance_inicial_mxn'], 2));
                $sheet->SetCellValue('G' . $row_number, '$' . number_format($pos_register['balance_inicial_usd'], 2) . ' USD');
                $sheet->SetCellValue('H' . $row_number, '$' . number_format($ventas, 2));
                $sheet->SetCellValue('I' . $row_number, '$' . number_format($efectivo, 2));
                $sheet->SetCellValue('J' . $row_number, '$' . number_format($tarjetas, 2));
                $sheet->SetCellValue('K' . $row_number, '$' . number_format($dolares, 2));
                $sheet->SetCellValue('L' . $row_number, '$' . number_format($saldo_final, 2));
                $sheet->SetCellValue('M' . $row_number, '$' . number_format($saldo_final_usd, 2) . ' USD');
                $sheet->SetCellValue('N' . $row_number, '$' . number_format($diferencia, 2));
                $sheet->SetCellValue('O' . $row_number, '$' . number_format($diferencia_usd, 2) . ' USD');

                $row_number++;
            }

            // SUBTOTAL DEL PUNTO DE VENTA
            if ($cajas_count > 0) {
                $row_number++; // Espacio
                $sheet->SetCellValue('A' . $row_number, 'SUBTOTAL ' . strtoupper($pos_item['name']) . ' (' . $cajas_count . ' cajas)');
                $sheet->SetCellValue('H' . $row_number, '$' . number_format($subtotal_ventas, 2));
                $sheet->SetCellValue('I' . $row_number, '$' . number_format($subtotal_efectivo, 2));
                $sheet->SetCellValue('J' . $row_number, '$' . number_format($subtotal_tarjetas, 2));
                $sheet->SetCellValue('K' . $row_number, '$' . number_format($subtotal_dolares, 2));
                $sheet->SetCellValue('L' . $row_number, '$' . number_format($subtotal_saldo_final, 2));
                $sheet->SetCellValue('M' . $row_number, '$' . number_format($subtotal_saldo_final_usd, 2) . ' USD');
                $sheet->SetCellValue('N' . $row_number, '$' . number_format($subtotal_diferencias, 2));
                $sheet->SetCellValue('O' . $row_number, '$' . number_format($subtotal_diferencias_usd, 2) . ' USD');

                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "E6FFE6",
                        ]
                    ]
                ]);
                $row_number += 2; // Espacio extra
            }

            // Sumar a totales generales
            $total_general_ventas += $subtotal_ventas;
            $total_general_efectivo += $subtotal_efectivo;
            $total_general_tarjetas += $subtotal_tarjetas;
            $total_general_dolares += $subtotal_dolares;
            $total_general_saldo_final += $subtotal_saldo_final;
            $total_general_saldo_final_usd += $subtotal_saldo_final_usd;
            $total_general_diferencias += $subtotal_diferencias;
            $total_general_diferencias_usd += $subtotal_diferencias_usd;
        }

        
        // TOTAL GENERAL (solo si hay múltiples POS)
        if ($total_cajas_count > 0) {
            $row_number++;
            
            // Solo mostrar total general si hay múltiples puntos de venta
            if (count($arr_pos) > 1) {
                $sheet->SetCellValue('A' . $row_number, 'TOTAL GENERAL - ' . strtoupper($sucursal_name) . ' (' . $total_cajas_count . ' cajas)');
                $sheet->SetCellValue('H' . $row_number, '$' . number_format($total_general_ventas, 2));
                $sheet->SetCellValue('I' . $row_number, '$' . number_format($total_general_efectivo, 2));
                $sheet->SetCellValue('J' . $row_number, '$' . number_format($total_general_tarjetas, 2));
                $sheet->SetCellValue('K' . $row_number, '$' . number_format($total_general_dolares, 2));
                $sheet->SetCellValue('L' . $row_number, '$' . number_format($total_general_saldo_final, 2));
                $sheet->SetCellValue('M' . $row_number, '$' . number_format($total_general_saldo_final_usd, 2) . ' USD');
                $sheet->SetCellValue('N' . $row_number, '$' . number_format($total_general_diferencias, 2));
                $sheet->SetCellValue('O' . $row_number, '$' . number_format($total_general_diferencias_usd, 2) . ' USD');

                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_number . ':O' . $row_number)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => "CCE5FF",
                        ]
                    ]
                ]);
            }
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
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "v_pos_register_report";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));




        //
        $search_value = trim($request->getQueryParam("search")['value']);
        $pos_id = $request->getQueryParam("pid");
        $opened_user_id = $request->getQueryParam("uid");
        //
        $start_opened_datetime = $request->getQueryParam("sd");
        $end_opened_datetime = $request->getQueryParam("ed");

        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.pos_name like '%$search_value%' )
                    )";
        }
        //
        if ( is_numeric($pos_id) ){
            //
            $search_clause .= " And t.pos_id = $pos_id ";
        }
        //
        if ( is_numeric($opened_user_id) ){
            $search_clause .= " And t.opened_user_id = $opened_user_id ";
        }
        //
        if ( $start_opened_datetime && $end_opened_datetime ){
            $search_clause .= " AND ( 
                CAST(opened_datetime AS DATE) >= '$start_opened_datetime' AND CAST(opened_datetime AS DATE) <= '$end_opened_datetime'
            ) ";
        }
        //
        //echo $search_clause; exit;


        


        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        // 
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "debug" => true,
                "field" => $order_field,
                "direction" => $order_direction,
                "count_stmt" => function() use ($table_name, $search_clause){
                    return "Select 
                        COUNT(*) AS total,
                        COALESCE(SUM(t.total_ventas), 0) AS sum_total_ventas,
                        COALESCE(SUM(t.balance_inicial_mxn), 0) AS sum_balance_inicial_mxn,
                        COALESCE(SUM(t.balance_inicial_usd), 0) AS sum_balance_inicial_usd,
                        COALESCE(SUM(t.ventas_total), 0) AS sum_ventas_total,
                        COALESCE(SUM(t.efectivo_cobrado_mxn), 0) AS sum_efectivo_cobrado_mxn,
                        COALESCE(SUM(t.cambio_dado), 0) AS sum_cambio_dado,
                        COALESCE(SUM(t.efectivo_neto_mxn), 0) AS sum_efectivo_neto_mxn,
                        COALESCE(SUM(t.tarjetas_total), 0) AS sum_tarjetas_total,
                        COALESCE(SUM(t.dolares_vendidos_mxn), 0) AS sum_dolares_vendidos_mxn,
                        COALESCE(SUM(t.dolares_vendidos_usd), 0) AS sum_dolares_vendidos_usd,
                        COALESCE(SUM(t.efectivo_final_esperado_mxn), 0) AS sum_efectivo_final_esperado_mxn,
                        COALESCE(SUM(t.efectivo_final_esperado_usd), 0) AS sum_efectivo_final_esperado_usd,
                        COALESCE(SUM(t.efectivo_final_real_mxn), 0) AS sum_efectivo_final_real_mxn,
                        COALESCE(SUM(t.efectivo_final_real_usd), 0) AS sum_efectivo_final_real_usd,
                        COALESCE(SUM(t.diferencia_mxn), 0) AS sum_diferencia_mxn,
                        COALESCE(SUM(t.diferencia_usd), 0) AS sum_diferencia_usd
                            From {$table_name} t Where t.app_id = ? {$search_clause}";
                },
                //
                "cnt_params" => array(
                    $app_id
                ),

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
                                      ,ROW_NUMBER() OVER (ORDER BY COALESCE({$order_field}, id) {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                      
                                            Where t.app_id = ?
                                            
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
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }





    



    // 
    public function PaginateRegisterSales($request, $response, $args) {


        // 
        $table_name = "v_sales";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));


        //
        $search_value = trim($request->getQueryParam("search")['value']);
        $created_user_id = $request->getQueryParam("uid");


        //
        $search_clause = "";
        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.pos_name like '%$search_value%' )
                    )";
        }
        //
        if ( is_numeric($created_user_id) ){
            //
            $search_clause .= " And t.created_user_id = $created_user_id ";
        }
        //echo $search_clause; exit;


        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];




        //
        $pos_register_id = $args['pos_register_id'];



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
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.pos_register_id = ? {$search_clause}";
                },
                //
                "cnt_params" => array(
                    $app_id,
                    $pos_register_id
                ),

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
                                      
                                            Where t.app_id = ?
                                            And t.pos_register_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                //
                "params" => array(
                    $app_id,
                    $pos_register_id
                ),

                //
                "parseRows" => function(&$row) use($app_id){
                    //dd($row); exit;
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }




    
    
    
    
    

    



   public function GetRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];

        //
        $record_id = $args['id'];

        //
        $schedule = Query::Single("Select * From v_pos_register_report where app_id = ? And id = ?", [$app_id, $record_id]);
        
        //
        return $response->withJson($schedule, 200);
    }




    

    



    /**
     * 
     * Buscamos registers de un pos determinado
     */
    public function GetPosRegistersListByDate($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $user_id = $ses_data['id'];

        //
        $pos_id = $args['pos_id'];
        $start_date = $args['start_date'];
        $end_date = $args['end_date'];
        //echo "$start_date, $end_date"; exit;
        

        //
        $str_where_date = " AND ( 
            CAST(opened_datetime AS DATE) >= '$start_date' AND CAST(opened_datetime AS DATE) <= '$end_date'
        )";
        //echo $str_where_date; exit;


        // Construir consulta según pos_id
        if ($pos_id === 'all') {
            // Todas las cajas de la fecha
            $results = Query::Multiple(
                "SELECT * FROM v_pos_registers 
                WHERE app_id = ? AND sucursal_id = ? {$str_where_date}", 
                [$app_id, $sucursal_id, $start_date]
            );
        } else {
            // Caja específica de la fecha
            $results = Query::Multiple(
                "SELECT * FROM v_pos_registers 
                WHERE app_id = ? AND sucursal_id = ? AND pos_id = ? {$str_where_date}", 
                [$app_id, $sucursal_id, $pos_id, $start_date]
            );
        }

        //
        return $response->withJson($results, 200);
    }




    


}