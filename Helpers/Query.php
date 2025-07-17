<?php
namespace Helpers;



use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;



class Query {



    //
    public static function PaginateRecords($start, $length, $order_info, $search_clause, $draw, $options){
        global $app;

        //var_dump( $order_info); exit;

        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );


        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);




            //
            $params = (isset($options['params'])) ? $options['params'] : [];
            //var_dump($params); exit;

            //
            $count_params = (isset($options['cnt_params'])) ? $options['cnt_params'] : $params;
            //var_dump($count_params); exit;



            // default total 0
            $total = 0;


            //----------------------------------------- Get Total Query ----------------
            if (isset($options['count_stmt'])){

                //
                $count_statement = $options['count_stmt']($options['table_name'], $search_clause);
                //echo $count_statement; exit;
                //
                $query_total = $sqlsrv_inst->query($count_statement, $count_params);
                // sql exceptions
                if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_total) ){
                    $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, array(
                        "default" => "Server Error, unable to do operation",
                        "SQLSTATE: 42S02" => "custom match err 42S02"
                    ));
                    return $results;
                }
                //
                if($row = sqlsrv_fetch_array($query_total, SQLSRV_FETCH_ASSOC)){
                    //var_dump($row); exit;
                    $total = $row['total'];
                }

            } else if (isset($options['count_total']) && is_numeric($options['count_total']) ){
                $total = $options['count_total'];
            }
            //echo " $total "; exit;


            // defaults
            $start = ($start) ? $start : 0; // for page number 0 = page 1
            $length = ($length) ? $length : 6; // for records length

    
            /*
             * Pagination Values
             * */
            if ( $total > 0 ){
                $total_pages    = ceil( $total / $length );
                $limit          = ($start + $length);
                $start++;
                //
                $where_row_clause = " AND row BETWEEN $start AND $limit ";                
            } else {
                $total_pages = 0;
                $limit = 0;
                $start = 0;
                //
                $where_row_clause = " AND row BETWEEN 0 AND 0 ";
            }
            //echo $where_row_clause; exit;
            




            //----------------------------------------- Get RECORDS Query ----------------
            //
            //
            $records_statement = $options['records_stmt']($options['table_name'], $order_info['field'], $order_info['direction'], $search_clause, $where_row_clause);
            //echo $records_statement; exit;
            //
            $query_main = $sqlsrv_inst->query($records_statement, $params);
            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_main) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, array(
                    "default" => "Server Error, unable to do operation",
                    "SQLSTATE: 42S02" => "custom match err 42S02"
                ));
                return $results;
            }



            //
            $records = array();
            //
            $records['recordsTotal']    = $total;
            $records['recordsFiltered'] = $total;
            $records['totalpages']      = $total_pages;
            $records['draw']            = $draw;
            //
            $records['data']            = array();
            //
            while($row = sqlsrv_fetch_array($query_main, SQLSRV_FETCH_ASSOC)){
                //var_dump($row); exit;
                //
                $options['parseRows']($row);
                //
                array_push($records['data'], $row);
            }

            //
            return $records;

        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }






    //
    public static function PaginateRecordsV2($start, $length, $draw, $options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );


        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);




            //
            $params = (isset($options['params'])) ? $options['params'] : [];
            //var_dump($params); exit;

            //
            $count_params = (isset($options['cnt_params'])) ? $options['cnt_params'] : $params;
            //var_dump($count_params); exit;


            //----------------------------------------- Get Total Query ----------------
            //
            $count_statement = $options['count_stmt']();
            //echo $count_statement; exit;


            //
            $query_total = $sqlsrv_inst->query($count_statement, $count_params);
            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_total) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, array(
                    "default" => "Server Error, unable to do operation",
                    "SQLSTATE: 42S02" => "custom match err 42S02"
                ));
                return $results;
            }
            // default total 0
            $total = 0;
            $arr_header_fields = null;
            //
            if($row = sqlsrv_fetch_array($query_total, SQLSRV_FETCH_ASSOC)){
                //dd($row);
                //
                $arr_header_fields = $row;
                $total = $row['total'];
            }
            //echo $total; exit;


            //
            if ($total == 0) {
                // No hay registros, devolver resultado vacío sin ejecutar la consulta de paginación
                $records = array();
                $records['header'] = $arr_header_fields;
                $records['recordsTotal'] = 0;
                $records['recordsFiltered'] = 0;
                $records['totalpages'] = 0;
                $records['draw'] = $draw;
                $records['data'] = array();
                return $records;
            }



            //dd($options);
            $length = ($length && $length > 0) ? $length : 6;
            


            /*
             * Pagination Values
             * */
            $total_pages    = ceil( $total / $length );
            $limit          = ($start + $length);
            $start++;
            //
            $where_row_clause = " AND row BETWEEN $start AND $limit ";
            //echo $where_row_clause; exit;




            //----------------------------------------- Get RECORDS Query ----------------
            //
            //
            $records_statement = $options['records_stmt']($where_row_clause);


            //
            $query_main = $sqlsrv_inst->query($records_statement, $params);
            //dd($query_main);
            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_main) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, array(
                    "default" => "Server Error, unable to do operation",
                    "SQLSTATE: 42S02" => "custom match err 42S02"
                ));
                return $results;
            }



            //
            $records = array();
            //
            $records['header']          = $arr_header_fields;
            $records['recordsTotal']    = $total;
            $records['recordsFiltered'] = $total;
            $records['totalpages']      = $total_pages;
            $records['draw']            = $draw;
            //
            $records['data']            = array();
            //
            while($row = sqlsrv_fetch_array($query_main, SQLSRV_FETCH_ASSOC)){
                //dd($row);
                //
                $options['parseRows']($row);
                //
                array_push($records['data'], $row);
            }

            //
            //dd($records);
            return $records;

        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }






    //
    public static function ScrollPaginate($start_record, $num_records, $options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );


        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);




            //
            $params = (isset($options['params'])) ? $options['params'] : [];
            //var_dump($params); exit;

            //
            $count_params = (isset($options['cnt_params'])) ? $options['cnt_params'] : $params;
            //var_dump($count_params); exit;


            //----------------------------------------- Get Total Query ----------------
            //
            $count_statement = $options['count_stmt']();
            //echo $count_statement; exit;


            //
            $query_total = $sqlsrv_inst->query($count_statement, $count_params);
            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_total) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, array(
                    "default" => "Server Error, unable to do operation",
                    "SQLSTATE: 42S02" => "custom match err 42S02"
                ));
                return $results;
            }
            // default total 0
            $total = 0;
            //
            if($row = sqlsrv_fetch_array($query_total, SQLSRV_FETCH_ASSOC)){
                $total = $row['total'];
            }
            //echo $total; exit;





            //
            $num_records = ($num_records && $num_records>0) ? $num_records : 5;



            /*
             * Pagination Values
             * */
            $total_pages    = ceil( $total / $num_records );
            //
            $limit_to_row = ( $start_record + $num_records );
            //
            $where_row_clause = " AND row BETWEEN $start_record AND " . ( $limit_to_row - 1 );
            //echo $where_row_clause; exit;




            //----------------------------------------- Get RECORDS Query ----------------
            //
            //
            $records_statement = $options['records_stmt']($where_row_clause);
            //echo $records_statement; exit;
            //
            $query_main = $sqlsrv_inst->query($records_statement, $params);
            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_main) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, array(
                    "default" => "Server Error, unable to do operation",
                    "SQLSTATE: 42S02" => "custom match err 42S02"
                ));
                return $results;
            }




            //
            $records = array();
            //
            $records['total_records'] = $total;
            $records['total_pages'] = $total_pages;
            $records['num_records'] = $num_records;
            $records['next_start_record'] = $limit_to_row++;
            //$records['where_row'] = $where_row_clause;
            //
            $records['data']            = array();
            //
            while($row = sqlsrv_fetch_array($query_main, SQLSRV_FETCH_ASSOC)){
                //var_dump($row); exit;
                //
                $options['parseRows']($row);
                //
                array_push($records['data'], $row);
            }

            //
            return $records;

        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }








    /*
     *
     * Limit section requests
     *
     * */
    public static function LimitSectionRequests($section_name, $request_identifier, $max_requests, $wait_minutes){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = false;



        //
        $wait_minutes_positive = abs($wait_minutes);

        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation",
            "YOU_HAVE_REACHED_MAX_REQUESTS" => "has alcanzado el maximo de {$max_requests} solicitudes, espera {$wait_minutes_positive} minutos"
        );






        //
        try {




            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);


            //
            $stmt = "{call usp_Auth_LimitSectionRequests(?,?,?,?,?)}";
            //echo $stmt; exit;




            //
            $param_success = 0;




            /*
             *
             * FIX WAIT MINUTES CONVERT TO NEGATIVE IF USER SETS POSITIVE NUMBER
             * ESTO ES POR QUE EN EL STORED PROCEDURE NECESITAMOS UN VALOR POSITIVO
             *
             * */
            if ( $wait_minutes > 0 ){
                $wait_minutes_negative = -abs($wait_minutes);
            }
            //
            else {
                $wait_minutes_negative = $wait_minutes;
            }
            //echo $wait_minutes_negative; exit;



            //
            $params = array(
                //
                array($section_name, SQLSRV_PARAM_IN),
                array($request_identifier, SQLSRV_PARAM_IN),
                array($max_requests, SQLSRV_PARAM_IN),
                array($wait_minutes_negative, SQLSRV_PARAM_IN),
                //
                array(&$param_success, SQLSRV_PARAM_OUT),
            );
            //var_dump($params); exit;



            //echo $stmt; exit;
            $query_insert = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_insert) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            $results['success'] = $param_success;


            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
        }
    }







    //
    public static function All($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);


            //
            $stmt = $options['stmt']();
            //echo $stmt; exit;


            //
            $params = (isset($options['params']) ? $options['params'] : null);
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }

            //
            $records = array();

            //
            while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){

                //
                (isset($options['parse']) ? $options['parse']($row) : null);

                //
                array_push($records, $row);
            }

            //
            return $records;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }





    /*
     *
     * SE TRAE TODO PERO EL PARSE DETERMINA QUE AGREGAR AL RECORDS DEL WHILE
     *
     * */
    public static function AllCond($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);


            //
            $stmt = $options['stmt']();
            //echo $stmt; exit;


            //
            $params = (isset($options['params']) ? $options['params'] : null);
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }

            //
            $records = array();

            //
            while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){

                //
                (isset($options['parse']) ? $options['parse']($records, $row) : null);

            }

            //
            return $records;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }





    //
    public static function Get($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);


            //
            $stmt = $options['stmt']();
            //echo $stmt; exit;


            //
            $params = (isset($options['params']) ? $options['params'] : null);
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }

            //
            if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                //var_dump($row); exit;
                //
                (isset($options['parse']) ? $options['parse']($row) : null);
                //
                return $row;
            }

            //
            return null;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }






    //
    public static function StoredProcedure($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = isset($options['debug']) ? $options['debug'] : false;

        //
        $exeptions_msgs = (isset($options['exeptions_msgs']) ? $options['exeptions_msgs'] : array(
            "default" => "Server Error, unable to do operation"
        ));




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);


            //
            $stmt = $options['stmt']();
            //echo $stmt; exit;



            //
            $params = (isset($options['params']) ? $options['params']() : null);
            //print_r($params); exit;


            //
            if ( $stmt === "{call usp_UpsertConfigSquare(?,?,?,?,?,?,?,?)}" ){
                //var_dump($params); exit;
            }


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            if ( isset($options['ret']) && $options['ret'] === "single" ){
                //
                if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                    (isset($options['parse']) ? $options['parse']($row) : null);
                    return $row;
                }
            }
            //
            else if ( isset($options['ret']) && $options['ret']  === "all" ){
                //
                $records = array();
                while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                    (isset($options['parse']) ? $options['parse']($row) : null);
                    array_push($records, $row);
                }
                return $records;
            }



            //
            return null;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }







    //
    public static function DoTask($options){
        global $app;


        /*
         * ESTO SE NECESITA PARA WEBSOCKETS,
         * NO MODIFICAR POR QUE SE AFECTAR EL web-sockets.php
         * */
        if ($app){
            $settings = $app->getContainer()->settings;
        } else {
            $settings_file = require PATH_BASE.DS.'settings.php';
            $settings = $settings_file['settings'];
        }

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = isset($options['debug']) ? $options['debug'] : false;

        //
        $exeptions_msgs = (isset($options['exeptions_msgs']) ? $options['exeptions_msgs'] : array(
            "default" => "Server Error, unable to do operation"
        ));




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);








            //
            $stmt = $options['stmt'];
            //echo $stmt; exit;



            //
            $params = (isset($options['params']) ? $options['params'] : null);
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query_insert_or_update = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_insert_or_update) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }

            //
            $results['task'] = $options['task'];

            // ADD RECORD
            if ($options['task']=="add"){
                if ( is_numeric($query_insert_or_update) ){
                    (isset($options['parse']) ? $options['parse']((int)$query_insert_or_update, $results) : null);
                }
            }
            // UPDATE/DELTE RECORD
            else {
                (isset($options['parse']) ? $options['parse']((int)$query_insert_or_update, $results) : null);
            }


            //
            return $results;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }








    //
    public static function DoTaskV2($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = isset($options['debug']) ? $options['debug'] : false;

        //
        $exeptions_msgs = (isset($options['exeptions_msgs']) ? $options['exeptions_msgs'] : array(
            "default" => "Server Error, unable to do operation"
        ));



        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);








            //
            $stmt = $options['stmt']();
            //echo $stmt; exit;



            //
            $params = (isset($options['params']) ? $options['params']() : null);
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query_insert_or_update = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_insert_or_update) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }

            //
            $results['task'] = $options['task'];

            // ADD RECORD
            if ($options['task']=="add"){
                if ( is_numeric($query_insert_or_update) ){
                    (isset($options['parse']) ? $options['parse']((int)$query_insert_or_update, $results) : null);
                }
            }
            // UPDATE/DELTE RECORD
            else {
                (isset($options['parse']) ? $options['parse']((int)$query_insert_or_update, $results) : null);
            }


            //
            return $results;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }












    //
    public static function Single($stmt, $params = [], $parse = null){
        global $app;


        /*
         * ESTO SE NECESITA PARA WEBSOCKETS,
         * NO MODIFICAR POR QUE SE AFECTAR EL web-sockets.php
         * */
        if ($app){
            $settings = $app->getContainer()->settings;
        } else {
            $settings_file = require PATH_BASE.DS.'settings.php';
            $settings = $settings_file['settings'];
        }
        //Helper::printFull($settings); exit;


        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);




            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }



            //
            if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                ($parse ? $parse($row) : null);
                return $row;
            }


            //
            return null;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }



    //
    public static function SingleRetValue($ret_value, $stmt, $params = []){
        global $app;


        /*
         * ESTO SE NECESITA PARA WEBSOCKETS,
         * NO MODIFICAR POR QUE SE AFECTAR EL web-sockets.php
         * */
        if ($app){
            $settings = $app->getContainer()->settings;
        } else {
            $settings_file = require PATH_BASE.DS.'settings.php';
            $settings = $settings_file['settings'];
        }


        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);




            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }



            //
            if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                //var_dump($row); exit;
                if (isset($row[$ret_value])){
                    return $row[$ret_value];
                }
            }


            //
            return null;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }





    //
    public static function Multiple($stmt, $params = [], $parse = null){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);




            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }



            //
            $records = array();
            while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                //
                ($parse ? $parse($row) : null);
                //
                array_push($records, $row);
            }
            return $records;


            //
            return null;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }








    //
    public static function GetAllByAcctId($table_name, $account_id, $display_active = false){
        //
        return self::All([
            "stmt" => function() use($table_name, $display_active){
                //
                if ($display_active){
                    return "SELECT t.* FROM {$table_name} t Where t.account_id = ? And t.active = 1";
                } else {
                    return "SELECT t.* FROM {$table_name} t Where t.account_id = ?";
                }
            },
            "params" => [
                $account_id
            ]
        ]);
    }


    //
    public static function GetAll($table_name, $account_id, $display_active = false){
        //
        return self::All([
            "stmt" => function() use($table_name, $display_active){
                //
                if ($display_active){
                    return "SELECT t.* FROM {$table_name} t Where t.account_id = ? And t.active = 1";
                } else {
                    return "SELECT t.* FROM {$table_name} t Where t.account_id = ?";
                }
            },
            "params" => [
                $account_id
            ]
        ]);
    }


    //
    public static function GetRecordById($table_name, $account_id, $id, $display_active = false){
        //
        return Query::Get([
            "stmt" => function() use($table_name, $display_active){
                //
                if ($display_active){
                    return "SELECT t.* FROM {$table_name} t Where t.account_id = ? And t.id = ? And t.active = 1";
                } else {
                    return "SELECT t.* FROM {$table_name} t Where t.account_id = ? And t.id = ?";
                }
            },
            "params" => [
                $account_id,
                $id
            ]
        ]);
    }




}

