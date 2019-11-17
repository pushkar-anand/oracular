<?php


namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;
use PhpUseful\Exception\RowNotFoundException;

class User
{
    public const USER_TABLE_NAME = 'Users';
    public const USER_ID_FIELD = 'user_id';
    public const USER_USN_FIELD = 'usn';
    public const USER_NAME_FIELD = 'name';
    public const USER_EMAIL_FIELD = 'email';
    public const USER_GENDER_FIELD = 'gender';
    public const USER_PASSWORD_FIELD = 'password';
    public const USER_COLLEGE_FILED = 'college';
    public const USER_DEPT_FIELD = 'dept';
    public const USER_SEM_FIELD = 'semester';
    public const USER_SEC_FIELD = 'section';

    public const ERROR_USN_EXISTS = 100;
    public const ERROR_EMAIL_EXISTS = 200;
    public const ERROR_INVALID_EMAIL = 300;

    public $userID;
    public $usn;
    public $name;
    public $email;
    public $gender;
    public $password;
    public $college;
    public $departmentOBJ;
    public $semester;
    public $section;
    private $oracularDB;
    private $logger;
    private $department;

    public function __construct($userID = null, $email = null)
    {
        $this->oracularDB = OracularDB::getDB();
        $this->logger = Logger::getLogger();

        $result = null;

        try {
            if ($userID !== null) {
                $result = $this->fetchDetailsByID($userID);
            }

            if ($email !== null) {
                $result = $this->fetchResultByEmail($email);
            }
        } catch (RowNotFoundException $e) {
            new Exception("The email/id doesn't is not registered");
        }
        if ($result !== null) {
            $this->userID = $result[self::USER_ID_FIELD];
            $this->email = $result[self::USER_EMAIL_FIELD];
            $this->name = $result[self::USER_NAME_FIELD];
            $this->usn = $result[self::USER_USN_FIELD];
            $this->gender = $result[self::USER_GENDER_FIELD];
            $this->password = $result[self::USER_PASSWORD_FIELD];
            $this->college = $result[self::USER_COLLEGE_FILED];
            $this->department = $result[self::USER_DEPT_FIELD];
            $this->semester = $result[self::USER_SEM_FIELD];
            $this->section = $result[self::USER_SEC_FIELD];
            $this->parseUserData();
        }
    }

    /**
     * @param int $userID
     * @return array
     * @throws RowNotFoundException
     */
    private function fetchDetailsByID(int $userID): array
    {
        try {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::USER_TABLE_NAME,
                self::USER_ID_FIELD,
                $userID
            );
            return $result;
        } catch (RowNotFoundException $e) {
            throw new RowNotFoundException();
        }
    }

    /**
     * @param string $email
     * @return array
     * @throws RowNotFoundException
     */
    private function fetchResultByEmail(string $email): array
    {
        try {
            $result = $this->oracularDB->dbConnection->fetchRow(
                self::USER_TABLE_NAME,
                self::USER_EMAIL_FIELD,
                $email
            );
            return $result;
        } catch (RowNotFoundException $e) {
            throw new RowNotFoundException();
        }
    }

    private function parseUserData()
    {
        $this->departmentOBJ = new Department($this->department);
    }

    public function login($pwd): bool
    {
        if (password_verify($pwd, $this->password) === true) {
            return true;
        }
        return false;
    }

    /**
     * @param $usn
     * @param $name
     * @param $email
     * @param $gender
     * @param $password
     * @param $college
     * @param $dept
     * @param $sem
     * @param $sec
     * @throws Exception
     */
    public function newUser($usn, $name, $email, $gender, $password, $college, $dept, $sem, $sec)
    {
        if (!$this->isEmailValid($email)) {
            throw new Exception("Invalid email.", self::ERROR_INVALID_EMAIL);
        }
        if ($this->checkUSNExists($usn)) {
            throw new Exception("USN already registered.", self::ERROR_USN_EXISTS);
        }
        if ($this->checkEmailExists($email)) {
            throw new Exception("Email already registered.", self::ERROR_EMAIL_EXISTS);
        }

        $fields = array(
            self::USER_USN_FIELD,
            self::USER_NAME_FIELD,
            self::USER_EMAIL_FIELD,
            self::USER_GENDER_FIELD,
            self::USER_PASSWORD_FIELD,
            self::USER_COLLEGE_FILED,
            self::USER_DEPT_FIELD,
            self::USER_SEM_FIELD,
            self::USER_SEC_FIELD
        );

        $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);

        try {
            $id = $this->oracularDB->dbConnection->insert(
                self::USER_TABLE_NAME,
                $fields,
                'ssssssiis',
                strtoupper($usn), ucwords($name), strtolower($email), $gender, $hashed_pwd, ucwords($college), $dept, $sem, strtoupper($sec)
            );
            $this->userID = $id;
            $this->email = $name;
            $this->name = $email;
            $this->usn = $usn;
            $this->gender = $gender;
            $this->password = $password;
            $this->college = $college;
            $this->department = $dept;
            $this->semester = $sem;
            $this->section = $sec;
            $this->parseUserData();

        } catch (Exception $e) {
            $this->logger->pushToError($e);
        }
    }

    private function isEmailValid($email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function checkUSNExists($usn): bool
    {
        $count = $this->oracularDB->dbConnection->getResultCount(
            self::USER_TABLE_NAME,
            self::USER_USN_FIELD,
            $usn
        );

        return ($count > 0);
    }

    private function checkEmailExists($email): bool
    {
        $count = $this->oracularDB->dbConnection->getResultCount(
            self::USER_TABLE_NAME,
            self::USER_EMAIL_FIELD,
            $email
        );

        return ($count > 0);
    }


}