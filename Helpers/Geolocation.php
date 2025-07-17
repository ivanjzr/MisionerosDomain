<?php
namespace Helpers;




class Geolocation {


    public static function getGeoCodeLocation($lat, $lng){
        // set your API key here
        $google_api_key = "AIzaSyDs8GGqDC4sxqI5I1wdaThKnd3oTIstsfo";
        // format this string with the appropriate latitude longitude
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key=" . $google_api_key;
        // make the HTTP request
        $data = @file_get_contents($url);
        // parse the json response
        $jsondata = json_decode($data, true);
        //Helper::printFull($jsondata); exit;

        // if we get a placemark array and the status was good, get the addres
        if( is_array($jsondata) && $jsondata['status'] === "OK" ){
            //
            //Helper::printFull($jsondata['results'][0]); exit;
            return self::parseLocationData($jsondata['results'][0]);
        }
    }
    //
    public static function parseLocationData($results__value){
        //
        $return = [
            //'type' => "GoogleGeoCode",
            //'zip' => null,
            //'street' => null,
            //'number' => null,
            //'country_name' => null,
            'lat' => null,
            'lng' => null,
            'country_code' => null,
            'state' => null,
            'city' => null,
            'formatted_address' => null
        ];
        //
        if ($results__value['geometry']['location']['lat'] !== '') {
            $return['lat'] = $results__value['geometry']['location']['lat'];
        }
        if (@$results__value['geometry']['location']['lng'] != '') {
            $return['lng'] = $results__value['geometry']['location']['lng'];
        }
        if (@$results__value['formatted_address'] != '') {
            $return['formatted_address'] = $results__value['formatted_address'];
        }
        //
        if (
            @$results__value['address_components'] != '' &&
            !empty(@$results__value['address_components'])
        ) {
            foreach (@$results__value['address_components'] as $address_components__value){
                if (@$address_components__value['types'][0] === 'country'){
                    //$return['country_name'] = $address_components__value['long_name'];
                    $return['country_code'] = $address_components__value['short_name'];
                }
                if (@$address_components__value['types'][0] === 'administrative_area_level_1'){
                    $return['state'] = $address_components__value['long_name'];
                }
                if (@$address_components__value['types'][0] === 'locality'){
                    $return['city'] = $address_components__value['long_name'];
                }
                if (@$address_components__value['types'][0] === 'postal_code'){
                    //$return['zip'] = $address_components__value['short_name'];
                }
                if (@$address_components__value['types'][0] === 'route'){
                    //$return['street'] = $address_components__value['long_name'];
                }
                if (@$address_components__value['types'][0] === 'street_number'){
                    //$return['number'] = $address_components__value['short_name'];
                }
            }
        }
        //
        return $return;
    }



    //
    public static function getGeoLocationDB($use_google_geo_code = false){
        $json = file_get_contents('https://geolocation-db.com/json');
        $data = json_decode($json, true);
        //
        /*
       country_code
       country_name
       state
       city
       postal
       latitude
       longitude
       IPv4
       */
        //
        if ($data && $data['latitude'] && $data['longitude']){
            //
            if ($use_google_geo_code){
                return self::getGeoCodeLocation($data['latitude'], $data['longitude']);
            } else {
                $data['type'] = "GeoLocationDB";
                return $data;
            }
        }
        return null;
    }




}

