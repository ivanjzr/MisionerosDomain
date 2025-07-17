<?php
namespace App;

use App\Auth\AuthTokens;
use Helpers\Query;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class RatchetWebsocket implements MessageComponentInterface {

    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }



    //
    public function checkToken($conn){
        //
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring,$queryarray);
        //
        if ( isset($queryarray['token']) && $queryarray['token'] && isset($queryarray['utype']) && $queryarray['utype'] ){
            //
            $api_key = base64_decode($queryarray['token']);
            $utype = (int)$queryarray['utype'];
            //
            return AuthTokens::GetAccountByToken($utype, $api_key);
        }
        return null;
    }


    public function onOpen(ConnectionInterface $conn) {
        //
        $resource_id = $conn->resourceId;


        $results = self::checkToken($conn);
        //var_dump($results);

        if ( $results && isset($results['id']) ){

            //
            $user_id = $results['id'];
            $utype = (int)$results['utype'];


            $str_table = "";
            if ( $utype === PROD_TYPE_STORE_ID ){
                $str_table = "companies";
            }
            else if ( $utype === PROD_TYPE_CUSTOMER_ID ){
                $str_table = "workers";
            }

            //
            $update_results = Query::DoTask([
                "task" => "update",
                "debug" => true,
                "stmt" => "
                   Update 
                    $str_table
                  Set 
                    --
                    ws_resource_id = ?
                    
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ",
                "params" => [
                    $resource_id,
                    //
                    $user_id
                ],
                "parse" => function($updated_rows, &$query_results) use($user_id){
                    $query_results['affected_rows'] = $updated_rows;
                    $query_results['id'] = (int)$user_id;
                }
            ]);
            //var_dump($update_results); exit;
            $affected_rows = 0;
            if ( $update_results && isset($update_results['affected_rows']) ){
                $affected_rows = $update_results['affected_rows'];
            }


            /*
             *
             * ATTACH ONLY AUTH USERS
             *
             * */
            $this->clients->attach($conn);


            //$conn->send("que pasion " . $resource_id);

            //
            echo "---Con Ok!, $str_table id " . $user_id . ", affected_rows: " . $affected_rows . ", res_id ({$resource_id})\n";
            return;
        }
        //
        echo "---Err, Unable to Connect, detaching res_id ({$resource_id})\n";
        $this->user_data = null;
        $this->clients->detach($conn);
    }

    //
    public function onMessage(ConnectionInterface $conn, $msg) {
        //
        $arr_data = json_decode($msg, true);
        //var_dump($arr_data);
        //
        if ( $arr_data && isset($arr_data['msg']) && isset($arr_data['type']) ){
            //
            $results = self::checkToken($conn);
            //Helper::printFull($results);
            //
            if ( $results && isset($results['id']) ){

                // handle HireTruckers type
                if ( $arr_data['type'] === "ht-company" || $arr_data['type'] === "ht-worker" ){

                    //
                    $this->handleHireTruckersMsg($conn, $results, $arr_data);

                }
                // handle other type
                else if ( $arr_data['type'] === "other" ){

                    /* implement other type */

                }

            }
            else {
                echo "---Error, user with res_id " . $conn->resourceId . " not auth \n"; return;
            }
        }
        //
        else {
            echo "---Error, empty msg \n"; return;
        }
    }


    //
    public function handleHireTruckersMsg( $conn, $user_data, $arr_data ){
        //$numRecv = count($this->clients) - 1;

        //
        $type = $arr_data['type'];
        $dest_id = $arr_data['dest_id'];
        $job_application_id = $arr_data['job_application_id'];
        $msg = $arr_data['msg'];


        // DEBUG DATA
        //echo "---type: $type, msg: $msg \n";
        //var_dump($user_data); exit;
        //
        $app_id = $user_data['app_id'];
        $user_id = $user_data['id'];
        //
        echo "Handling socket for {$type} with id {$user_id} and ResId {$conn->resourceId} \n";

        //
        $application_msg_id = $this->insertMsg($type, $app_id, $user_id, $dest_id, $job_application_id, $msg);
        $destination_client = $this->getDestinationClient($type, $dest_id);

        //
        $msg_res = Query::Single("Select t.* From jobs_applications_messages t Where t.app_id = ? And id = ?", [$app_id, $application_msg_id]);

        //
        if ($msg_res && isset($msg_res['id'])){

            // Envia al user actual
            echo "---Sending to origin \n";
            $conn->send(json_encode($msg_res));


            // Envia al user destino
            if ($destination_client){
                echo "---Sending to destination \n";
                $destination_client->send(json_encode($msg_res));
            }
        }
    }


    //
    public function getDestinationClient($type, $dest_id){
        //
        $clientFound = null;
        $res = null;
        $str_msg = "---Searching user type ";
        //
        if ( $type === "ht-company" ){
            $str_msg .=  "worker with id " . $dest_id;
            $res = Query::Single("Select * From v_customers Where id = ?", [$dest_id] );
        }
        //
        else if ( $type === "ht-worker" ){
            $str_msg .=  "company with id " . $dest_id;
            $res = Query::Single("Select * From ViewCompanies Where id = ?", [$dest_id] );
        }
        //var_dump($res); exit;
        if ( $res && isset($res['ws_resource_id']) && $res['ws_resource_id'] ){

            //
            $resourceId = (int)$res['ws_resource_id'];
            $str_msg .= ", found Ok with res_id " . $resourceId . " \n";

            //
            foreach ($this->clients as $client) {
                echo "--iterating Ok ->resourceId: {$client->resourceId}, userResourceId: {resourceId} \n";
                //
                if ( $client->resourceId == $resourceId ){
                    $clientFound = $client;
                    break;
                }
            }
        } else {
            $str_msg .= ", res_id not found \n";
        }
        echo $str_msg;
        return $clientFound;
    }

    //
    public function insertMsg($type, $app_id, $user_id, $dest_id, $job_application_id, $msg){
        //
        $insert_results = null;
        //
        if ( $type === "ht-company" ){

            // $user_data = company
            // $dest_id = worker_id

            //
            $insert_results = Query::DoTask([
                "task" => "add",
                "debug" => true,
                "stmt" => "
                    Insert Into jobs_applications_messages
                    ( app_id, job_application_id, company_id, worker_id, msg_by_company_id, message, datetime_created )
                    Values
                    ( ?, ?, ?, ?,  ?, ?, GETDATE() )
                    ;SELECT SCOPE_IDENTITY()
                ",
                "params" => [
                    $app_id,
                    $job_application_id,
                    $user_id,
                    $dest_id,
                    //
                    $user_id,
                    $msg
                ],
                "parse" => function($insert_id, &$query_results){
                    $query_results['id'] = (int)$insert_id;
                }
            ]);
        }
        //
        else if ( $type === "ht-worker" ){

            // $user_data = worker
            // $dest_id = company_id

            //
            $insert_results = Query::DoTask([
                "task" => "add",
                "debug" => true,
                "stmt" => "
                    Insert Into jobs_applications_messages
                    ( app_id, job_application_id, worker_id, company_id, msg_by_worker_id, message, datetime_created )
                    Values
                    ( ?, ?, ?, ?, 
                    ?, ?, GETDATE() )
                    ;SELECT SCOPE_IDENTITY()          
                ",
                "params" => [
                    $app_id,
                    $job_application_id,
                    $user_id,
                    $dest_id,
                    //
                    $user_id,
                    $msg
                ],
                "parse" => function($insert_id, &$query_results){
                    $query_results['id'] = (int)$insert_id;
                }
            ]);
        }
        //
        if ( $insert_results && isset($insert_results['error']) && $insert_results['error'] ){
            echo "---Error insert msg " . $insert_results['error'] . "\n";
        }
        else if ( $insert_results && isset($insert_results['id']) && $insert_results['id'] ){
            echo "---Insert Msg Id " . $insert_results['id'] . " Ok \n";
            return $insert_results['id'];
        }
        //
        return null;
    }


    //
    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        //
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    //
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

}