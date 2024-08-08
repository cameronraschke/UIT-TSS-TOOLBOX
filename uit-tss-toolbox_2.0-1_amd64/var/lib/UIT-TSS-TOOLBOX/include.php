<?php
date_default_timezone_set('America/Chicago');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');


function stringFilter ($string) {
    if ($string == "" || $string == " " || $string == "NULL" || empty($string) || is_null($string) || !isset($string))  {
        return 1;
    } else {
        return 0;
    }
}

function numFilter ($string) {
    if (stringFilter($string) == 0) {
        if (is_numeric($string) && $string > 0) {
            return 0;
        } else {
            return 1;
        }
    } else {
        return 1;
    }
}


class MySQLConn {
    private static $user = "cameron";
    private static $pass = "UHouston!";
    private static $host = "localhost";
    private static $dbName = "laptopDB";
    private static $charset = "utf8mb4";
    private static $options = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => true, PDO::ERRMODE_EXCEPTION => true);
    public function dbObj() {
        return new PDO("mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=" . self::$charset . ";", self::$user, self::$pass, self::$options);
    }
}

class db {
    private $sql;
    private $arr;
    private $pdo;

    public function select($sql) {
        $db = new MySQLConn();
        $this->pdo = $db->dbObj();
        $this->sql = $sql;
        $this->arr = array();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $this->arr = $stmt->fetchAll();
        $rowCount = $stmt->rowCount();
    }

    public function get() {
        if(is_array($this->arr) && count($this->arr) > 1) {
            return $this->arr;
        } else {
            return "NULL";
        }
    }

    public function insertJob ($uuid) {
        $db = new MySQLConn();
        $this->pdo = $db->dbObj();
        if (stringFilter($uuid) == 0) {
            $sql = "INSERT INTO jobstats (uuid) VALUES (:uuid)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR);

            if (stringFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }

    public function updateJob ($key, $value, $uuid) {
        $db = new MySQLConn();
        $this->pdo = $db->dbObj();
        if (stringFilter($key) == 0 && stringFilter($uuid) == 0) {
            $sql = "UPDATE jobstats SET $key = :value WHERE uuid = :uuid";
            $stmt = $this->pdo->prepare($sql);

            if (is_numeric($value) && numFilter($value) == 1) {
                $value = "NULL";
            }

            if (stringFilter($value) == 0) {
                $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR);
                $stmt->bindParam('value', $value, PDO::PARAM_NULL);
            }

            if (stringFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
}

?>