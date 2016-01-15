<?php
require_once __DIR__. '/../../vendor/autoload.php';

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{

    public function indexAction()
    {
        $client = new Everyman\Neo4j\Client('localhost', 7474);

        $this->view->disable();

        //Create a response instance
        $response = new \Phalcon\Http\Response();

        //Set the content of the response
        $response->setContent(json_encode($client->getServerInfo()));
        $response->setContentType('application/json', 'UTF-8');

        //Return the response
        return $response;
    }
}
