<?php


namespace OracularApp;


use Exception;
use PhpUseful\Exception\RowNotFoundException;

class EventRegistration
{
    public const EVENTS_REG_TABLE_NAME = 'EventRegData';

    public const USER_ID_FIELD = 'user_id';
    public const EVENT_ID_FIELD = 'event_id';
    public const RSVP_FIELD = 'rsvp_status';
    public const ATTENDED_FIELD = 'attended';

    public $userID;
    public $eventID;
    public $rsvpStatus;
    public $attended;

    public $user;
    public $event;

    private $oracularDB;
    private $logger;

    public function __construct(int $userID = null, int $eventID = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        if ($userID !== null && $eventID !== null) {
            $this->fetchDetails($userID, $eventID);
        }
    }

    private function fetchDetails(int $userID, int $eventID)
    {
        $query = 'SELECT * FROM ' .
            self::EVENTS_REG_TABLE_NAME .
            ' WHERE ' . self::EVENT_ID_FIELD . ' = ? AND ' . self::USER_ID_FIELD . ' = ?';
        $stmt = $this->oracularDB->dbConnection->getConn()->prepare($query);
        $stmt->bind_param('ii', $eventID, $userID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $this->userID = $result[self::USER_ID_FIELD];
        $this->eventID = $result[self::EVENT_ID_FIELD];
        $this->rsvpStatus = $result[self::RSVP_FIELD];
        $this->attended = $result[self::ATTENDED_FIELD];
        $this->fetchObjs();
    }

    private function fetchObjs()
    {
        try {
            $this->event = new Event($this->eventID);
            $this->user = new User($this->userID);
        } catch (RowNotFoundException $e) {
            $this->logger->pushToError("EventRegData doesn't exists for {$this->eventID} && {$this->userID}");
        }
    }

    public function register(int $userID, int $eventID)
    {
        try {
            $this->oracularDB->dbConnection->insert(
                self::EVENTS_REG_TABLE_NAME,
                array(self::EVENT_ID_FIELD, self::USER_ID_FIELD),
                'ii',
                $eventID, $userID
            );
            $this->fetchDetails($userID, $eventID);
        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }

    /**
     * @param bool $attended
     * @throws Exception
     */
    public function markAttended(bool $attended = true)
    {
        $this->updateField(self::ATTENDED_FIELD, $attended);
    }

    /**
     * @param string $fieldName
     * @param bool $value
     * @throws Exception
     */
    private function updateField(string $fieldName, bool $value)
    {
        $query = 'UPDATE ' . self::EVENTS_REG_TABLE_NAME .
            ' SET ' . $fieldName . ' = ?' .
            ' WHERE ' . self::EVENT_ID_FIELD . ' = ? AND ' . self::USER_ID_FIELD . ' = ?';
        $stmt = $this->oracularDB->dbConnection->getConn()->prepare($query);
        $stmt->bind_param('iii', $value, $this->eventID, $this->userID);
        if ($stmt->execute() === false) {
            throw new Exception('Error: ' . $stmt->error);
        }
    }

    /**
     * @param bool $rsvp
     * @throws Exception
     */
    public function markRSVP(bool $rsvp = true)
    {
        $this->updateField(self::RSVP_FIELD, $rsvp);
    }

    public static function isUserRegistered(int $eventID, int $userID): bool
    {
        $query = 'SELECT * FROM ' . self::EVENTS_REG_TABLE_NAME .
            ' WHERE ' . self::EVENT_ID_FIELD . ' = ? AND ' . self::USER_ID_FIELD . ' = ?';
        $stmt = OracularDB::getDB()->dbConnection->getConn()->prepare($query);
        $stmt->bind_param('ii', $eventID, $userID);
        $stmt->execute();
        $stmt->store_result();
        return ($stmt->num_rows != 0);
    }

    /**
     * @param int $eventID
     * @return array
     */
    public static function getAllRegisteredUsers(int $eventID): array
    {
        $users = array();
        $results = OracularDB::getDB()->dbConnection->fetchAllMatchingRows(self::EVENTS_REG_TABLE_NAME, self::EVENT_ID_FIELD, $eventID);
        foreach ($results as $result) {
            $user = new User($result[self::USER_ID_FIELD]);
            $users[] = $user;
        }
        return $users;
    }


}