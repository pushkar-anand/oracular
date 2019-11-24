<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;
use PhpUseful\Exception\RowNotFoundException;

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
    public const EVENT_CREATED_BY_FIELD = 'created_by';

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
    public $createdBy;

    public $eventDeptOBJ;
    public $eventTypeOBJ;

    private $oracularDB;
    private $logger;

    /**
     * Event constructor.
     * @param int|null $eventID
     * @throws RowNotFoundException
     */
    public function __construct($eventID = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        if ($eventID != null) {
            try {
                $result = $this->oracularDB->dbConnection->fetchRow(
                    self::EVENTS_TABLE_NAME,
                    self::EVENT_ID_FIELD,
                    $eventID
                );
            } catch (RowNotFoundException $e) {
                throw new RowNotFoundException("Event does not exists");
            }
            $this->eventID = $result[self::EVENT_ID_FIELD];
            $this->eventName = $result[self::EVENT_NAME_FIELD];
            $this->eventDesc = $result[self::EVENT_DESC_FIELD];
            $this->eventIMG = $result[self::EVENT_IMG_FIELD];
            $this->eventType = $result[self::EVENT_TYPE_FIELD];
            $this->eventStartTime = $result[self::EVENT_START_TIME_FIELD];
            $this->eventEndTime = $result[self::EVENT_END_TIME_FIELD];
            $this->eventDept = $result[self::EVENT_DEPT_FIELD];
            $this->eventVenue = $result[self::EVENT_VENUE_FIELD];
            $this->createdBy = $result[self::EVENT_CREATED_BY_FIELD];

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
        $this->eventHumanReadableStartTime = date('d-M-y h:m a ', $dt);

        $dt = strtotime($this->eventEndTime);
        $this->eventHumanReadableEndTime = date('d-M-y h:m a', $dt);
    }

    private function encodeIMG()
    {
        $this->eventIMG = base64_encode($this->eventIMG);
    }

    public function newEvent(
        string $eventName,
        string $eventDesc,
        string $eventType,
        string $eventStartTime,
        string $eventEndTime,
        string $eventDept,
        string $eventVenue,
        string $eventIMG,
        string $createdBy
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
            self::EVENT_IMG_FIELD,
            self::EVENT_CREATED_BY_FIELD
        );

        try {
            $query = "INSERT INTO Events (event_name, event_desc, event_type, event_start_time, event_end_time, dept, venue, event_img, created_by) VALUES (?,?,?,?,?,?,?,?,?)";
            $stmt = $this->oracularDB->dbConnection->getConn()->prepare($query);
            $null = NULL;
            $stmt->bind_param('ssissisbi', $eventName, $eventDesc, $eventType, $eventStartTime, $eventEndTime, $eventDept, $eventVenue, $null, $createdBy);
            $stmt->send_long_data(7, $eventIMG);
            if ($stmt->execute() === false) {
                $this->logger->pushToCritical('Error inserting data. ' . $stmt->error);
            } else {
                $id = $this->oracularDB->dbConnection->getConn()->insert_id;
                $this->eventID = $id;
                $this->eventName = $eventName;
                $this->eventDesc = $eventDesc;
                $this->eventIMG = $eventIMG;
                $this->eventType = $eventType;
                $this->eventStartTime = $eventStartTime;
                $this->eventEndTime = $eventEndTime;
                $this->eventDept = $eventDept;
                $this->eventVenue = $eventVenue;
                $this->createdBy = $createdBy;
                $this->parseEventData();
            }
        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }

    public function register(int $userID)
    {
        $eventRegistration = new EventRegistration();
        $eventRegistration->register($userID, $this->eventID);
    }

    public function isUserRegistered(int $userID)
    {
        return EventRegistration::isUserRegistered($this->eventID, $userID);
    }

}