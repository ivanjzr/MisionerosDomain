<?php
namespace Helpers;




class TwigParser{






    //
    public static function render($html, $data, $template_name = "tmpl"){
        //
        $twig = new \Twig_Environment();
        $twig->setLoader( new \Twig_Loader_Array( [ $template_name => $html ] ));
        //
        return $twig->render($template_name, $data);
    }



}