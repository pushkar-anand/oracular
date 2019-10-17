<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;

class EventType
{

    public const EVENT_TYPE_TABLE_NAME = 'EventType';

    public const EVENT_TYPE_ID_FIELD = 'event_type_id';
    public const EVENT_TYPE_NAME_FIELD = 'event_type_name';
    public const EVENT_TYPE_KEY_FIELD = 'event_type_key';
    public const EVENT_TYPE_DESC_FIELD = 'event_type_desc';

    private $oracularDB;
    private $logger;

    public $eventTypeID;
    public $eventTypeName;
    public $eventTypeKey;
    public $eventTypeDesc;

    public function __construct($eventTypeID = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        if ($eventTypeID != null) {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::EVENT_TYPE_TABLE_NAME,
                self::EVENT_TYPE_ID_FIELD,
                $eventTypeID
            );
            $this->eventTypeID = $result[self::EVENT_TYPE_ID_FIELD];
            $this->eventTypeName = $result[self::EVENT_TYPE_NAME_FIELD];
            $this->eventTypeDesc = $result[self::EVENT_TYPE_DESC_FIELD];
            $this->eventTypeKey = $result[self::EVENT_TYPE_KEY_FIELD];
        }
    }

    public function add(string $eventTypeName, string $eventTypeKey, string $eventTypeDesc)
    {
        try {
            $id = $this->oracularDB->dbConnection->insert(
                self::EVENT_TYPE_TABLE_NAME,
                array(self::EVENT_TYPE_NAME_FIELD, self::EVENT_TYPE_KEY_FIELD, self::EVENT_TYPE_DESC_FIELD),
                "sss",
                $eventTypeName, $eventTypeKey, $eventTypeDesc
            );
            $this->eventTypeID = $id;
            $this->eventTypeName = $eventTypeName;
            $this->eventTypeDesc = $eventTypeDesc;
            $this->eventTypeKey = $eventTypeKey;

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }



}