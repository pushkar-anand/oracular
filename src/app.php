<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\Logger;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$logger = new Logger();
$router = new Route();

$loader = new FilesystemLoader(__DIR__ . '/../views');
$twig = new Environment($loader);


try {
    $router->addMatch('GET', '/', function () {
        global $twig;
        echo $twig->render('home.twig', array('title' => 'Oracular'));
    });

    $router->execute();

} catch (Exception $e) {
    $logger->pushToCritical($e->getMessage());
}
