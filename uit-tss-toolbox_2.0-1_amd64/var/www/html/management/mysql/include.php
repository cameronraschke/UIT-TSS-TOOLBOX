<?php
date_default_timezone_set('America/Chicago');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');


class MySQLConn {
    private $db;
    private static $user = "cameron";
    private static $pass = "UHouston!";
    private static $host = "localhost";
    private static $dbName = "laptopDB";
    private static $charset = "utf8mb4";
    private static $options = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => true, PDO::ERRMODE_EXCEPTION => true);
    public function dbObj() {
        $this->db = new PDO("mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=" . self::$charset . ";", self::$user, self::$pass, self::$options);
        return $this->db;
    }
}

class db {
    private $sql;
    private $arr;

    function setSQL($sql) {
        if (!isset($pdo)) { $db = new MySQLConn(); $pdo = $db->dbObj(); }
        $this->sql = $sql;
        $this->arr = array();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $this->arr = $stmt->fetchAll();
        $rowCount = $stmt->rowCount();
    }

    function getSQL() {
        if(is_array($this->arr) && count($this->arr) > 1) {
            return $this->arr;
        } else {
            return "NULL";
        }
    }
}
?>