<?php
require_once __DIR__. '/../../vendor/autoload.php';

use Phalcon\Mvc\Controller;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher;

header('Access-Control-Allow-Origin: *');

function _distance($lat1, $lon1, $lat2, $lon2)
{
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 111.18957696;
}


class AirportsController extends Controller
{

    public function indexAction()
    {
        $client = new Client('localhost', 7474);

        $lat = $this->request->getQuery("lat", "float", 0.0);
        $lng = $this->request->get('lng', "float", 0.0);
        $distance = $this->request->get('distance', "float", 500);
        $offset = $this->request->get('offset', "int", 0);
        $limit = $this->request->get('limit', "int", 5);

        $cache =& $this->di->get('cache');

        $redis_key = implode('|', array(
            'AirportsController',
            'indexAction',
            $lat,
            $lng,
            $distance,
            $offset,
            $limit,
        ));

        try {
            // If Redis is connected - try to get result.
            $result = $cache->get($redis_key);
            $cache_connected = true;
        } catch (Exception $e) {
            $result = false;
            $cache_connected = false;
        }

        if ($result) {
            $result = unserialize($result);
        } else {
            $result = array();

            $query = sprintf(
                "START n=node:geom('withinDistance:[%s, %s, %s]')
                MATCH n-[r:AVAILABLE_DESTINATION]->()
                RETURN DISTINCT n
                SKIP %s LIMIT %s",
                number_format($lat, 6),
                number_format($lng, 6),
                number_format($distance, 1),
                $offset,
                $limit
            );

            $query = new Cypher\Query($client, $query);

            $query = $query->getResultSet();

            foreach ($query as $row) {
                $item = array(
                    'id' => $row['n']->getId(),
                    'distance' => _distance(
                        $row['n']->getProperties()['latitude'],
                        $row['n']->getProperties()['longitude'],
                        $lat,
                        $lng
                    ),
                );
                foreach ($row['n']->getProperties() as $key => $value) {
                    $item[$key] = $value;
                }
                $result[] = $item;
            }

            if ($cache_connected) {
                $cache->set($redis_key, serialize($result));
            }
        }

        $this->view->disable();
        $response = new \Phalcon\Http\Response();

        // Set the content of the response.
        $response->setContent(json_encode(array('json_list' => $result)));
        $response->setContentType('application/json', 'UTF-8');

        return $response;
    }
}
