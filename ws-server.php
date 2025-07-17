


<?php

// utils defines
define('DS', DIRECTORY_SEPARATOR);
define('UD', "/");

// path defines
define('PATH_PUBLIC', __DIR__);
define('PATH_BASE', __DIR__);
//
define('settings', 'App');
define("PATH_VENDOR", "vendor");
define('PATH_SCHEDULER', 'scheduler');
define("PATH_CONTROLLERS", "Controllers");
define("PATH_HELPERS", "Helpers");
define("PATH_CACHE", "cache");
define("PATH_VIEWS", "views");


use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\RatchetWebsocket;


require 'constants.php';
require 'vendor/autoload.php';



/*
// Run the server application through the WebSocket protocol on port 8080
$app = new Ratchet\App('localhost', 8080);
$app->route('/chat', new RatchetWebsocket, array('*'));
$app->route('/echo', new Ratchet\Server\EchoServer, array('*'));
$app->run();
*/

/*
$loop = [];
$server = new Ratchet\App("localhost", 8080);
$server->route('/chat', new RatchetWebsocket, array('*'));
*/


use React\EventLoop\Factory;
use React\Socket\Server;
use React\Socket\SecureServer;



//
$ssl_path = __DIR__.DS.'ssl';



$loop = Factory::create();

$server = new Server('0.0.0.0:8443', $loop);

$secureServer = new SecureServer($server, $loop, [
    'local_cert'  => $ssl_path.DS.'cert.pem',
    'local_pk' => $ssl_path.DS.'key.pem',
    'verify_peer' => false
]);

$httpServer = new HttpServer(
    new WsServer(
        new RatchetWebsocket()
    )
);

//$server = IoServer::factory($httpServer, 8443);
$server = new IoServer($httpServer, $secureServer, $loop);



//
$server->run();


