<?php


namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpUseful\MySQLHelper;

class OracularDB
{

    private static $oracularDB = null;
    public $dbConnection;

    private function __construct()
    {
        $this->dbConnection = new MySQLHelper(
            DB_SERVER,
            DB_USER,
            DB_PASS,
            DB_NAME
        );
    }

    public static function getDB(): OracularDB
    {
        if (self::$oracularDB == null) {
            self::$oracularDB = new OracularDB();
        }
        return self::$oracularDB;
    }

}