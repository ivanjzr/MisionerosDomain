<?php
namespace Middleware;


//
use App\Languages;


//
class SiteLangHandler
{





    public $section_name;
    public $app_id;



    //
    public function __construct($section_name, $app_id = null)
    {
        $this->section_name = $section_name;
        $this->app_id = $app_id;
    }






    //
    public function __invoke($request, $response, $next)
    {



        // get route info & lang param
        $lang_code = $request->getAttribute('routeInfo')[2]["lang"];
        //echo $lang_code; exit;

        /*
         * Get Lang Info
         * */
        $lang_info = Languages::GetLangByCode($lang_code);
        //var_dump($lang_info); exit;


        /*
         * Si no tenemos Lang Info obtenemos el default
         * */
        if ( !(isset($lang_info["id"]) && $lang_info["id"] > 0) ){
            $lang_info = Languages::GetDefaultLang();
            //var_dump($lang_info); exit;
        }
        //
        $request = $request->withAttribute('lang_info', $lang_info);
        $request = $request->withAttribute('app_id', $this->app_id);


        //
        $response = $next($request, $response);
        return $response;
    }






}