<?php
namespace App;





//
use Aws\Comprehend\ComprehendClient;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;

class AwsConfig
{




    // Amazon S3 API credentials
    public static $region = 'us-west-2';
    public static $version = 'latest';
    public static $access_key_id = '';
    public static $secret_access_key = '';
    public static $bucket_name = '';
    //
    public static $api_gwy_ws_endpoint = "";


    // Palabras prohibidas
    public static $arr_bad_words = ["culo", "culon", "culito", "joto", "marica", "maricon", "perro", "verga", "mamon", "chinga", "chinga tu madre", "culero", "puto"];




    public static function checkImagenApropiada($image_path){

        //
        $rekognitionClient = new RekognitionClient([
            'version' => 'latest',
            'region' => self::$region,
            'credentials' => new Credentials(self::$access_key_id, self::$secret_access_key)
        ]);

        $params = [
            'Image' => [
                'Bytes' => file_get_contents($image_path),
            ],
            'MinConfidence' => 75.0,
        ];

        try {

            //
            $result = $rekognitionClient->detectModerationLabels($params);
            //Helper::printFull($result); exit;

            // Establece un umbral de confianza personalizado
            $umbralConfianza = 90; // Puedes ajustar este valor según tus necesidades


            //
            $inapropiate_label = null;

            // Recorre las etiquetas de moderación detectadas
            foreach ($result['ModerationLabels'] as $label) {
                if ($label['Confidence'] >= $umbralConfianza) {
                    // Si alguna etiqueta supera el umbral de confianza, considera la imagen inapropiada
                    $inapropiate_label = $label['Name'];
                    break;
                }
            }

            //
            if ($inapropiate_label){
                return ['label' => $inapropiate_label];
            }

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
        //
        return null;
    }












    public static function checkTextoInapropiado($textToAnalyze){



        // Configura el cliente de AWS Comprehend
        $client = new ComprehendClient([
            'version' => '2017-11-27',
            'region' => self::$region,
            'credentials' => new Credentials(self::$access_key_id, self::$secret_access_key)
        ]);



        // Parámetros para detectar entidades
        $params = [
            'Text' => $textToAnalyze,
            'LanguageCode' => 'es'
        ];



        /**
        Alto umbral (por ejemplo, 0.9 o superior): Si deseas ser muy restrictivo y evitar la mayor cantidad posible de falsos positivos, puedes considerar un umbral alto, como 0.9. Esto significa que solo bloquearás contenido con una alta confianza de ser negativo, lo que reduce la probabilidad de errores, pero puede permitir que algunos contenidos negativos pasen desapercibidos.

        Umbral moderado (por ejemplo, 0.5 a 0.8): Un umbral moderado proporciona un equilibrio entre precisión y tolerancia. Puedes optar por un rango de confianza de 0.5 a 0.8. Esto bloqueará contenido negativo con una confianza moderada, lo que puede reducir los falsos positivos pero aún captura la mayoría del contenido negativo.

        Bajo umbral (por debajo de 0.5): Un umbral bajo permite que pase la mayoría del contenido, incluso si no está seguro de su tono. Esto es útil si deseas ser muy permisivo y solo bloquear contenido obviamente ofensivo.
         */
        //
        $umbralConfianza = 0.8;



        try {


            $inapropiate_text = false;


            // Llama a la operación DetectSentiment
            $result = $client->detectSentiment($params);
            //Helper::printFull($result); exit;




            //
            $sentiment = ucfirst(strtolower($result['Sentiment']));
            $confidence = $result['SentimentScore'][$sentiment];

            //
            if ($sentiment === 'Negative' && $confidence > $umbralConfianza ) {
                $inapropiate_text = true;
            }

            //
            if ($inapropiate_text){
                return ['text' => "Texto inaproipado"];
            }


            /*
            //
            $result = $client->detectEntities($params);
            Helper::printFull($result); exit;
            // Imprime las entidades detectadas
            foreach ($result['Entities'] as $entity) {
                if ($entity['Score'] >= $umbralScore) {
                    //
                    echo 'Tipo de entidad: ' . $entity['Type'] . "\n";
                    echo 'Texto de entidad: ' . $entity['Text'] . "\n";
                    echo 'Comienzo: ' . $entity['BeginOffset'] . "\n";
                    echo 'Fin: ' . $entity['EndOffset'] . "\n\n";
                    //
                    $inapropiate_text = $entity['Text'];
                    break;
                }
            }
            */


        } catch (AwsException $e) {
            return ['error' => $e->getMessage()];
        }
        //
        return null;
    }





}
