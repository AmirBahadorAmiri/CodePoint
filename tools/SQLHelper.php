<?php

class SQLHelper {

    private $sql;

    private $hostname = "localhost";
    private $username = "root";
    private $password = "";

    private $database_name = "code_point";
    private $result = "";

    public function __construct() {
        // PHP 8.1+ throws mysqli_sql_exception by default, so connect_error never
        // gets a chance to fire — the try/catch is what actually reports the problem.
        try {
            $this->sql = new mysqli(
                $this->getHostname(),
                $this->getUsername(),
                $this->getPassword(),
                $this->getDatabaseName()
            );
            mysqli_set_charset($this->sql, "utf8mb4");
        } catch (mysqli_sql_exception $e) {
            die("اتصال به دیتابیس برقرار نشد: " . $e->getMessage());
        }

        if ($this->getSql()->connect_error) {
            die("Connection failed: " . $this->getSql()->connect_error);
        }
    }

    public function sendQuery($query) {
        try {
            $r = $this->getSql()->query($query);
        } catch (mysqli_sql_exception $e) {
            die("اجرای کوئری ناموفق بود: " . $e->getMessage());
        }

        if ($this->getSql()->errno) {
            $this->setResult(null);
            die("Query failed: " . $this->getSql()->error);
        }

        $this->setResult($r);
    }

    public function escape($value) {
        return $this->getSql()->real_escape_string((string)$value);
    }

    public function fetchAll($query) {
        $this->sendQuery($query);
        $rows = array();
        if ($this->getResult()) {
            while ($row = $this->getResult()->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function fetchOne($query) {
        $this->sendQuery($query);
        if ($this->getResult() && $this->getResult()->num_rows > 0) {
            return $this->getResult()->fetch_assoc();
        }
        return null;
    }

    public function setResult($result) {
        $this->result = $result;
    }

    public function getResult() {
        return $this->result;
    }

    public function getDatabaseName() {
        return $this->database_name;
    }

    public function getHostname() {
        return $this->hostname;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getSql() {
        return $this->sql;
    }

    public function getUsername() {
        return $this->username;
    }

}
