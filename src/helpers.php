<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OracularApp\Admin;
use OracularApp\DataManager;
use OracularApp\EventClassifier;
use OracularApp\EventRegistration;
use OracularApp\Session;
use OracularApp\User;
use PhpUseful\EasyHeaders;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

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

function appendAdminData(array &$twigData)
{
    $admin = new Admin($_SESSION[Session::SESSION_ADMIN]);
    $twigData['admin'] = $admin;
}

function appendDepartmentList(array &$twigData)
{
    $dataManager = new DataManager(DataManager::DEPARTMENT);
    $twigData['departments'] = $dataManager->getArrayData();
}

function appendEventTypeList(array &$twigData)
{
    $dataManager = new DataManager(DataManager::EVENT_TYPE);
    $twigData['eventTypes'] = $dataManager->getArrayData();
}

function appendAdminList(array &$twigData)
{
    $dataManager = new DataManager(DataManager::ADMIN);
    $twigData['admins'] = $dataManager->getArrayData();
}

function redirectIfLoggedIN(Session $session)
{
    if ($session->isUserLoggedIn()) {
        EasyHeaders::redirect('/?user-logged-in');
    }
    if ($session->isAdminLoggedIn()) {
        EasyHeaders::redirect('/admin/dashboard');
    }
}

function addTwigCustomFunctions(Environment &$twig)
{
    $checkRegTwigFunction = new TwigFunction('userRegisteredForEvent', function (string $eventID, string $userID = null) {
        if ($userID === null) {
            return false;
        }
        return EventRegistration::isUserRegistered($eventID, $userID);
    });
    $twig->addFunction($checkRegTwigFunction);
}

function addTwigCustomFilters(Environment &$twig)
{
    $firstWordFilter = new TwigFilter('first_word', function ($string) {
        $words = explode(' ', trim($string));
        return $words[0];
    });

    $twig->addFilter($firstWordFilter);
}

function getAcronym(string $string): string
{
    $words = preg_split("/\s+/", $string);
    $acronym = "";

    foreach ($words as $w) {
        if (ctype_alpha($w)) {
            $acronym .= $w[0];
        }
    }
    return strtoupper($acronym);
}

function getKey(string $string): string
{
    $string = strtolower($string);
    return str_replace(' ', '-', $string);
}


