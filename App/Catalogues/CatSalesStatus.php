<?php
namespace App\Catalogues;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;
use Helpers\Query;


//
class CatSalesStatus
{








    //
    public static function renameStatusTitle($update_type, &$row){
        //Helper::printFull($row); exit;
        //
        if ( $update_type === "delivery"){
            //
            if ( $row['sale_status'] === "ready" ){
                $row['status_title'] = 'Ready For Delivery';
            }
            //
            else if ( $row['sale_status'] === "delivered" ){
                $row['status_title'] = 'Delivered';
            }
        }
        //
        else if ( $update_type === "pickup" ){
            //
            if ( $row['sale_status'] === "ready" ){
                $row['status_title'] = 'Ready For Pickup';
            }
            //
            else if ( $row['sale_status'] === "delivered" ){
                $row['status_title'] = 'Picked Up';
            }
        }
    }







}