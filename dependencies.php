<?php

/*
 *
 * DEPENDENCY CONTAINER INTERFACE
 *
 * */

//
$container = $app->getContainer();



$container['twig_doc'] = function ($container) {

    //
    $view = new \Slim\Views\Twig(PATH_TWIG_DOCS, [
        //'cache' => PATH_CACHE.DS.'twig',
        'cache' => false,
        'auto_reload' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');

    //
    $view->addExtension(new Slim\Views\TwigExtension($container->get('router'), $basePath));

    //
    return $view;
};



/*
 *
 * TWIG TEMPLATES
 * https://www.cloudways.com/blog/twig-templates-in-slim/
 *
 * */
$container['twig_view'] = function ($container) {

    //
    $view = new \Slim\Views\Twig(PATH_VIEWS, [
        //'cache' => PATH_CACHE.DS.'twig',
        'cache' => false,
        'auto_reload' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');


    //
    $view->addExtension(new Slim\Views\TwigExtension($container->get('router'), $basePath));

    //
    return $view;
};






/*
 *
 * PHP TEMPLATES
 *
 * */
// Register component on container
$container['php_view'] = function ($container) {
    return new \Slim\Views\PhpRenderer(PATH_VIEWS);
};


