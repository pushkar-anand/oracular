<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OracularApp\DataManager;
use OracularApp\EventClassifier;
use OracularApp\Session;
use OracularApp\User;
use PhpUseful\EasyHeaders;

function appendEventsData(array &$twigData)
{
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
}

function appendUserData(array &$twigData)
{
    $user = new User($_SESSION[Session::SESSION_USER]);
    $twigData['user'] = $user;
}

function redirectIfLoggedIN(Session $session)
{
    if ($session->isUserLoggedIn() || $session->isAdminLoggedIn()) {
        EasyHeaders::redirect('/?user-logged-in');
    }
}

