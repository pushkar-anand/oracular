<?php


namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;
use PhpUseful\Exception\RowNotFoundException;

class Admin
{
    public const ADMIN_TABLE_NAME = 'Admins';

    public const ADMIN_ID_FIELD = 'admin_id';
    public const ADMIN_NAME_FIELD = 'admin_name';
    public const ADMIN_EMAIL_FIELD = 'email';
    public const ADMIN_PASS_FIELD = 'password';
    public const ADMIN_DEPT_FIELD = 'dept';
    public const ADMIN_LEVEL_FIELD = 'admin_level';

    public static $adminLevelValues = array('super' => 'super', 'mid' => 'mid', 'low' => 'low');

    public $adminID;
    public $adminEmail;
    public $adminName;
    public $adminDept;
    public $adminDeptObj;
    public $adminLevel;
    private $adminPassword;
    private $oracularDB;
    private $logger;

    public function __construct(int $adminID = null, string $adminEmail = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        $result = null;

        try {
            if ($adminID !== null && $adminEmail === null) {
                $result = $this->fetchDetailsByID($adminID);
            }

            if ($adminEmail !== null) {
                $result = $this->fetchResultByEmail($adminEmail);
            }
        } catch (RowNotFoundException $e) {
            new Exception("The email/id doesn't is not registered");
        }
        if ($result !== null) {
            $this->adminID = $result[self::ADMIN_ID_FIELD];
            $this->adminName = $result[self::ADMIN_NAME_FIELD];
            $this->adminEmail = $result[self::ADMIN_EMAIL_FIELD];
            $this->adminPassword = $result[self::ADMIN_PASS_FIELD];
            $this->adminDept = $result[self::ADMIN_DEPT_FIELD];
            $this->adminLevel = $result[self::ADMIN_LEVEL_FIELD];
            $this->parseAdminData();
        }
    }

    /**
     * @param int $adminID
     * @return array
     * @throws RowNotFoundException
     */
    private function fetchDetailsByID(int $adminID): array
    {
        try {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::ADMIN_TABLE_NAME,
                self::ADMIN_ID_FIELD,
                $adminID
            );
            return $result;
        } catch (RowNotFoundException $e) {
            throw new RowNotFoundException();
        }
    }

    /**
     * @param string $adminEmail
     * @return array
     * @throws RowNotFoundException
     */
    private function fetchResultByEmail(string $adminEmail): array
    {
        try {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::ADMIN_TABLE_NAME,
                self::ADMIN_EMAIL_FIELD,
                $adminEmail
            );
            return $result;
        } catch (RowNotFoundException $e) {
            throw new RowNotFoundException();
        }
    }

    private function parseAdminData()
    {
        $this->adminDeptObj = new Department($this->adminDept);
    }

    public function newAdmin($adminName, $adminEmail, $adminPassword, $adminDept, $adminLevel)
    {
        $fields = array(
            self::ADMIN_NAME_FIELD,
            self::ADMIN_EMAIL_FIELD,
            self::ADMIN_PASS_FIELD,
            self::ADMIN_DEPT_FIELD
        );

        $hashed_pwd = password_hash($adminPassword, PASSWORD_DEFAULT);

        try {

            $id = $this->oracularDB->dbConnection->insert(
                self::ADMIN_TABLE_NAME,
                $fields,
                'sssi',
                $adminName, $adminEmail, $hashed_pwd, $adminDept, $adminLevel
            );

            $this->adminID = $id;
            $this->adminName = $adminName;
            $this->adminEmail = $adminEmail;
            $this->adminPassword = $hashed_pwd;
            $this->adminDept = $adminDept;
            $this->adminLevel = $adminLevel;
            $this->parseAdminData();

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }

    }

    public function login($pwd): bool
    {
        if (password_verify($pwd, $this->adminPassword) === true) {
            return true;
        }
        return false;
    }

}