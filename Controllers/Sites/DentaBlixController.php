<?php
//
namespace Controllers\Sites;

//
use App\Catalogues\CatCategories;
use App\Site\SiteConfig;
use App\Stores\Stores;
use Controllers\BaseController;
use App\App;
use App\Config\ConfigSquare\ConfigSquare;
use Helpers\Helper;
use Helpers\Query;
use Helpers\QboHelper;

use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use  QuickBooksOnline\API\Exception\ServiceException;



$appId = 1234;

//
class DentaBlixController extends BaseController
{




    public static function getCustomerInfo(){
        //
        $qbo_res = QboHelper::getLastToken();
        //dd($qbo_res); exit;
        if ( isset($qbo_res['error']) && $qbo_res['error'] ){
            return $response->withJson($qbo_res, 400);
        }
        //
        $realm_id = $qbo_res['realm_id'];
        $access_token = $qbo_res['access_token'];
        $refresh_token = $qbo_res['refresh_token'];



        //
        $config = QboHelper::getQbOApiCallsConfig($realm_id, $access_token, $refresh_token);
        //dd($config); exit;
        $dataService = DataService::Configure($config);



        //$dataService->setLogLocation("/Users/hlu2/Desktop/newFolderForLog");



        /*
        $allCompanies = $dataService->FindAll('CompanyInfo');
        dd($allCompanies); exit;
        foreach ($allCompanies as $oneCompany) {
            $oneCompanyReLookedUp = $dataService->FindById($oneCompany);
            echo "Company Name: {$oneCompanyReLookedUp->CompanyName}\n";
        }
        */

        //
        $companyInfo = $dataService->getCompanyInfo();
        dd($companyInfo); exit;

    }




    //
    public function ViewAll2($request, $response, $args) {

        //
        $arr_content = [];
        //echo hostUrl; exit;


        $account_id = 12;
         //
         $arr_content['config'] = ConfigSquare::getConfig($account_id);
         //dd($config); exit;
        

        //
        return $this->container->php_view->render($response, 'sites/plabuz/index.phtml', array_merge([
            //
            "App" => new App("en-us"),
            "appId" => appId,
            "hostUrl" => hostUrl,
            "appName" => appName,
            "fbAppId" => fbAppId,
            "site_config" => SiteConfig::GetRecordById(appId)
        ], $arr_content));
    }



    //
    public function ViewAll($request, $response, $args) {



        echo "test"; exit;
        //
        $arr_content = [];
        echo hostUrl; exit;


        //
        if ( appId === 11 ){
            //
            $page_title = "MissionExpress";
            $meta_description = "Welcome to MissionExpress";
            $meta_author = "info@missionexpress.us";
            //
            $og_img_url = "https://missionexpress.us/images/missionexpress-og-image.png?v=5.1";
            $page_url = "https://missionexpress.us/";
            $description = "*Welcome to MissionExpress";
        }
        //
        else if ( appId === 13 ){
            //
            $page_title = "Plabuz";
            $meta_description = "Welcome to Plabuz";
            $meta_author = "info@plabuz.com";
            //
            $og_img_url = "https://plabuz.com/images/plabuz-og-image.png?v=5.1";
            $page_url = "https://plabuz.com/";
            $description = "*Welcome to Plabuz";
        }
        //
        else if ( appId === 16 ){
            //
            $page_title = "Tickets4Buses";
            $meta_description = "Welcome to Tickets4Buses";
            $meta_author = "info@tickets4buses.com";
            //
            $og_img_url = "https://tickets4buses.com/images/missionexpress-og-image.png?v=5.1";
            $page_url = "https://tickets4buses.com/";
            $description = "*Welcome to Tickets4Buses";
        }

        
        //echo appId . " $page_title, $meta_description, $meta_author, $og_img_url, $page_url, $description "; exit;



        //
        $og_type = "website";

        //
        $arr_content['seo_meta_tags'] = "
<title>$page_title - $description</title>
<meta name='description' content='$meta_description'>
<meta name='author' content='$meta_author'>
<meta property='og:title' content='$page_title'>
<meta property='og:description' content='$description'>
<meta property='og:url' content='$page_url'>
<meta property='og:type' content='$og_type'>
<meta property='fb:app_id' content='".fbAppId."'>
<meta property='og:image' content='$og_img_url'>
<meta property='og:image:url' content='$og_img_url'>
<meta property='og:image:secure_url' content='$og_img_url' />
<meta property='og:image:type' content='image/png' />
<meta property='og:image:width' content='1200' />
<meta property='og:image:height' content='630' />
";


        //
        return self::renderCommonContent($this->container, $request, $response, 'index.html', $arr_content);
    }





    public function ViewCreateAccount($request, $response, $args) {



        //
        $arr_content = [];
        //echo hostUrl; exit;


         //
         if ( appId === 11 ){
            //
            $page_title = "MissionExpress";
            $meta_description = "Welcome to MissionExpress";
            $meta_author = "info@missionexpress.us";
            //
            $og_img_url = "https://missionexpress.us/images/missionexpress-og-image.png?v=5.1";
            $page_url = "https://missionexpress.us/";
            $description = "*Welcome to MissionExpress";
        }
        //
        else if ( appId === 13 ){
            //
            $page_title = "Plabuz";
            $meta_description = "Welcome to Plabuz";
            $meta_author = "info@plabuz.com";
            //
            $og_img_url = "https://plabuz.com/images/plabuz-og-image.png?v=5.1";
            $page_url = "https://plabuz.com/";
            $description = "*Welcome to Plabuz";
        }
        //
        else if ( appId === 16 ){
            //
            $page_title = "Tickets4Buses";
            $meta_description = "Welcome to Tickets4Buses";
            $meta_author = "info@tickets4buses.com";
            //
            $og_img_url = "https://tickets4buses.com/images/missionexpress-og-image.png?v=5.1";
            $page_url = "https://tickets4buses.com/";
            $description = "*Welcome to Tickets4Buses";
        }



        //
        $og_type = "website";

        //
        $arr_content['seo_meta_tags'] = "
<title>$page_title - $description</title>
<meta name='description' content='$meta_description'>
<meta name='author' content='$meta_author'>
<meta property='og:title' content='$page_title'>
<meta property='og:description' content='$description'>
<meta property='og:url' content='$page_url'>
<meta property='og:type' content='$og_type'>
<meta property='fb:app_id' content='".fbAppId."'>
<meta property='og:image' content='$og_img_url'>
<meta property='og:image:url' content='$og_img_url'>
<meta property='og:image:secure_url' content='$og_img_url' />
<meta property='og:image:type' content='image/png' />
<meta property='og:image:width' content='1200' />
<meta property='og:image:height' content='630' />
";


        //
        return self::renderCommonContent($this->container, $request, $response, 'index.html', $arr_content);
    }





    public function ViewCreateAccountStore($request, $response, $args) {



        //
        $arr_content = [];
        //echo hostUrl; exit;


        //
        if ( appId === 2 ){
            //
            $page_title = "Qponea";
            $meta_description = "Descuentos irresistibles en tu bolsillo! ¡Ahorra como nunca antes!";
            $meta_author = "dev@qponea.com";
            //
            $og_img_url = "https://qponea.com/images/Qponea-og-image.png?v=5";
            $page_url = "https://qponea.com/";
            $description = "Te llevamos donde están las Ofertas";
        }
        //
        else if ( appId === 10 ){
            //
            $page_title = "Pedidero";
            $meta_description = "¡Ordena, recoge y ahorra tiempo! Tu pedido listo antes de que puedas decir 'hambre'";
            $meta_author = "dev@pedidero.com";
            //
            $og_img_url = "https://pedidero.com/images/Pedidero-og-image.png?v=5.1";
            $page_url = "https://pedidero.com/";
            $description = "Anticipa Tu Pedido";
        }
        //
        else if ( appId === 11 ){
            //
            $page_title = "YonkeParts";
            $meta_description = "Crea tu Cuenta para Negocio";
            $meta_author = "info@yonkeparts.com";
            //
            $og_img_url = "https://YonkeParts.com/images/YonkeParts-og-image.png?v=5.1";
            $page_url = "https://YonkeParts.com/";
            $description = "Crea tu Cuenta para Negocio!";
        }



        //
        $og_type = "website";

        //
        $arr_content['seo_meta_tags'] = "
<title>$page_title - $description</title>
<meta name='description' content='$meta_description'>
<meta name='author' content='$meta_author'>
<meta property='og:title' content='$page_title'>
<meta property='og:description' content='$description'>
<meta property='og:url' content='$page_url'>
<meta property='og:type' content='$og_type'>
<meta property='fb:app_id' content='".fbAppId."'>
<meta property='og:image' content='$og_img_url'>
<meta property='og:image:url' content='$og_img_url'>
<meta property='og:image:secure_url' content='$og_img_url' />
<meta property='og:image:type' content='image/png' />
<meta property='og:image:width' content='1200' />
<meta property='og:image:height' content='630' />
";


        //
        return self::renderCommonContent($this->container, $request, $response, 'index.html', $arr_content);
    }




    //$arr_content['coupon_info'] = CouponsCoupons::GetCouponCouponById($coupon_id);




    //
    public function ViewStoreInfo($request, $response, $args) {


        //
        $arr_content = [];


        //
        $store_id = $args['store_id'];
        $store_url = $args['store_url'];
        //echo " $store_id $store_url "; exit;


        //
        $store_info = Query::Single("Select t.* From ViewStores t Where t.id = ?", [
            $store_id
        ], function(&$row){
            //
            if ( $biz_logo = Stores::getStoreLogo($row['id'], $row['img_ext'])){
                $row['biz_logo'] = $biz_logo;
                unset($row['img_ext']);
            }
        });



        //
        if ( appId === 2 ){
            //
            $page_title = "Qponea - " . $store_info['company_name'] . " - " . $store_info['store_title'];
            $meta_description = "Descuentos irresistibles en tu bolsillo! ¡Ahorra como nunca antes!";
            $meta_author = "Qponea";
            //
            $og_img_url = "https://qponea.com/images/Qponea-og-image.png?v=5";
            $page_url = "https://qponea.com" . Stores::getStoreUrl($store_info['id'], $store_info['company_name'], $store_info['store_title']);
            $og_title = $page_title . " ya esta en Qponea";
        }
        //
        else if ( appId === 10 ){
            //
            $page_title = "Pedidero - " . $store_info['company_name'] . " - " . $store_info['store_title'];
            $meta_description = "¡Ordena, recoge y ahorra tiempo! Tu pedido listo antes de que puedas decir 'hambre'";
            $meta_author = "dev@pedidero.com";
            //
            $og_img_url = "https://pedidero.com/images/Pedidero-og-image.png?v=5";
            $page_url = "https://pedidero.com" . Stores::getStoreUrl($store_info['id'], $store_info['company_name'], $store_info['store_title']);
            $og_title = $page_title . " ya esta en Pedidero";
        }
        //
        else if ( appId === 11 ){
            //
            $page_title = "YonkeParts - " . $store_info['company_name'] . " - " . $store_info['store_title'];
            $meta_description = "MarketPlace de AutoPartes";
            $meta_author = "info@yonkeparts.com";
            //
            $og_img_url = "https://YonkeParts.com/images/YonkeParts-og-image.png?v=5";
            $page_url = "https://YonkeParts.com" . Stores::getStoreUrl($store_info['id'], $store_info['company_name'], $store_info['store_title']);
            $og_title = $page_title . " ya esta en YonkeParts";
        }

        $og_type = "website";

        //
        $arr_content['seo_meta_tags'] = "
<title>$page_title</title>
<meta name='description' content='$meta_description'>
<meta name='author' content='$meta_author'>
<meta name='description' content='Descuentos irresistibles en tu bolsillo! ¡Ahorra como nunca antes!'>
<meta property='og:title' content='$og_title'>
<meta property='og:description' content='Explora sus ofertas exclusivas y ahorra al máximo'>
<meta property='og:url' content='$page_url'>
<meta property='og:type' content='$og_type'>
<meta property='fb:app_id' content='".fbAppId."'>
<meta property='og:image' content='$og_img_url'>
<meta property='og:image:url' content='$og_img_url'>
<meta property='og:image:secure_url' content='$og_img_url' />
<meta property='og:image:type' content='image/png' />
<meta property='og:image:width' content='1200' />
<meta property='og:image:height' content='630' />
";





        //
        return self::renderCommonContent($this->container, $request, $response,  'index.html', $arr_content);
    }



    public function ViewStoresMain($request, $response, $args) {




        //
        $arr_content['qponea_img_url'] = "https://qponea.com/images/Qponea-og-image.png?v=5";
        $arr_content['og_store_page_url'] = "https://qponea.com/stores";
        $arr_content['og_title'] = "Qponea - Negocios";
        $arr_content['og_description'] = "Impulsa tus Ventas a través de Cupones Digitales";
        $arr_content['og_type'] = "website";



        //
        return self::renderCommonContent($this->container, $request, $response,  'index.html', $arr_content);
    }



    //
    public function ViewRegisterStore($request, $response, $args) {


        //
        $arr_content = [];

        //
        $arr_content['qponea_img_url'] = "https://qponea.com/images/Qponea-og-image.png?v=5";
        $arr_content['og_store_page_url'] = "https://qponea.com/stores/create-account";
        $arr_content['og_title'] = "Para Negocios";
        $arr_content['og_description'] = "Crea tu cuenta y expande tu negocio!";
        $arr_content['og_type'] = "website";


        //
        return self::renderCommonContent($this->container, $request, $response,  'index.html', $arr_content);
    }




    //
    public function ViewRegisterCustomer($request, $response, $args) {

        //
        $arr_content = [];



        //
        $arr_content['qponea_img_url'] = "https://qponea.com/images/Qponea-og-image.png?v=5";
        $arr_content['og_store_page_url'] = "https://qponea.com/create-account";
        $arr_content['og_title'] = "Para Cuponeros";
        $arr_content['og_description'] = "Crea tu cuenta para obtener cupones es gratis!";
        $arr_content['og_type'] = "website";



        //
        return self::renderCommonContent($this->container, $request, $response,  'index.html', $arr_content);
    }










    //
    public static function renderCommonContent($container, $request, $response, $view_name,  &$arr_content = []){

        //
        $lang_info = $request->getAttribute("lang_info");
        //var_dump($lang_info); exit;

        //
        return $container->twig_view->render($response, PATH_REL_VIEWS.DS.$view_name, array_merge([
            //
            "App" => new App($lang_info),
            "appId" => appId,
            "hostUrl" => hostUrl,
            "appName" => appName,
            "fbAppId" => fbAppId,
            "site_config" => SiteConfig::GetRecordById(appId)

        ], $arr_content));
    }


}
