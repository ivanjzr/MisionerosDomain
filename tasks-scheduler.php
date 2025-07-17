<?php
/**
 *
 * A WRAPPER CLASS FOR SCHEDULER FROM POWERSHELL
 *
 */
//    schtasks /Create [/S <system> [/U <username> [/P [<password>]]]]
//    [/RU <username> [/RP <password>]] /SC <schedule> [/MO <modifier>] [/D <day>]
//    [/M <months>] [/I <idletime>] /TN <taskname> /TR <taskrun> [/ST <starttime>]
//    [/RI <interval>] [ {/ET <endtime> | /DU <duration>} [/K] [/XML <xmlfile>] [/V1]]
//    [/SD <startdate>] [/ED <enddate>] [/IT] [/Z] [/F]
// https://msdn.microsoft.com/en-us/library/windows/desktop/bb736357(v=vs.85).aspx
class scheduler{




    //
    public $cmd = "";




    //
    public function __construct($override_default_php_timezone = false, $use_abs_path = false, $is_x64 = false){
        // default is 32 bits
        $ver = "System32";
        if($is_x64){
            $ver = "SysWOW64";
        }
        // determine exe to use
        $this->schtasks_exe = "schtasks";
        if ($use_abs_path){
            $this->schtasks_exe = "c:\\Windows\\$ver\\schtasks.exe ";
        }
        //
        if ($override_default_php_timezone){
            // 'America/Cancun'
            date_default_timezone_set($override_default_php_timezone);
        }
    }



    //
    public function debug(){
        echo $this->cmd;
    }






    // /FO format - A value that specifies the output format. The valid values are TABLE, LIST, and CSV.
    // /NH - A value that specifies that the column header should not be displayed in the output. This is valid only for TABLE and CSV formats.
    public function query($list_type = "csv", $column_header = false){
        $column_header_data = "";
        if ($column_header){
            $column_header_data = "/NH";
        }
        $list_type = strtoupper($list_type);
        $this->cmd = $this->schtasks_exe." /QUERY /FO $list_type $column_header_data ";
    }


	//schtasks /Delete
	//[/S system [/U username [/P [password]]]]
	//[/TN taskname] [/F]
	public function delete($tsk_name){
		$this->cmd = $this->schtasks_exe." /DELETE /TN $tsk_name /F";
	}


    //
    public function create($tsk_name){
        $this->cmd = $this->schtasks_exe." /CREATE /TN $tsk_name ";
    }



    // MINUTE, HOURLY, DAILY, WEEKLY, MONTHLY, ONCE, ONLOGON, ONIDLE, and ONEVENT.
    public function type($task_type){
        $task_type = strtoupper($task_type);
        $this->cmd = $this->cmd . " /SC $task_type ";
    }




    // not applicable for the following schedule types: ONSTART, ONLOGON, ONIDLE, and ONEVENT.
    public function startDate($custom = false, $modify = ""){
        //
        $start_date = "";

        // custom start date
        if ($custom){
            $start_date = " $custom ";
        }

        // start date from today
        else {
            //
            $date = new DateTime("now");
            //
            if ($modify){
                $date->modify($modify);
            }
            //
            $start_date = $date->format("m/d/Y");
        }

        //
        $this->cmd = $this->cmd . " /SD $start_date ";
    }



    // not applicable for the following schedule types: ONSTART, ONLOGON, ONIDLE, and ONEVENT.
    public function endDate($custom = false, $modify = ""){
        //
        $end_date = "";

        // custom end date
        if ($custom){
            $end_date = " $custom ";
        }

        // end date from today
        else {
            //
            $date = new DateTime("now");
            //
            if ($modify){
                $date->modify($modify);
            }
            //
            $end_date = $date->format("m/d/Y");
        }

        //
        $this->cmd = $this->cmd . " /ED $end_date ";
    }




    //
    public function startTime($custom = false, $modify = ""){

        //
        $start_time = "";

        // custom start time
        if ($custom){
            $start_time = " $custom ";
        }

        // start time from today plus 1 minute
        else {

            //
            $date = new DateTime("now");

            //
            if ($modify){
                $date->modify($modify);
            } else {
                $date->modify("+1 minutes");
            }

            //
            $start_time = $date->format("H:i");
        }

        //
        $this->cmd = $this->cmd . " /ST $start_time ";
    }



    //
    public function deleteAfterFinalRun(){
        $this->cmd = $this->cmd . " /Z ";
    }



    // additional parameters
    // example: $tasktrigger = "/sc DAILY /ST $start_task_time /F /RI 1 /DU 24:00";
    public function additional($aditional){
        if ($aditional){
            $aditional = strtoupper($aditional);
            $this->cmd = $this->cmd . " $aditional ";
        }
    }



    // requires auth
    public function auth($username, $password){
        //
        $user_data = "";
        if ($username){
            $user_data = "/RU $username";
        }
        //
        $pwd_data = "";
        if ($password){
            $pwd_data = "/RP $password";
        }
        //
        $this->cmd = $this->cmd . " $user_data $pwd_data ";
    }



	// requires auth type 2
    public function auth2($username, $password){
        //
        $user_data = "";
        if ($username){
            $user_data = "/U $username";
        }
        //
        $pwd_data = "";
        if ($password){
            $pwd_data = "/P $password";
        }
        //
        $this->cmd = $this->cmd . " $user_data $pwd_data ";
    }





    // $actionfile = "/tr \"powershell -executionpolicy unrestricted -file C:\\call.ps1 -file C:\\call.ps1\"";
    // $actionfile = "/tr \"php -f C:\phpfile.php minuevoid\"";
    public function action($action){
        //
        $action_data = "";
        if ($action){
            $action_data = "/TR \"$action\"";
        }
        //
        $this->cmd = $this->cmd . " $action_data ";
    }



    //
    public function run($debug_output = false){
        // append to output
        $this->cmd = $this->cmd . " 2>&1";
        // $runCMD = "$schtasks /create /tn mynewtask /tr mynewtask.exe /sc DAILY /st 07:00 /f /RI 60 /du 24:00 /RU Administrator /RP abcd1234 2>&1";
        $output = shell_exec($this->cmd);
        //
        if ($debug_output){
            echo( '<pre>' );
            echo( $output );
            echo( '</pre>' );
        }
        //
        return $output;
    }



    //
    public function helperFindTaskByName($output, $task_name){
        $pos = strpos($output, $task_name);
        //
        if ($pos === false) {
            return false;
            //echo "La cadena '$task_id' no fue encontrada en la busqueda";
        } else {
            return true;
            //echo "La cadena '$task_id' fue encontrada en la busqueda";
            //echo " y existe en la posicion $pos";
        }
    }



}





















//
function ScheduleTask($options){
    //var_dump($options); exit;


    // "Ivan-PC\\Administrator";
    $taks_scheduler_username = $options['username'];
    $taks_scheduler_pwd = (isset($options['password']) && $options['password']) ? $options['password'] : null;


    //
    $scheduler_timezone = false;

    // syncronize time zone to server
    if ( isset($options['scheduler_timezone']) && $options['scheduler_timezone'] ){
        $scheduler_timezone = $options['scheduler_timezone'];
    }


    // task type
    $task_type = "ONCE";




    //  minuevoid
    $php_main_scheduler_index_file = PATH_BASE.DS.'scheduler'.DS.'index.php';
    // parametro 1 siempre sera el task id, otros parametros son para uso
    $php_exec_params = " '".$options['task_id']."' '".$options['handler_file']."' ";

    //
    if ( is_array($options['additional_params']) && count($options['additional_params']) > 0){
        foreach($options['additional_params'] as $additional_param){
            $php_exec_params .= " '$additional_param' ";
        }
    }
    //echo $php_exec_params; exit;

    // no aplica si tenemos de tipo 'ONCE' y generara un error
    $delete_after_final_run = false;

    //
    $sch = new scheduler($scheduler_timezone);

    // LISTA TAREAS PARA BUSCAR SI YA EXISTE
    $sch->query('csv', true);


    //$sch->debug(); exit;
    $search_results = $sch->run();
    //var_dump($search_results); exit;


    $results = array();
    $results['error'] = null;



    //
    if ( $sch->helperFindTaskByName($search_results, $options['task_id']) ){
        $results['error'] = "Ya existe la tarea ".$options['task_id'];
    }


    else {
        //


        //echo "No existe la tarea $task_id, creando..." ;

        //
        $sch->create($options['task_id']);

        // MINUTE, HOURLY, DAILY, WEEKLY, MONTHLY, ONCE, ONLOGON, ONIDLE, and ONEVENT.
        $sch->type($task_type);



        // not applicable for the following schedule types: ONSTART, ONLOGON, ONIDLE, and ONEVENT.
        //echo $options['start_date']; exit;
        if (is_numeric($options['start_date'])){
            $sch->startDate(false, $options['start_date']." days");
        } else {
            $sch->startDate($options['start_date']);
        }
        //$sch->startDate($start_date);


        // applicable for the following schedule types: ONCE, ONSTART, ONLOGON, ONIDLE, and ONEVENT.
        //$sch->endDate(false, "2 days");
        //$sch->endDate();



        // $sch->startTime(false, "+2 hour +5 minutes");
        $sch->startTime($options['start_time']);





        // Will add aditional params
        // /F /RI 1 /DU 24:00
        // $sch->additional("/Z");



        // delete when done?
        if ($delete_after_final_run){
            $sch->deleteAfterFinalRun();
        }

        //
        $php_extensions_to_use = $options['php_extensions_to_use'];

        $str_cmmmand = "php $php_extensions_to_use -f $php_main_scheduler_index_file $php_exec_params";
        //echo $str_cmmmand; exit;

        //
        $sch->action($str_cmmmand);
        $sch->auth($taks_scheduler_username, $taks_scheduler_pwd);


        //
        //$sch->debug(); exit;
        $create_results = $sch->run();
        //var_dump($create_results); exit;


        if ( str_contains($create_results, "SUCCESS") ){
            $results['success'] = true;
            $results['success_msg'] = "tarea '".$options['task_id']."' creada exitosamente";
        } else {

            //
            if ( str_contains($create_results, "Invalid argument") ){
                $results['error'] = "error en los argumentos";
            }
            else if ( str_contains($create_results, "Task may not run because /ST is earlier than current time") ) {
                $results['error'] = "la tarea se creo pero podria no ejecutarse debido a que la hora es menor a la hora actual";
            }
            else {
                $results['error'] = $create_results;
            }
        }


    }

    //
    return $results;
}

