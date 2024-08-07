<?php
date_default_timezone_set('America/Chicago');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');


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

    public function selectDB($sql) {
        if (!isset($pdo)) { echo "new PDO" . PHP_EOL; $db = new MySQLConn(); $this->pdo = $db->dbObj(); }
        $this->sql = $sql;
        $this->arr = array();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $this->arr = $stmt->fetchAll();
        $rowCount = $stmt->rowCount();
    }

    public function getSQL() {
        if(is_array($this->arr) && count($this->arr) > 1) {
            return $this->arr;
        } else {
            return "NULL";
        }
    }
}
?>