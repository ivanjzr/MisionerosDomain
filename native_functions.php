<?php




/*
 *
 * https://en.wikipedia.org/wiki/Language_code
 * #1 OBTIENE EL IDIOMA SEGUN EL BROWSER
 * #2 LO TRADUCE DE FORMA GENERAL SEGUN SU CODIGO (EJEMPLO 419 = ES-MX, ETC)
 * #3 DEVUELVE EL VALOR PARA BUSCAR EN BD SEGUN CODIGO
 * */
function getBrowserLang(){
    //
    $locale = null;
    //
    if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
        //$locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }
    //echo $locale; exit;
    //
    if ( $locale && ($locale === "es_419" || $locale === "es"  || $locale === "spa" )){
        return "es-mx";
    } else {
        return "en-us";
    }
}



function getDotedfile($dotted_dir_file){
    //
    $arr_file = explode(".", $dotted_dir_file);

    // pop file name
    $filename = $dotted_dir_file;
    if ( count($arr_file) > 0 ){
        $filename = array_pop($arr_file);
    }

    // folder
    $folder = "";
    if ( count($arr_file) > 0 ){
        $folder = implode(DS, $arr_file);
    }

    //
    return strtolower($folder.DS.$filename.".php");
}


function isMobile(){
    //
    //return true;
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
        return true;
    }
    return false;
}

function logContent($log_file, $log_text, $do_echo = true){
    $data = $log_text . PHP_EOL;
    $fp = fopen($log_file, 'a');
    fwrite($fp, $data);
    if ($do_echo){
        echo $log_text;
    }
}


if (!function_exists("str_contains")){
    function str_contains($str_msg, $sarch_text){
        if (strpos($str_msg, $sarch_text) !== false) {
            return true;
        }
        return false;
    }
}





function toAmount($amount, $tipo_moneda = ""){
    return "$".$amount . ($tipo_moneda ? " (" . $tipo_moneda . ")" : "");
}


function getProtocol() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
}

function get_ip_address(){

    // Check for shared Internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IP addresses passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        // Check if multiple IP addresses exist in var
        if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if (validate_ip($ip))
                    return $ip;
            }
        }
        else {
            if (validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED']))
        return $_SERVER['HTTP_X_FORWARDED'];
    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
        return $_SERVER['HTTP_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED']))
        return $_SERVER['HTTP_FORWARDED'];

    // Return unreliable IP address since all else failed
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Ensures an IP address is both a valid IP address and does not fall within
 * a private network range.
 */
function validate_ip($ip) {

    if (strtolower($ip) === 'unknown')
        return false;

    // Generate IPv4 network address
    $ip = ip2long($ip);

    // If the IP address is set and not equivalent to 255.255.255.255
    if ($ip !== false && $ip !== -1) {
        // Make sure to get unsigned long representation of IP address
        // due to discrepancies between 32 and 64 bit OSes and
        // signed numbers (ints default to signed in PHP)
        $ip = sprintf('%u', $ip);

        // Do private network range checking
        if ($ip >= 0 && $ip <= 50331647)
            return false;
        if ($ip >= 167772160 && $ip <= 184549375)
            return false;
        if ($ip >= 2130706432 && $ip <= 2147483647)
            return false;
        if ($ip >= 2851995648 && $ip <= 2852061183)
            return false;
        if ($ip >= 2886729728 && $ip <= 2887778303)
            return false;
        if ($ip >= 3221225984 && $ip <= 3221226239)
            return false;
        if ($ip >= 3232235520 && $ip <= 3232301055)
            return false;
        if ($ip >= 4294967040)
            return false;
    }
    return true;
}



function toValidUrl($string){
    $string = preg_replace('/[^a-zA-Z0-9\-]/', ' ', $string);
    $string = preg_replace('/\s+/', ' ', $string);
    $string = trim($string);
    $string = str_replace(' ', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = strtolower($string);
    return $string;
}


//
$stripe_is_prod = true;

//
function getStripeSecretKey(){
    global $stripe_is_prod;
    //
    $stripeSecretKey = 'sk_test_51J2SyFKW5onaeGjyPGjx5t4SXp4gXvaeCxzVRCGpP7wGRHLGfUUSYKaDOnbP8ifQeYxRBwH2P0AdZWmR7afUEGpN00AXkU9RG4';
    if ( $stripe_is_prod ){
        $stripeSecretKey = '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';
    }
    return $stripeSecretKey;
}
//
function getStripePublicKey(){
    global $stripe_is_prod;
    //
    $stripePublicKey = 'pk_test_51J2SyFKW5onaeGjyjOQ7GsLxBeviREyyTx7jys2YZ1W4lxkPoXl9KBJbJZm8icfLELjfmSfP6Yl1G3WFc4nGoRKZ00XO8sHLcp';
    if ( $stripe_is_prod ){
        $stripePublicKey = 'pk_live_51J2SyFKW5onaeGjyu0fHd3n20D7B25npxWrpNfDdV9fGDiXxz9slkaxndjZXDBWFOBWX0UR1Adopk3tHbKL8R0nn00u0EdnKuM';
    }
    return $stripePublicKey;
}


function getFbId(){
    return "1600535333804375";
}

function getOnlyNumbers($cadena) {
    // Usamos preg_replace para eliminar cualquier carácter que no sea un número
    return preg_replace('/\D/', '', $cadena);
}


function dd($content, $is_exit = true){
    echo "<pre>";
    print_r($content);
    echo "</pre>";
    if ($is_exit)
        exit;
}


function cleanNumericValue($value) {
    return ($value === '' || $value === null) ? null : $value;
}


function formatCurrency($amount) {
    return round($amount, 2);
}



function generateRandomCode($length = 4) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

function searchContact($search_text){
    $str_where = "";
    if ($search_text){
        return " And (
            ( t.name like '%$search_text%' ) Or 
            ( t.email like '%$search_text%' ) Or
            ( t.phone_number like '%$search_text%' ) 
        )";
    }
    return "";
}