<?php
namespace Middleware;




class RestrictHost
{



    //
    private $host;


    //
    public function __construct($host)
    {
        $this->host = $host;
    }


    //
    public function __invoke($request, $response, $next)
    {

        //
        if ( $request->getUri()->getHost() !== $this->host ){
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        //
        $response = $next($request, $response);


        // you can do further tasks
        //$response->getBody()->write(' FINAL ');

        //
        return $response;
    }
}