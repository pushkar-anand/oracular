<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;

class Department
{
    public const DEPARTMENT_TABLE_NAME = 'Department';
    public const DEPT_ID_FIELD = 'dept_id';
    public const DEPT_NAME_FIELD = 'dept_name';

    private $oracularDB;
    private $logger;

    private $dept_id;
    private $dept_name;


    public function __construct($dept_id = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        if ($dept_id != null) {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::DEPARTMENT_TABLE_NAME,
                self::DEPT_ID_FIELD,
                $dept_id
            );
            $this->dept_id = $result[self::DEPT_ID_FIELD];
            $this->dept_name = $result[self::DEPT_NAME_FIELD];
        }
    }

    /**
     * @return mixed
     */
    public function getDeptId()
    {
        return $this->dept_id;
    }

    /**
     * @return mixed
     */
    public function getDeptName()
    {
        return $this->dept_name;
    }

    public function add(string $name)
    {
        try {
            $id = $this->oracularDB->dbConnection->insert(
                self::DEPARTMENT_TABLE_NAME,
                array(self::DEPT_NAME_FIELD),
                's',
                $name
            );

            $this->dept_id = $id;
            $this->dept_name = $name;

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }


}