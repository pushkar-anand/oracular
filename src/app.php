<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\DataManager;
use OracularApp\Logger;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$logger = Logger::getLogger();
$router = new Route();

$loader = new FilesystemLoader(__DIR__ . '/../views');
$twig = new Environment($loader);


try {
    $router->addMatch('GET', '/', function () {
        global $twig;
        $twigData = array();

        $dataManager = new DataManager(DataManager::EVENT);
        $eventsData = $dataManager->getArrayData();

        $twigData['events'] = $eventsData;
        $twigData['title'] = 'Oracular';

        echo $twig->render('home.twig', $twigData);
    });

    $router->execute();

} catch (Exception $e) {
    $logger->pushToCritical($e);
}
