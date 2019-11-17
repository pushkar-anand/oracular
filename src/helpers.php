<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OracularApp\DataManager;
use OracularApp\EventClassifier;
use OracularApp\Session;
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

function redirectIfLoggedIN(Session $session)
{
    if ($session->isUserLoggedIn()) {
        EasyHeaders::redirect('/?user-logged-in');
    } elseif ($session->isAdminLoggedIn()) {
        EasyHeaders::redirect('/admin/dashboard');
    }
}

function redirectIfNotLoggedIN(Session $session)
{
    if ($session->isAdminLoggedIn() === false) {
        EasyHeaders::redirect('/admin/login');
    }
}

