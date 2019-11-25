<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use DateTime;
use Exception;

class EventClassifier
{
    private $eventsData;

    private $upcoming = array();
    private $past = array();

    private $years = array();
    private $departments = array();
    private $types = array();

    private $logger;

    public function __construct(array $eventsData)
    {
        $this->logger = Logger::getLogger();

        try {
            $this->eventsData = $eventsData;
            $today = new DateTime();

            foreach ($eventsData as $event) {

                if (!in_array($event->year, $this->years)) {
                    array_push($this->years, $event->year);
                }

                if (!in_array($event->eventDeptOBJ->deptShortName, $this->departments)) {
                    array_push($this->departments, $event->eventDeptOBJ->deptShortName);
                }

                if (!in_array($event->eventTypeOBJ->eventTypeKey, $this->types)) {
                    array_push($this->types, $event->eventTypeOBJ->eventTypeKey);
                }

                $eventDateTime = new DateTime($event->eventStartTime);
                if ($today > $eventDateTime) {
                    array_push($this->past, $event);
                } else {
                    array_push($this->upcoming, $event);
                }
            }

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }

    public function getClassifiedEvents(): array
    {
        return array('upcoming' => $this->upcoming, 'past' => $this->past, 'all' => array_merge($this->upcoming, $this->past));
    }

    public function getYears(): array
    {
        return $this->years;
    }

    /**
     * @return array
     */
    public function getDepartments(): array
    {
        return $this->departments;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }


}