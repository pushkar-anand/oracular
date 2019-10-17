<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;

class DataManager
{
    public const EVENT = 100;
    public const EVENT_TYPE = 101;
    public const DEPARTMENT = 102;

    private const TABLE_NAME_KEY = 'tableName';
    private const TABLE_PRIMARY_FIELD_KEY = 'primaryKey';


    private $logger;
    private $oracularDB;

    private $tableData;

    private $data = array();
    private $type;


    /**
     * DataManager constructor.
     * @param int $type
     * @throws Exception
     */
    public function __construct(int $type)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        $this->type = $type;

        switch ($type) {

            case self::EVENT:
                $this->tableData = array(
                    self::TABLE_NAME_KEY => Event::EVENTS_TABLE_NAME,
                    self::TABLE_PRIMARY_FIELD_KEY => Event::EVENT_ID_FIELD
                );
                break;

            case self::EVENT_TYPE:
                $this->tableData = array(
                    self::TABLE_NAME_KEY => EventType::EVENT_TYPE_TABLE_NAME,
                    self::TABLE_PRIMARY_FIELD_KEY => EventType::EVENT_TYPE_ID_FIELD
                );
                break;

            case self::DEPARTMENT:
                $this->tableData = array(
                    self::TABLE_NAME_KEY => Department::DEPARTMENT_TABLE_NAME,
                    self::TABLE_PRIMARY_FIELD_KEY => Department::DEPT_ID_FIELD
                );

                break;
            default:
                throw new Exception("Couldn't initiate class. Invalid type supplied.");
        }
        $this->fetchData();
    }

    /**
     * @throws Exception
     */
    private function fetchData()
    {

        $query = "SELECT {$this->tableData[self::TABLE_PRIMARY_FIELD_KEY]} FROM {$this->tableData[self::TABLE_NAME_KEY]}";
        $result = $this->oracularDB->dbConnection->getConn()->query($query);
        $i = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $obj = $this->makeObject($row);
                array_push($this->data, $obj);
            }
        }
    }

    /**
     * @param $row
     * @return Department|Event|EventType
     * @throws Exception
     */
    private function makeObject($row)
    {
        $id = $row[$this->tableData[self::TABLE_PRIMARY_FIELD_KEY]];

        switch ($this->type) {

            case self::EVENT:
                return new Event($id);
                break;

            case self::EVENT_TYPE:
                return new EventType($id);
                break;

            case self::DEPARTMENT:
                return new Department($id);
                break;
            default:
                throw new Exception("Invalid OBJ creation");
        }
    }

    /**
     *
     * @return array
     */
    public function getArrayData(): array
    {
        return $this->data;
    }


}