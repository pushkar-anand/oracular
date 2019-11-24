<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\Config;
use OracularApp\DataManager;
use OracularApp\Event;
use OracularApp\EventImageHelper;
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
addTwigCustomFunctions($twig);

$router = new Route();

try {

    /*    $router->addMatch('GET', '/setup/first', function () use ($twig) {
            echo $twig->render('setup.twig');
        });*/

    $router->addMatch('GET', '/', function () use ($twig, $session) {
        $twigData = array();
        if ($session->isUserLoggedIn()) {
            $twigData['userLoggedIN'] = true;
            appendUserData($twigData);
        }
        if ($session->isAdminLoggedIn()) {
            $twigData['adminLoggedIN'] = true;
            appendAdminData($twigData);
        }
        appendEventsData($twigData);
        echo $twig->render('home.twig', $twigData);
    });

    $router->addMatch('GET', '/login', function () use ($twig, $session) {
        redirectIfLoggedIN($session);
        $twigData = array();
        $twigData['login_text'] = 'Login';
        $twigData['login_redirect'] = '/user/login';
        echo $twig->render('login.twig', $twigData);
    });

    $router->addMatch('POST', '/user/login', function () use ($twig, $session) {
        $twigData = array();
        $twigData['login_text'] = 'Login';
        $twigData['login_redirect'] = '/user/login';
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

    $router->addMatch('GET', '/event/register', function () use ($twig, $session, $logger) {
        if ($session->isUserLoggedIn() === false) {
            EasyHeaders::redirect('/login');
        }
        if (isset($_GET['id'])) {
            $userID = $_SESSION[Session::SESSION_USER];
            $id = Functions::escapeInput($_GET['id']);
            try {
                $event = new Event($id);
                if ($event->isUserRegistered($userID) === false) {
                    $event->register($userID);
                }
            } catch (Exception $e) {
                $logger->pushToError("Event with $id does not exists.");
                EasyHeaders::redirect('/');
            }
        }
        EasyHeaders::redirect('/');
    });

    $router->addMatch('GET', '/user/dashboard', function () use ($twig, $session, $logger) {
        if ($session->isUserLoggedIn() === false) {
            EasyHeaders::redirect('/login');
        }
        $twigData = array();
        $twigData['userLoggedIN'] = true;
        appendUserData($twigData);
        echo $twig->render('user.dashboard.twig', $twigData);
    });


    $router->addMatch('GET', '/admin/login', function () use ($twig, $session) {
        redirectIfLoggedIN($session);
        $twigData['login_text'] = 'Admin Login';
        $twigData['login_redirect'] = '/admin/login';
        echo $twig->render('login.twig', $twigData);
    });

    $router->addMatch('POST', '/admin/login', function () use ($twig, $session) {
        $data = array();
        $data['login_text'] = 'Admin Login';
        $data['login_redirect'] = '/admin/login';
        $email = Functions::escapeInput($_POST['email']);
        $password = Functions::escapeInput($_POST['password']);
        try {
            $admin = $session->adminLogin($email, $password);
            if ($admin === true) {
                EasyHeaders::redirect('/?admin-logged-in');
            } else {
                $data['error'] = array('password' => 'Incorrect Password.');
            }
        } catch (UserNotFoundException $e) {
            $data['error'] = array('email' => 'Email is not registered.');
        }
        echo $twig->render('login.twig', $data);
    });

    $router->addMatch('GET', '/event/new', function () use ($twig, $session) {
        if ($session->isAdminLoggedIn() === false) {
            EasyHeaders::redirect('/admin/login');
        }
        $dataManager = new DataManager(DataManager::EVENT_TYPE);
        $twigData = array();
        appendAdminData($twigData);
        $twigData['eventTypes'] = $dataManager->getArrayData();
        echo $twig->render('event.add.twig', $twigData);
    });

    $router->addMatch('POST', '/event/new', function () use ($twig, $session, $logger) {
        if ($session->isAdminLoggedIn() === false) {
            EasyHeaders::redirect('/admin/login');
        }
        $twigData = array();
        appendAdminData($twigData);
        //var_dump($_POST);
        //var_dump($_FILES['event-img']);
        $error = array();
        //var_dump($twigData);
        if (
            isset($_POST['event-name']) &&
            isset($_POST['event-type']) &&
            isset($_POST['event-start-date']) &&
            isset($_POST['event-start-time']) &&
            isset($_POST['event-end-date']) &&
            isset($_POST['event-end-time']) &&
            isset($_POST['event-venue']) &&
            isset($_POST['event-desc']) &&
            isset($_FILES['event-img'])) {

            $startDateTimeStr = Functions::escapeInput($_POST['event-start-date']) . ' ' . Functions::escapeInput($_POST['event-start-time']);
            $startTimestamp = strtotime($startDateTimeStr);

            $endDateTimeStr = Functions::escapeInput($_POST['event-end-date']) . ' ' . Functions::escapeInput($_POST['event-end-time']);
            $endTimeStamp = strtotime($endDateTimeStr);

            if ($endTimeStamp <= $startTimestamp) {
                $error['eventEndDate'] = 'Event end cannot be less or equal to event start.';
                $error['eventEndTime'] = 'Event end cannot be less or equal to event start.';
            }
            $eventName = Functions::escapeInput($_POST['event-name']);
            $eventType = Functions::escapeInput($_POST['event-type']);
            $startDateTime = date('Y-m-d H:i:s', $startTimestamp);
            $endDateTime = date('Y-m-d H:i:s', $endTimeStamp);
            $eventVenue = Functions::escapeInput($_POST['event-venue']);
            $eventDesc = Functions::escapeInput($_POST['event-desc']);
            $eventImg = new EventImageHelper($_FILES['event-img']);

            $eventDept = $twigData['admin']->adminDept;
            $eventOwner = $_SESSION[Session::SESSION_ADMIN];

            try {
                $event = new Event();
                $event->newEvent(
                    $eventName,
                    $eventDesc,
                    $eventType,
                    $startDateTime,
                    $endDateTime,
                    $eventDept,
                    $eventVenue,
                    $eventImg->getImageBlob(),
                    $eventOwner
                );
            } catch (Exception $e) {
                $logger->pushToError($e);
            }


        } else {
            $error['error'] = 'Fill all the fields.';
        }
        $twigData['error'] = $error;
        echo $twig->render('event.add.twig', $twigData);
    });

    $router->addMatch('POST', '/department/new', function () use ($session) {
        if ($session->isAdminLoggedIn() === false && $session->isMidAdmin() === false) {
            EasyHeaders::unauthorized();
        }
    });

    $router->addMatch('POST', '/admin/new', function () use ($session) {
        if ($session->isAdminLoggedIn() === false && $session->isSuperAdmin() === false) {
            EasyHeaders::unauthorized();
        }
    });

    $router->addMatch('GET', '/logout', function () use ($session) {
        $session->logout();
        EasyHeaders::redirect('/?logged-out');
    });

    $router->execute();

} catch (Exception $e) {
    $logger->pushToCritical($e);
    EasyHeaders::server_error();
}
