<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OracularApp\DataManager;
use OracularApp\EventClassifier;

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
