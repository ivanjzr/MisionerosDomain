<?php
namespace App\Databases;

//
use App\Databases\SqlServerHelper;


//
class SqlServer
{




    //
    public $connection = array();






    //
    public function __construct($sqlsrv_config) {
        if (function_exists('sqlsrv_connect')){
            //
            $this->connection = sqlsrv_connect($sqlsrv_config['server'], array("UID" => $sqlsrv_config['uid'], "PWD" => $sqlsrv_config['pass'], "Database" => $sqlsrv_config['database'], "CharacterSet"  => $sqlsrv_config['character_set']));
            //
            if( $this->connection === false ) {
                throw new \Exception(SqlServerHelper::parse_sql_exception());
            }
        } else {
            throw new \Exception('SQL Server module is not installed');
        }
    }





    // funcion que ejecuta un solo query
    public function query($query, $params = array()){
        if ( is_array($params) && count($params) > 0 ){
            return sqlsrv_query($this->connection, $query, $params);
        }
        else {
            return sqlsrv_query($this->connection, $query);
        }
    }





    // funcion que ejecuta 2 queries (uno tras otro)
    public function query_next($query, $params){
        //
        $query_results = false;
        //
        if ( is_array($params) && count($params) > 0 ){
            $query_results = sqlsrv_query($this->connection, $query, $params);
            //
            if( $query_results ) {
                sqlsrv_next_result($query_results);
                sqlsrv_fetch($query_results);
                // devuelve false en caso de error
                return sqlsrv_get_field($query_results, 0);
            }
        }
        // devuelve aun con error
        return $query_results;
    }





    public function getConn(){
        return $this->connection;
    }



    /*
     *
     *
     */
    public function closeConnection(){
        sqlsrv_close($this->connection);
    }


}
