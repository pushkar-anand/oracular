<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$router = new Route();

$loader = new FilesystemLoader(__DIR__ . '/../views');
$twig = new Environment($loader);


try {
    $router->addMatch('GET', '/', function () {
        global $twig;
        echo $twig->render('default.twig', array('title' => 'EasyPHP-boilerplate'));
    });

    $router->execute();

} catch (Exception $e) {
    error_log($e->getMessage());
}
