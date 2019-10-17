<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\DataManager;
use OracularApp\EventClassifier;
use OracularApp\Logger;
use OracularApp\Session;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$session = new Session();

date_default_timezone_set('Asia/Kolkata');

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

        $eventClassifier = new EventClassifier($eventsData);

        $twigData['events'] = $eventClassifier->getClassifiedEvents();
        $twigData['title'] = 'Oracular';
        $twigData['filterList'] = array(
            'Year' => $eventClassifier->getYears(),
            'Departments' => $eventClassifier->getDepartments(),
            'Type' => $eventClassifier->getTypes()
        );

        //var_dump($eventsData[0]);

        echo $twig->render('home.twig', $twigData);
    });

    $router->execute();

} catch (Exception $e) {
    $logger->pushToCritical($e);
}
