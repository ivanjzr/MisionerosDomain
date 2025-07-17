<?php
namespace Helpers;

//
use mikehaertl\wkhtmlto\Pdf;
use mikehaertl\wkhtmlto\Image;


//
class BuildPdf{




    
    




    //
    public static function Build($title, $inline_css, $additional_css_styles, $header_data, $arr_content, $pdf_file_path, $margins = null, $options = array()){



        // $title, $inline_css, $additional_css_styles, $header_data, $arr_content, $pdf_file_path, $margins = null, $options = array()


        //
        $header_html_data = <<<EOF
            <!DOCTYPE html>
            <html lang="en-us">
            </head>
            <body style="padding:0;margin:0;font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;">
                $header_data
                <br />
            </body>
            </html>
EOF;
        //echo $header_html_file; exit;




        //
        $pdf_css_path = PATH_PUBLIC.DS.'pdf.css';



        /*
         *
         * https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
         * https://github.com/mikehaertl/phpwkhtmltopdf
         *
         * */
        $pdf_options = [

            'no-outline',         // Make Chrome not complain

            'margin-top'    => (isset($margins) && isset($margins['top']) ? $margins['top']: 0),
            'margin-right'  => (isset($margins) && isset($margins['right']) ? $margins['right']: 0),
            'margin-bottom' => (isset($margins) && isset($margins['bottom']) ? $margins['bottom']: 0),
            'margin-left'   => (isset($margins) && isset($margins['left']) ? $margins['left']: 0),

            'commandOptions' => [
                'useExec' => true
            ],

            'viewport-size' => "1280x1024",
            'header-html' => $header_html_data,
            'footer-font-size' => 8,
            'footer-left' => "[webpage]",
            'footer-center' => "[date] - [time]",
            'footer-right' => "[page]/[toPage]",

            /* Portrait  Landscape */
            'orientation'   => ($options && isset($options) && isset($options['orientation']) ? $options['orientation']: "Portrait"),

            /*'disable-smart-shrinking',*/
            /*'footer-html' => $footer_html_data,*/
            /*,'user-style-sheet' => $pdf_css_path,*/
        ];

        //
        if ( isset($options['disable_smart_shrinking']) && $options['disable_smart_shrinking'] ){
            array_push($options, 'disable-smart-shrinking');
        }






        //
        $pdf = new Pdf($pdf_options);

        //
        $pdf->setOptions(['ignoreWarnings'=>true]);

        //
        $pdf->binary = 'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe';







        /*
         *
         * HEADER CONTENT
         *
         * */
        if ( $arr_content && is_array($arr_content) && count($arr_content) > 0 ){
            //
            foreach($arr_content as $idx => $body_content){

                //
                $content_html = <<<EOF
            <!DOCTYPE html>
            <html lang="en-us">
            <head>
                $additional_css_styles
            <style>
                $inline_css
            </style>
            </head>
            <body>
                $body_content
            </body>
            </html>
EOF;
                //
                $pdf->addPage($content_html);

            }
        }



        //$pdf->commandOptions()
        //echo $str_html; exit;


        /*
         * Pdf File Path provided
         * */
        if ($pdf_file_path){
            if (!$pdf->saveAs($pdf_file_path)) {
                $error = $pdf->getError();
                echo $error;
            }
        }


        /*
         * Display Page
         * */
        /*
        if ($display_pdf){
            if (!$pdf->send()) {
                $error = $pdf->getError();
                echo $error; exit;
            }
        }
        */


    }









}

