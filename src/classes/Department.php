<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;

class Department
{
    public const DEPARTMENT_TABLE_NAME = 'Department';
    public const DEPT_ID_FIELD = 'dept_id';
    public const DEPT_NAME_FIELD = 'dept_name';
    public const DEPT_SHORT_FIELD = 'dept_shortcode';

    private $oracularDB;
    private $logger;

    public $deptID;
    public $deptName;
    public $deptShortName;


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
            $this->deptID = $result[self::DEPT_ID_FIELD];
            $this->deptName = $result[self::DEPT_NAME_FIELD];
            $this->deptShortName = $result[self::DEPT_SHORT_FIELD];
        }
    }

    public function add(string $name, string $shortcode)
    {
        try {
            $id = $this->oracularDB->dbConnection->insert(
                self::DEPARTMENT_TABLE_NAME,
                array(self::DEPT_NAME_FIELD, self::DEPT_SHORT_FIELD),
                'ss',
                $name, $shortcode
            );

            $this->deptID = $id;
            $this->deptName = $name;
            $this->deptShortName = $shortcode;

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }


}