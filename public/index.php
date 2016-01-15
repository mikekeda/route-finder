<?php

use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Cache\Backend;

try {

    // Register an autoloader
    $loader = new Loader();
    $loader->registerDirs(array(
        '../app/controllers/',
        '../app/models/'
    ))->register();

    // Create a DI
    $di = new FactoryDefault();

    // Setup the view component
    $di->set('view', function () {
        $view = new View();
        $view->setViewsDir('../app/views/');
        return $view;
    });

    // Setup a base URI so that all generated URIs include the "route-finder.loc" folder
    $di->set('url', function () {
        $url = new UrlProvider();
        $url->setBaseUri('/route-finder.loc/');
        return $url;
    });

    $di->set('router', function () {

        $router = new \Phalcon\Mvc\Router();

        $router->add("/ajax/airports", array(
            'controller' => 'airports',
            'action' => 'index',
        ));

        return $router;
    });

    $di->set('cache', function () {
        $redis = new Redis();
        $redis->connect("localhost", "6379");
        return $redis;
    });

    // Handle the request
    $application = new Application($di);

    echo $application->handle()->getContent();

} catch (\Exception $e) {
     echo "PhalconException: ", $e->getMessage();
}
