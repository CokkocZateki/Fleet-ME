<?php
require_once('classes/class.esiapi.php');
use Swagger\Client\ApiException;
use Swagger\Client\Api\UniverseApi;
use Swagger\Client\Api\SearchApi;

$cachetime = 600;

if (isset($_GET['q'])) {
    if (strlen($_GET['q']) > 3) {
        $cachefile = 'cache/tt-'.$_GET['q'].'.json';
        if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
            $response = file_get_contents($cachefile);
            header('Content-type: application/json');
            echo $response;
            die();
        }
        $esiapi = new ESIAPI();
        $searchapi = new SearchApi($esiapi);
        try {
            $charids = json_decode($searchapi->getSearch($_GET['q'], array('character'), 'en-us', false, 'tranquility'), true);
            if (count($charids)) {
                $universeapi = new UniverseApi($esiapi);
                //$ids = new \Swagger\Client\Model\PostUniverseNamesIds(array('ids' => $charids['character']));
                try {
                    $results = $universeapi->postUniverseNames($charids['character'], 'tranquility');
                    $response = '[';
                    foreach ($results as $result) {
                        $response .= $result->__toString().',';
                    }
                    $response .= ']';
                } catch (Exception $e) {
                    print $e->getMessage();
                    echo('{}');
                    die();
                }
                header('Content-type: application/json');
                $response = str_replace(',]', ']', preg_replace( "/\r|\n/", "", $response));
                echo $response;
                file_put_contents($cachefile, $response, LOCK_EX);
                die();
            } else {
                echo('{}');
                die();
            }
        } catch (Exception $e) {
            echo('{}');
            die();
        }
    } else {
        echo('{}');
        die();
    }
} else {
    echo('{}');
    die();
}

?>
