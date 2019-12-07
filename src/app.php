<?php
require_once __DIR__ . '/../vendor/autoload.php';

use EasyRoute\Route;
use OracularApp\Admin;
use OracularApp\Config;
use OracularApp\DataManager;
use OracularApp\Department;
use OracularApp\Event;
use OracularApp\EventImageHelper;
use OracularApp\EventType;
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
addTwigCustomFilters($twig);

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
        $dataManager = new DataManager(DataManager::DEPARTMENT);
        $twigData['departments'] = $dataManager->getArrayData();
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

    $router->addMatch('GET', '/user/dashboard', function () use ($twig, $session, $logger) {
        if ($session->isUserLoggedIn() === false) {
            EasyHeaders::redirect('/login');
        }
        $twigData = array();
        $twigData['userLoggedIN'] = true;
        appendUserData($twigData);
        echo $twig->render('user.dashboard.twig', $twigData);
    });

    $router->addMatch('GET', '/event/details', function () use ($twig, $session) {
        $twigData = array();
        if ($session->isUserLoggedIn()) {
            $twigData['userLoggedIN'] = true;
            appendUserData($twigData);
        }
        if ($session->isAdminLoggedIn()) {
            $twigData['adminLoggedIN'] = true;
            appendAdminData($twigData);
        }
        if (!isset($_GET['id'])) {
            EasyHeaders::redirect('/');
        }
        $eventID = Functions::escapeInput($_GET['id']);
        $event = new Event($eventID);
        $twigData['event'] = $event;
        echo $twig->render('event.detail.twig', $twigData);
    });

    $router->addMatch('GET', '/event/details/registrations', function () use ($twig, $session, $logger) {
        $twigData = array();
        if ($session->isAdminLoggedIn() === false && !isset($_GET['id'])) {
            EasyHeaders::redirect('/');
        }
        $eventID = Functions::escapeInput($_GET['id']);
        $event = new Event($eventID);

        $adminID = $_SESSION[Session::SESSION_ADMIN];
        if (!$session->isSuperAdmin() || $event->createdBy !== $adminID) {
            EasyHeaders::redirect('/');
        }
        $twigData['event'] = $event;
        appendEventRegisteredUsers($twigData);
        $twigData['adminLoggedIN'] = true;
        echo $twig->render('event.details.registrations.twig', $twigData);
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

    $router->addMatch('GET', '/event/edit', function () use ($twig, $session, $logger) {
        if ($session->isAdminLoggedIn() === false && !isset($_GET['id'])) {
            EasyHeaders::redirect('/');
        }
        $eventID = Functions::escapeInput($_GET['id']);
        $event = new Event($eventID);
        $adminID = $_SESSION[Session::SESSION_ADMIN];
        if (!$session->isSuperAdmin() || $event->createdBy !== $adminID) {
            EasyHeaders::redirect('/');
        }
        $twigData = array();
        $twigData['event'] = $event;
        $dataManager = new DataManager(DataManager::EVENT_TYPE);
        $twigData['eventTypes'] = $dataManager->getArrayData();
        echo $twig->render('event.edit.twig', $twigData);
    });

    $router->addMatch('POST', '/event/edit', function () use ($twig, $session, $logger) {
        $twigData = array();
        $error = array();
        if ($session->isAdminLoggedIn() === false) {
            EasyHeaders::redirect('/');
        }
        if (
            isset($_POST['event-id']) &&
            isset($_POST['event-name']) &&
            isset($_POST['event-type']) &&
            isset($_POST['event-start-date']) &&
            isset($_POST['event-start-time']) &&
            isset($_POST['event-end-date']) &&
            isset($_POST['event-end-time']) &&
            isset($_POST['event-venue']) &&
            isset($_POST['event-desc'])) {

            $eventID = Functions::escapeInput($_POST['event-id']);
            $event = new Event($eventID);

            $adminID = $_SESSION[Session::SESSION_ADMIN];
            if (!$session->isSuperAdmin() || $event->createdBy !== $adminID) {
                EasyHeaders::redirect('/');
            }

            $startTimestamp = getTimestampUNIX($_POST['event-start-date'], $_POST['event-start-time']);
            $endTimeStamp = getTimestampUNIX($_POST['event-end-date'], $_POST['event-end-time']);

            if ($endTimeStamp <= $startTimestamp) {
                $error['eventEndDate'] = 'Event end cannot be less or equal to event start.';
                $error['eventEndTime'] = 'Event end cannot be less or equal to event start.';
            }

            if (count($error) === 0) {
                updateVarIfChanged($event->eventName, Functions::escapeInput($_POST['event-name']));
                updateVarIfChanged($event->eventType, Functions::escapeInput($_POST['event-type']));
                updateVarIfChanged($event->eventVenue, Functions::escapeInput($_POST['event-venue']));
                updateVarIfChanged($event->eventDesc, Functions::escapeInput($_POST['event-desc']));
                updateVarIfChanged($event->eventStartTime, getTimestampMYSQL($startTimestamp));
                updateVarIfChanged($event->eventEndTime, getTimestampMYSQL($endTimeStamp));

                if (isset($_FILES['event-img']) && is_uploaded_file($_FILES['event-img']['tmp_name'])) {
                    $eventIMG = new EventImageHelper($_FILES['event-img']);
                    updateVarIfChanged($event->eventIMG, $eventIMG->getImageBlob());
                    $logger->pushToDebug('User uploaded image.');
                } else {
                    $event->eventIMG = base64_decode($event->eventIMG);
                }
                $event->update();
                EasyHeaders::redirect("/event/details?event={$event->eventName}&id={$eventID}");
            }
        }
        $twigData['error'] = $error;
        $twigData['adminLoggedIN'] = true;
        $dataManager = new DataManager(DataManager::EVENT_TYPE);
        $twigData['eventTypes'] = $dataManager->getArrayData();
        echo $twig->render('event.edit.twig', $twigData);
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

            $startTimestamp = getTimestampUNIX($_POST['event-start-date'], $_POST['event-start-time']);
            $endTimeStamp = getTimestampUNIX($_POST['event-end-date'], $_POST['event-end-time']);

            if ($endTimeStamp <= $startTimestamp) {
                $error['eventEndDate'] = 'Event end cannot be less or equal to event start.';
                $error['eventEndTime'] = 'Event end cannot be less or equal to event start.';
            }
            $eventName = Functions::escapeInput($_POST['event-name']);
            $eventType = Functions::escapeInput($_POST['event-type']);
            $startDateTime = getTimestampMYSQL($startTimestamp);
            $endDateTime = getTimestampMYSQL($endTimeStamp);
            $eventVenue = Functions::escapeInput($_POST['event-venue']);
            $eventDesc = Functions::escapeInput($_POST['event-desc']);
            $eventImg = new EventImageHelper($_FILES['event-img']);

            $eventDept = $twigData['admin']->adminDept;
            $eventOwner = $_SESSION[Session::SESSION_ADMIN];

            if (count($error) === 0) {
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
                    EasyHeaders::redirect('/');
                } catch (Exception $e) {
                    $logger->pushToError($e);
                }
            }
        } else {
            $error['error'] = 'Fill all the fields.';
        }
        $twigData['error'] = $error;
        echo $twig->render('event.add.twig', $twigData);
    });

    $router->addMatch('POST', '/event/type/new', function () use ($twig, $session) {
        if ($session->isAdminLoggedIn() === false) {
            EasyHeaders::unauthorized();
        }
        if (isset($_POST['event-type-name']) && isset($_POST['event-type-desc'])) {
            $eventTypeName = Functions::escapeInput($_POST['event-type-name']);
            $eventTypeDesc = Functions::escapeInput($_POST['event-type-desc']);

            $eventType = new EventType();
            $eventType->add($eventTypeName, getAcronym($eventTypeName), $eventTypeDesc);
            EasyHeaders::redirect('/admin/dashboard');
        }
    });

    $router->addMatch('GET', '/admin/login', function () use ($twig, $session) {
        redirectIfLoggedIN($session);
        $twigData['login_text'] = 'Admin Login';
        $twigData['login_redirect'] = '/admin/login';
        echo $twig->render('login.twig', $twigData);
    });

    $router->addMatch('GET', '/admin/dashboard', function () use ($twig, $session) {
        if ($session->isAdminLoggedIn() === false) {
            EasyHeaders::redirect('/admin/login');
        }
        $twigData = array();
        appendAdminData($twigData);
        appendEventsData($twigData);
        appendDepartmentList($twigData);
        appendEventTypeList($twigData);
        appendAdminList($twigData);
        $twigData['adminLoggedIN'] = true;
        echo $twig->render('admin.dashboard.twig', $twigData);
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

    $router->addMatch('POST', '/admin/new', function () use ($session) {
        if ($session->isAdminLoggedIn() === false && $session->isSuperAdmin() === false) {
            EasyHeaders::unauthorized();
        }
        if (isset($_POST['admin-name']) &&
            isset($_POST['admin-email']) &&
            isset($_POST['admin-dept']) &&
            isset($_POST['admin-pass']) &&
            isset($_POST['admin-level'])) {
            $adminName = Functions::escapeInput($_POST['admin-name']);
            $adminEmail = Functions::escapeInput($_POST['admin-email']);
            $adminPassword = Functions::escapeInput($_POST['admin-pass']);
            $adminDept = Functions::escapeInput($_POST['admin-dept']);
            $adminLevel = Functions::escapeInput($_POST['admin-level']);

            $admin = new Admin();
            $admin->newAdmin($adminName, $adminEmail, $adminPassword, $adminDept, $adminLevel);
            EasyHeaders::redirect('/admin/dashboard');
        }
    });

    $router->addMatch('POST', '/department/new', function () use ($session) {
        if ($session->isAdminLoggedIn() === false && $session->isMidAdmin() === false) {
            EasyHeaders::unauthorized();
        }
        if (isset($_POST['dept-name'])) {
            $deptName = Functions::escapeInput($_POST['dept-name']);
            $department = new Department();
            $department->add($deptName, getKey($deptName));
            EasyHeaders::redirect('/admin/dashboard');
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
