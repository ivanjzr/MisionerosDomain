<?php
namespace App;

//
use App\Databases\MySqli;
use App\Databases\MySqliHelper;
use Helpers\Helper;



//
class PagesContent
{






    //
    public static function PaginateRecords($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();

        //mysqli_report(MYSQLI_REPORT_ALL);


        //$mysqli_debug = $settings['mysqli_debug'];
        $mysqli_debug = true;

        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );


        //
        try {



            //
            $mysqli_inst = new MySqli($settings['sqlsrv_connection']);
            //var_dump($mysqli_inst); exit;




            //
            $page_no = $mysqli_inst->link->real_escape_string(trim($options['page_no']));
            $page_size = $mysqli_inst->link->real_escape_string(trim($options['page_size']));
            //
            $order_field = $mysqli_inst->link->real_escape_string(trim($options['order_field']));
            $order_type = $mysqli_inst->link->real_escape_string(trim($options['order_type']));




            // filter search fields
            $_search = $mysqli_inst->link->real_escape_string(trim($options['_search']));
            //
            $filter_pagina_id = $mysqli_inst->link->real_escape_string(trim(Helper::safeVar($options, "filter_pagina_id")));
            $filter_idioma_id = $mysqli_inst->link->real_escape_string(trim(Helper::safeVar($options, "filter_idioma_id")));

            /*
            * Filter Records (Search)
            * */
            //
            $search_clause = null;
            //
            if ( $_search && $_search == 'true' ){

                //
                if ( $filter_pagina_id ){
                    $search_clause .= " And t.page_id = $filter_pagina_id ";
                }

                //
                if ( $filter_idioma_id ){
                    $search_clause .= " And tp.lang_id = $filter_idioma_id ";
                }

            } else {

                //
                $search_clause = " And 1 = 2 ";

            }
            //echo $search_clause; exit;




            /*
             * Count Statement
             * */
            $stmt_total = "
            Select 
                Count(*) total 
                  From pages_content t 
                    Left Join pages tp On tp.id = t.page_id
                    Where 1=1
                    " . $search_clause . "
            ";

            //echo $stmt_total; exit;
            $query_total = $mysqli_inst->link->prepare($stmt_total);
            /*
            $bind_results = $stmt_total->bind_param(
                's',
                $options['option_value']
            );
            //
            if ( false === $bind_results ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $settings['exceptions']);
                return $results;
            }
            */
            //var_dump($stmt_total); exit;

            //
            if ( false === $query_total ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }


            //
            $query_total->execute();


            //
            if ( $query_total->errno ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }

            /*
             * fetch & release results
             * */
            $query_total->bind_result($total);
            //
            $query_total->fetch();
            //echo $total; exit;
            //
            $query_total->free_result();


            /*----------------------------------------------------------------------*/


            /*
            * Create patination clause
            * */
            $total_pages     = ceil( $total / $page_size );
            $offset = ( $page_no - 1 ) * $page_size;
            //
            $where_row_clause = " LIMIT " . $offset . "," . $page_size ;
            //echo $where_row_clause; exit;



            /*----------------------------------------------------------------------*/


            /*
             * Records Statement
             * */
            $stmt_records = "
              SELECT            
                    
                    t.*
                    
                        FROM pages_content t
                        
                          Left Join pages tp On tp.id = t.page_id
                          
                          Where 1 = 1
                          
                          " . $search_clause . "
                          
                          Order By " . $order_field . " " . $order_type . "
                          
                          " . $where_row_clause . "                            
            ";

            //echo $stmt_records; exit;
            $query_records = $mysqli_inst->link->prepare($stmt_records);

            //
            if ( false === $query_records ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }

            /*
            $query_records->bind_param(
                'ss',
                $options['order_field'],
                $options['order_type']
            );
            */
            //var_dump($query_records); exit;


            //
            $query_records->execute();


            //
            if ( $query_records->errno ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }


            /*
             * fetch & release results
             * */
            $results = $query_records->get_result();

            //
            $records = array();
            //
            $records['totalrecords']    = $total; // Total count
            $records['totalpages']      = $total_pages; // Total pages
            $records['currpage']        = $page_no; // Current page number
            $records['rows']            = array();

            //
            while ($row = $results->fetch_array(MYSQLI_ASSOC)){
                //var_dump($row);
                array_push($records['rows'], $row);
            }
            //var_dump($records); exit;


            /*
            // Otra forma de traer resultados
            $query_records->bind_result($id, $name);
            //
            $records = array();
            while ( $query_records->fetch() ) {
                //
                array_push($records, array(
                    "id" => $id,
                    "name" => $name
                ));
            }
            var_dump($records); exit;
            */

            //
            $query_records->free_result();


            //
            return $records;
        }
        catch (\Exception $exeption){
            $exception_msg = MySqliHelper::parse_exception_error($exeption);
            $results['error'] = MySqliHelper::catch_err_msg($settings['mysqli_debug'], $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($mysqli_inst) && isset($mysqli_inst->link) && $mysqli_inst->link ){ $mysqli_inst->link->close(); }
        }
    }






    //
    public static function GetRecordById($id){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();

        //mysqli_report(MYSQLI_REPORT_ALL);

        //$mysqli_debug = $settings['mysqli_debug'];
        $mysqli_debug = false;

        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );



        //
        try {



            //
            $mysqli_inst = new MySqli($settings['sqlsrv_connection']);
            //var_dump($mysqli_inst); exit;



            /*
             * Get Record Statement
             * */
            $stmt_records = "
              SELECT
                t.*
                  FROM pages_content t                    
                    Where t.id = ?
                                                      
            ";

            //echo $stmt_records; exit;
            $query_records = $mysqli_inst->link->prepare($stmt_records);

            //
            if ( false === $query_records ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }

            //
            $query_records->bind_param(
                'i',
                $id
            );
            //var_dump($query_records); exit;


            //
            $query_records->execute();


            //
            if ( $query_records->errno ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }


            /*
             * fetch & release results
             * */
            $query_results = $query_records->get_result();

            //
            if ($row = $query_results->fetch_array(MYSQLI_ASSOC)){
                //var_dump($row);

                //
                $query_records->free_result();

                //
                return $row;
            }


            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = MySqliHelper::parse_exception_error($exeption);
            $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($mysqli_inst) && isset($mysqli_inst->link) && $mysqli_inst->link ){ $mysqli_inst->link->close(); }
        }
    }









    //
    public static function GetPageContent($page_id){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();

        //mysqli_report(MYSQLI_REPORT_ALL);

        //$mysqli_debug = $settings['mysqli_debug'];
        $mysqli_debug = true;

        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );



        //
        try {



            //
            $mysqli_inst = new MySqli($settings['sqlsrv_connection']);
            //var_dump($mysqli_inst); exit;



            /*
             * Get Record Statement
             * */
            $stmt_records = "
              SELECT
                t.*
                  FROM pages_content t                    
                    
                    Where t.page_id = ?
                                        
                    And t.active = 1              
                                                            
            ";

            //echo $stmt_records; exit;
            $query_records = $mysqli_inst->link->prepare($stmt_records);

            //
            if ( false === $query_records ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }

            //
            $query_records->bind_param(
                'i',
                $page_id
            );
            //var_dump($query_records); exit;


            //
            $query_records->execute();


            //
            if ( $query_records->errno ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }


            /*
             * fetch & release results
             * */
            $query_results = $query_records->get_result();

            //
            $records = array();

            //
            while ($row = $query_results->fetch_array(MYSQLI_ASSOC)){
                //var_dump($row);
                array_push($records, $row);
            }


            $query_records->free_result();
            //
            return $records;
        }
        catch (\Exception $exeption){
            $exception_msg = MySqliHelper::parse_exception_error($exeption);
            $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($mysqli_inst) && isset($mysqli_inst->link) && $mysqli_inst->link ){ $mysqli_inst->link->close(); }
        }
    }









    //
    public static function Edit($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();

        //mysqli_report(MYSQLI_REPORT_ALL);

        //$mysqli_debug = $settings['mysqli_debug'];
        $mysqli_debug = false;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation",
            "idx_administrators_email" => "ya existe correo que intentas agregar"
        );



        //
        try {



            //
            $mysqli_inst = new MySqli($settings['sqlsrv_connection']);
            //var_dump($mysqli_inst); exit;




            /*
             * Update Statement
             * */
            //
            $stmt_records = "
                  
                  Update 
                    pages_content
                  
                  Set 
                    content = ?
                  
                  Where id = ?
                                                            
                  ";




            //echo $stmt_records; exit;
            $query_records = $mysqli_inst->link->prepare($stmt_records);

            //
            if ( false === $query_records ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }


            //
            //
            $query_records->bind_param(
                'si',
                $options['content'],
                $options['id']
            );
            //var_dump($query_records); exit;


            //
            $query_records->execute();


            //
            if ( $query_records->errno ) {
                $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $mysqli_inst->link->errno . " - " . $mysqli_inst->link->error, $exeptions_msgs);
                return $results;
            }


            //
            $results['affected_rows'] = $query_records->affected_rows;
            $results['id'] = $options['id'];

            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = MySqliHelper::parse_exception_error($exeption);
            $results['error'] = MySqliHelper::catch_err_msg($mysqli_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($mysqli_inst) && isset($mysqli_inst->link) && $mysqli_inst->link ){ $mysqli_inst->link->close(); }
        }
    }









}
