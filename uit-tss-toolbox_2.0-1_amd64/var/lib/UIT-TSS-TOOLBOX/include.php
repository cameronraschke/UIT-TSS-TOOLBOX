<?php
date_default_timezone_set('America/Chicago');
$timeZone = new \DateTimeZone('America/Chicago');
$dt = new \DateTimeImmutable();
$dt = $dt->setTimezone($timeZone);
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');

function updateTimeValue () {
    $dt = null;
    $date = null;
    $time = null;
    $timeZone = new \DateTimeZone('America/Chicago');
    $dt = new \DateTimeImmutable();
    $dt = $dt->setTimezone($timeZone);
    $date = $dt->format('Y-m-d');
    $time = $dt->format('Y-m-d H:i:s.v');
    return $time;
}

function strFilter ($string) {
    if ($string === "" || $string === " " || $string === "NULL" || empty($string) || is_null($string)) {
        return 1;
    } else {
        return 0;
    }
}

function numFilter ($string) {
    if (strFilter($string) == 0) {
        if (is_numeric($string)) {
            return 0;
        } else {
            return 1;
        }
    } else {
        return 1;
    }
}

function arrFilter ($arr) {
    if (is_array($arr)) {
        if (empty($arr)) {
            return 1;
        } else {
            return 0;
        }
    } else {
        return 1;
    }
}


class scriptTimer {
    private $arrMarker;
    private $arrMarkerObj;
    private $markerIt;
    private $arrTime;
    private $arrTimeObj;
    private $timeIt;
    private $curTime;

    private function refreshTime() {
        $this->curTime = hrtime(true);
    }

    private function clearTime() {
        $this->arrTime = null;
        $this->arrTimeObj = null;
        $this->timeIt = null;
    }

    private function clearMarker() {
        $this->arrMarker = null;
        $this->arrMarkerObj = null;
        $this->markerIt = null;
    }

    public function start() {
        $this->clearTime();
        $this->refreshTime();
        $this->arrTime = array();
        $this->arrTimeObj = new ArrayObject($this->arrTime);
        $this->timeIt = $this->arrTimeObj->getIterator();
        $this->arrTimeObj->append($this->curTime);
    }

    public function startMarker() {
        $this->clearMarker();
        $this->refreshTime();
        $this->arrMarker = array();
        $this->arrMarkerObj = new ArrayObject($this->arrMarker);
        $this->markerIt = $this->arrMarkerObj->getIterator();
        $this->arrMarkerObj->append($this->curTime);
    }

    public function endMarker() {
        $this->refreshTime();
        $this->arrMarkerObj->append($this->curTime);
        $count = $this->markerIt->count() - 1;
        $this->markerIt->seek($count);
        $end = $this->markerIt->current();
        $this->markerIt->rewind();
        $start = $this->markerIt->current();
        $execTime = round(($end - $start) / 1e9, 4);
        return $execTime;
        $this->clearMarker();
    }

    public function end() {
        $this->refreshTime();
        $this->arrTimeObj->append($this->curTime);
        $count = $this->timeIt->count() - 1;
        $this->timeIt->seek($count);
        $end = $this->timeIt->current();
        $this->timeIt->rewind();
        $start = $this->timeIt->current();
        $execTime = round(($end - $start) / 1e9, 4);
        return $execTime;
        $this->clearTime();
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
    private $rowCount;


    public function select($sql) {
        $db = new MySQLConn();
        $this->pdo = $db->dbObj();
        $this->sql = $sql;
        $this->arr = array();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $this->arr = $stmt->fetchAll();
        $this->rowCount = $stmt->rowCount();
    }

    public function AssocSelect($sql) {
        $db = new MySQLConn();
        $this->pdo = $db->dbObj();
        $this->sql = $sql;
        $this->arr = array();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $this->arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->rowCount = $stmt->rowCount();
    }
    
    public function Pselect($sql, $arr) {
        $db = new MySQLConn();
        $pdo = $db->dbObj();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($arr);
        $this->arr = $stmt->fetchAll();
    }

    public function get() {
        if(is_array($this->arr) && arrFilter($this->arr) == 0) {
            return $this->arr;
        } else {
            return 0;
        }
    }

    public function get_count() {
        if(is_array($this->arr) && arrFilter($this->arr) == 0) {
            return $this->rowCount;
        } else {
            return "NULL";
        }
    }


    // JOBSTATS table
    public function insertJob ($uuid) {
        if (strFilter($uuid) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO jobstats (uuid) VALUES (:uuid)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR);

            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }

    public function updateJob ($key, $value, $uuid) {
        if (strFilter($key) == 0 && strFilter($uuid) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE jobstats SET $key = :value WHERE uuid = :uuid";
            $stmt = $this->pdo->prepare($sql);

            if (is_numeric($value) && numFilter($value) == 1) {
                $value = "NULL";
            }

            if (strFilter($value) == 0) {
                $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR);
                $stmt->bindParam('value', $value, PDO::PARAM_NULL);
            }

            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }


    // CLIENTSTATS table
    public function insertCS ($tagNum) {
        if (strFilter($tagNum) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO clientstats (tagnumber) VALUES (:tagNum)";
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);

            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }

    public function updateCS ($key, $value, $tagnumber) {
        if (strFilter($key) == 0 && strFilter($tagnumber) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE clientstats SET $key = :value WHERE tagnumber = :tagnumber";
            $stmt = $this->pdo->prepare($sql);

            if (is_numeric($value) && numFilter($value) == 1) {
                $value = "NULL";
            }

            if (strFilter($value) == 0) {
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':tagnumber', $tagnumber, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':tagnumber', $tagnumber, PDO::PARAM_STR);
            }

            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }


    // SERVERSTATS table
    public function insertSS ($date) {
        if (strFilter($date) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO serverstats (date) VALUES (:date)";
            $stmt = $this->pdo->prepare($sql);
    
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    
    public function updateSS ($key, $value, $date) {
        if (strFilter($key) == 0 && strFilter($date) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE serverstats SET $key = :value WHERE date = :date";
            $stmt = $this->pdo->prepare($sql);
    
            if (is_numeric($value) && numFilter($value) == 1) {
                $value = "NULL";
            }
    
            if (strFilter($value) == 0) {
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    

    // Location table
    public function insertLocation ($time) {
        if (strFilter($time) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO locations (time) VALUES (:time)";
            $stmt = $this->pdo->prepare($sql);
    
            $stmt->bindParam(':time', $time, PDO::PARAM_STR);
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    
    public function updateLocation ($key, $value, $time) {
        if (strFilter($key) == 0 && strFilter($time) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE locations SET $key = :value WHERE time = :time";
            $stmt = $this->pdo->prepare($sql);
    
            if (strFilter($value) == 0) {
                if ($value == "TRUE") {
                    $value = "0";
                    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                } elseif ($value == "FALSE") {
                    $value = "1";
                    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                }
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    

    // Remote table
    public function insertRemote ($tagNum) {
        if (strFilter($tagNum) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO remote (tagnumber) VALUES (:tagNum)";
            $stmt = $this->pdo->prepare($sql);
    
            $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    
    public function updateRemote ($tagNum, $key, $value) {
        if (strFilter($tagNum) == 0 && strFilter($key) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE remote SET $key = :value WHERE tagnumber = :tagNum";
            $stmt = $this->pdo->prepare($sql);
    
            if (strFilter($value) == 0) {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    
    
    public function updateSystemData ($tagNum, $key, $value) {
        if (strFilter($tagNum) == 0 && strFilter($key) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE system_data SET $key = :value WHERE tagnumber = :tagNum";
            $stmt = $this->pdo->prepare($sql);
    
            if (is_numeric($value) && numFilter($value) == 1) {
                $value = "NULL";
            }
    
            if (strFilter($value) == 0) {
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
            $stmt = null;
            $sql = null;
    
            $dt = new DateTimeImmutable();
            $time = $dt->format('Y-m-d H:i:s.v');
    
            $sql = "UPDATE system_data SET time = :clienttime WHERE tagnumber = :tagNum";
            $stmt = $this->pdo->prepare($sql);
    
            if (strFilter($time) == 0) {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':clienttime', $time, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':clienttime', $time, PDO::PARAM_NULL);
            }
            
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
            $stmt = null;
            $sql = null;
        }
    }
    
    public function insertSystemData ($tagNum) {
        if (strFilter($tagNum) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO system_data (tagnumber) VALUES (:tagNum)";
            $stmt = $this->pdo->prepare($sql);
    
            if (strFilter($tagNum) == 0) {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }

    // Departments table
    public function insertDepartments ($time) {
        if (strFilter($time) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "INSERT INTO departments (time) VALUES (:time)";
            $stmt = $this->pdo->prepare($sql);
    
            $stmt->bindParam(':time', $time, PDO::PARAM_STR);
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }
    
    public function updateDepartments ($key, $value, $time) {
        if (strFilter($key) == 0 && strFilter($time) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();
            $sql = "UPDATE departments SET $key = :value WHERE time = :time";
            $stmt = $this->pdo->prepare($sql);
    
            if (strFilter($value) == 0) {
                if ($value == "TRUE") {
                    $value = "0";
                    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                } elseif ($value == "FALSE") {
                    $value = "1";
                    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                }
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }
        }
    }    

    public function insertBIOS ($tagNum) {
        if (strFilter($tagNum) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();

            $sql = "INSERT INTO bios_stats (tagnumber) VALUES (:tagNum)";
            $stmt = $this->pdo->prepare($sql);
    
            $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
    
            if (strFilter($stmt) === 0) {
                $stmt->execute();
            }

            $stmt = null;
            $sql = null;
        }
    }

    public function updateBIOS ($tagNum, $key, $value) {
        if (strFilter($tagNum) === 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();

            $sql = "UPDATE bios_stats SET $key = :value, time = :updateTime WHERE tagnumber = :tagNum";
            $stmt = $this->pdo->prepare($sql);

            $dt = new DateTimeImmutable();
            $updateTime = $dt->format('Y-m-d H:i:s.v');
    
            if (strFilter($value) === 0) {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':updateTime', $updateTime, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':updateTime', $updateTime, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }

            unset($updateTime);
            $dt = null;
            $stmt = null;
            $sql = null;
    
        }
    }


    public function insertOS ($tagNum) {
        if (strFilter($tagNum) == 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();

            $sql = "INSERT INTO os_stats (tagnumber) VALUES (:tagNum)";
            $stmt = $this->pdo->prepare($sql);
    
            $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
    
            if (strFilter($stmt) === 0) {
                $stmt->execute();
            }

            $stmt = null;
            $sql = null;
        }
    }

    public function updateOS ($tagNum, $key, $value, $updateTime) {
        if (strFilter($tagNum) === 0) {
            $db = new MySQLConn();
            $this->pdo = $db->dbObj();

            $sql = "UPDATE os_stats SET $key = :value, time = :updateTime WHERE tagnumber = :tagNum";
            $stmt = $this->pdo->prepare($sql);
    
            if (strFilter($value) === 0) {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->bindParam(':updateTime', $updateTime, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':tagNum', $tagNum, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_NULL);
                $stmt->bindParam(':updateTime', $updateTime, PDO::PARAM_STR);
            }
    
            if (strFilter($stmt) == 0) {
                $stmt->execute();
            }

            unset($updateTime);
            $dt = null;
            $stmt = null;
            $sql = null;
    
        }
    }
}

?>