<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\Config;
use OracularApp\DataManager;
use OracularApp\Exceptions\UserNotFoundException;
use OracularApp\Logger;
use OracularApp\Session;
use OracularApp\User;
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

    /*    $router->addMatch('GET', '/setup/first', function () use ($twig) {
            echo $twig->render('setup.twig');
        });*/

    $router->addMatch('GET', '/', function () use ($twig, $session) {
        $twigData = array();
        if ($session->isUserLoggedIn()) {
            $twigData['userLoggedIN'] = true;
        }
        if ($session->isAdminLoggedIn()) {
            $twigData['adminLoggedIN'] = true;
        }
        appendEventsData($twigData);
        echo $twig->render('home.twig', $twigData);
    });

    $router->addMatch('GET', '/login', function () use ($twig, $session) {
        redirectIfLoggedIN($session);
        $twigData = array();
        $twigData['login_redirect'] = '/user/login';
        echo $twig->render('login.twig', $twigData);
    });

    $router->addMatch('POST', '/user/login', function () use ($twig, $session) {
        $twigData = array();
        $email = Functions::escapeInput($_POST['email']);
        $password = Functions::escapeInput($_POST['password']);
        try {
            $admin = $session->userLogin($email, $password);
            if ($admin === true) {
                EasyHeaders::redirect('/?user-logged-in');
            } else {
                $twigData['error'] = array('password' => 'Incorrect Password.');
            }
        } catch (UserNotFoundException $e) {
            $twigData['error'] = array('email' => 'Email is not registered.');
        }
        echo $twig->render('login.twig', $twigData);
    });

    $router->addMatch('GET', '/user/register', function () use ($twig, $session) {
        redirectIfLoggedIN($session);
        $dataManager = new DataManager(DataManager::DEPARTMENT);
        $twigData = array();
        $twigData['departments'] = $dataManager->getArrayData();
        //var_dump($twigData);
        echo $twig->render('user.register.twig', $twigData);
    });

    $router->addMatch('POST', '/user/register', function () use ($twig) {
        $twigData = array();
        $error = array();
        if (
            isset($_POST['name']) &&
            isset($_POST['email']) &&
            isset($_POST['password']) &&
            isset($_POST['gender']) &&
            isset($_POST['usn']) &&
            isset($_POST['college']) &&
            isset($_POST['dept']) &&
            isset($_POST['semester']) &&
            isset($_POST['section'])) {

            $name = Functions::escapeInput($_POST['name']);
            $email = Functions::escapeInput($_POST['email']);
            $password = Functions::escapeInput($_POST['password']);
            $gender = Functions::escapeInput($_POST['gender']);
            $usn = Functions::escapeInput($_POST['usn']);
            $college = Functions::escapeInput($_POST['college']);
            $dept = Functions::escapeInput($_POST['dept']);
            $sem = Functions::escapeInput($_POST['semester']);
            $sec = Functions::escapeInput($_POST['section']);

            try {
                $user = new User();
                $user->newUser($usn, $name, $email, $gender, $password, $college, $dept, $sem, $sec);
                EasyHeaders::redirect('/?user-registered');
            } catch (Exception $e) {
                if ($e->getCode() === User::ERROR_INVALID_EMAIL || $e->getCode() === User::ERROR_EMAIL_EXISTS) {
                    $error['email'] = $e->getMessage();
                } elseif ($e->getCode() === User::ERROR_USN_EXISTS) {
                    $error['usn'] = $e->getMessage();
                }
            }
        } else {
            $error['error'] = 'Fill all the fields.';
        }

        $twigData['error'] = $error;
        echo $twig->render('user.register.twig', $twigData);
    });


    $router->addMatch('GET', '/admin/dashboard', function () use ($twig, $session) {
        redirectIfNotLoggedIN($session);
        $twigData = array();
        $twigData['admin'] = true;
        appendEventsData($twigData);
        echo $twig->render('home.twig', $twigData);
    });

    $router->addMatch('GET', '/admin/login', function () use ($twig, $session) {
        redirectIfLoggedIN($session);
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
