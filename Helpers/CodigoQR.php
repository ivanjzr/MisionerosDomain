<?php
namespace Helpers;

//
use Endroid\QrCode\QrCode;

//
class CodigoQR
{


    //
    public static function Generar($path, $text){


        // Create a basic QR code
        $qrCode = new QrCode();
        $qrCode
            ->setText($text)
            ->setSize(200)
            ->setLabelFontSize(16);

        // Save it to a file
        $qrCode->writeFile($path);
    }


}
