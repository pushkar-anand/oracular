<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\Config;
use OracularApp\Exceptions\UserNotFoundException;
use OracularApp\Logger;
use OracularApp\Session;
use PhpUseful\EasyHeaders;
use PhpUseful\Functions;
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
        echo $twig->render('setup.twig');
    });

    $router->addMatch('GET', '/', function () {
        global $twig;
        $twigData = array();
        appendEventsData($twigData);
        echo $twig->render('home.twig', $twigData);
    });

    $router->addMatch('GET', '/admin/dashboard', function () use ($twig, $session) {
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
            EasyHeaders::redirect('/admin/dashboard');
        }
        echo $twig->render('admin.login.twig');
    });

    $router->addMatch('POST', '/admin/login', function () use ($twig, $session) {
        $data = array();
        $email = Functions::escapeInput($_POST['email']);
        $password = Functions::escapeInput($_POST['password']);
        try {
            $admin = $session->adminLogin($email, $password);
            if ($admin === true) {
                EasyHeaders::redirect('/admin/dashboard');
            } else {
                $data['error'] = array('password' => 'Incorrect Password.');
            }
        } catch (UserNotFoundException $e) {
            $data['error'] = array('email' => 'Email is not registered.');
        }
        echo $twig->render('admin.login.twig', $data);
    });

    $router->execute();

} catch (Exception $e) {
    $logger->pushToCritical($e);
    EasyHeaders::server_error();
}
