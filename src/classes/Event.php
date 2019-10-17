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
    public const EVENT_TYPE_FIELD = 'event_type';
    public const EVENT_START_TIME_FIELD = 'event_start_time';
    public const EVENT_END_TIME_FIELD = 'event_end_time';
    public const EVENT_DEPT_FIELD = 'dept';
    public const EVENT_VENUE_FIELD = 'venue';

    private $oracularDB;
    private $logger;

    public $eventID;
    public $eventName;
    public $eventDesc;
    public $eventType;
    public $eventStartTime;
    public $eventEndTime;
    public $eventDept;
    public $eventVenue;


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
            $this->eventType = $result[self::EVENT_TYPE_FIELD];
            $this->eventStartTime = $result[self::EVENT_START_TIME_FIELD];
            $this->eventEndTime = $result[self::EVENT_END_TIME_FIELD];
            $this->eventDept = $result[self::EVENT_DEPT_FIELD];
            $this->eventVenue = $result[self::EVENT_VENUE_FIELD];
        }
    }

    public function newEvent(
        string $eventName,
        string $eventDesc,
        string $eventType,
        string $eventStartTime,
        string $eventEndTime,
        string $eventDept,
        string $eventVenue
    )
    {
        $fields = array(
            self::EVENT_NAME_FIELD,
            self::EVENT_DESC_FIELD,
            self::EVENT_TYPE_FIELD,
            self::EVENT_START_TIME_FIELD,
            self::EVENT_END_TIME_FIELD,
            self::EVENT_DEPT_FIELD,
            self::EVENT_VENUE_FIELD
        );

        try {
            $id = $this->oracularDB->dbConnection->insert(
                self::EVENTS_TABLE_NAME,
                $fields,
                "ssissis",
                $eventName, $eventDesc, $eventType, $eventStartTime, $eventEndTime, $eventDept, $eventVenue
            );

            $this->eventID = $id;
            $this->eventName = $eventName;
            $this->eventDesc = $eventDesc;
            $this->eventType = $eventType;
            $this->eventStartTime = $eventStartTime;
            $this->eventEndTime = $eventEndTime;
            $this->eventDept = $eventDept;
            $this->eventVenue = $eventVenue;

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }

    /**
     * @return mixed
     */
    public function getEventID()
    {
        return $this->eventID;
    }

    /**
     * @return mixed
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @return mixed
     */
    public function getEventDesc()
    {
        return $this->eventDesc;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @return mixed
     */
    public function getEventStartTime()
    {
        return $this->eventStartTime;
    }

    /**
     * @return mixed
     */
    public function getEventEndTime()
    {
        return $this->eventEndTime;
    }

    /**
     * @return mixed
     */
    public function getEventDept()
    {
        return $this->eventDept;
    }

    /**
     * @return mixed
     */
    public function getEventVenue()
    {
        return $this->eventVenue;
    }


}