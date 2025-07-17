<?php
namespace App\Databases;


//
class MySqli
{




    //
    public $link = array();






    //
    public function __construct($db_config, $debug = false) {


        //
        $this->debug = $debug;



        //
        if (function_exists('mysqli_connect')){
            try{
                //
                $this->link = mysqli_connect($db_config['server'], $db_config['uid'], $db_config['pass'], $db_config['database']);
                //
                if ( isset($db_config['character_set']) && $db_config['character_set'] ){
                    $this->link->set_charset("utf8");
                }

                //$driver = new mysqli_driver();
                //$driver->report_mode = MYSQLI_REPORT_ALL;

            }
            catch(Exception $e){
                throw new Exception($e->getMessage());
            }

        } else {
            throw new Exception('mysqli module is not installed');
        }

    }



}
