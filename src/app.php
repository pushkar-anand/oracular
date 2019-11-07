<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\Config;
use OracularApp\Logger;
use OracularApp\Session;
use PhpUseful\EasyHeaders;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

Config::setDefaults();

$session = new Session();

$logger = Logger::getLogger();

$loader = new FilesystemLoader(__DIR__ . '/../views');
$twig = new Environment($loader);

$router = new Route();

try {

    $router->addMatch('GET', '/setup/first', function () use ($twig) {

    });

    $router->addMatch('GET', '/', function () {
        global $twig;
        $twigData = array();
        appendEventsData($twigData);
        echo $twig->render('home.twig', $twigData);
    });

    $router->addMatch('GET', '/admin', function () use ($twig, $session) {
        if (!$session->isAdminLoggedIn()) {
            EasyHeaders::redirect('/admin/login');
        }
        $twigData = array();
        $twigData['admin'] = true;
        appendEventsData($twigData);
        echo $twig->render('home.twig', $twigData);
    });

    $router->addMatch('GET', '/admin/login', function () use ($twig, $session) {
        if ($session->isAdminLoggedIn()) {
            EasyHeaders::redirect('/admin');
        }
        echo $twig->render('admin.login.twig');
    });

    $router->execute();

} catch (Exception $e) {
    $logger->pushToCritical($e);
    EasyHeaders::server_error();
}
