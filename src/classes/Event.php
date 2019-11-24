<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;

class Event
{
    public const EVENTS_TABLE_NAME = 'Events';

    public const EVENT_ID_FIELD = 'event_id';
    public const EVENT_NAME_FIELD = 'event_name';
    public const EVENT_DESC_FIELD = 'event_desc';
    public const EVENT_IMG_FIELD = 'event_img';
    public const EVENT_TYPE_FIELD = 'event_type';
    public const EVENT_START_TIME_FIELD = 'event_start_time';
    public const EVENT_END_TIME_FIELD = 'event_end_time';
    public const EVENT_DEPT_FIELD = 'dept';
    public const EVENT_VENUE_FIELD = 'venue';

    public $eventID;
    public $eventName;
    public $eventDesc;
    public $eventIMG;
    public $eventType;
    public $eventStartTime;
    public $eventHumanReadableStartTime;
    public $eventEndTime;
    public $eventHumanReadableEndTime;
    public $year;
    public $eventDept;
    public $eventVenue;

    public $eventDeptOBJ;
    public $eventTypeOBJ;

    private $oracularDB;
    private $logger;

    public function __construct($eventID = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        if ($eventID != null) {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::EVENTS_TABLE_NAME,
                self::EVENT_ID_FIELD,
                $eventID
            );
            $this->eventID = $result[self::EVENT_ID_FIELD];
            $this->eventName = $result[self::EVENT_NAME_FIELD];
            $this->eventDesc = $result[self::EVENT_DESC_FIELD];
            $this->eventIMG = $result[self::EVENT_IMG_FIELD];
            $this->eventType = $result[self::EVENT_TYPE_FIELD];
            $this->eventStartTime = $result[self::EVENT_START_TIME_FIELD];
            $this->eventEndTime = $result[self::EVENT_END_TIME_FIELD];
            $this->eventDept = $result[self::EVENT_DEPT_FIELD];
            $this->eventVenue = $result[self::EVENT_VENUE_FIELD];

            $this->parseEventData();
        }
    }

    private function parseEventData()
    {
        $this->saveHumanReadableDate();
        $dt = strtotime($this->eventStartTime);
        $this->year = date('Y', $dt);

        $this->eventDeptOBJ = new Department($this->eventDept);
        $this->eventTypeOBJ = new EventType($this->eventType);
        $this->encodeIMG();
    }

    private function saveHumanReadableDate()
    {
        $dt = strtotime($this->eventStartTime);
        $this->eventHumanReadableStartTime = date('d-M-y', $dt);

        $dt = strtotime($this->eventEndTime);
        $this->eventHumanReadableEndTime = date('d-M-y', $dt);
    }

    public function newEvent(
        string $eventName,
        string $eventDesc,
        string $eventType,
        string $eventStartTime,
        string $eventEndTime,
        string $eventDept,
        string $eventVenue,
        string $eventIMG
    )
    {
        $fields = array(
            self::EVENT_NAME_FIELD,
            self::EVENT_DESC_FIELD,
            self::EVENT_TYPE_FIELD,
            self::EVENT_START_TIME_FIELD,
            self::EVENT_END_TIME_FIELD,
            self::EVENT_DEPT_FIELD,
            self::EVENT_VENUE_FIELD,
            self::EVENT_IMG_FIELD
        );

        try {
            $id = $this->oracularDB->dbConnection->insert(
                self::EVENTS_TABLE_NAME,
                $fields,
                "ssississ",
                $eventName, $eventDesc, $eventType, $eventStartTime, $eventEndTime, $eventDept, $eventVenue, $eventIMG
            );

            $this->eventID = $id;
            $this->eventName = $eventName;
            $this->eventDesc = $eventDesc;
            $this->eventIMG = $eventIMG;
            $this->eventType = $eventType;
            $this->eventStartTime = $eventStartTime;
            $this->eventEndTime = $eventEndTime;
            $this->eventDept = $eventDept;
            $this->eventVenue = $eventVenue;

            $this->parseEventData();

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }

    private function encodeIMG()
    {
        $this->eventIMG = base64_encode($this->eventIMG);
    }

}