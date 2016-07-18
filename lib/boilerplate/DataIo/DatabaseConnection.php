<?php

namespace boilerplate\DataIo;

use boilerplate\Core\Configuration;
use boilerplate\Core\ConfigurationOption;

class DatabaseConnection {
    private $pdo;
    private $config;

    public function __construct() {
        $this->config = new Configuration(false);

        try {
            $this->pdo = new \PDO('mysql:host=' . $this->config->get(ConfigurationOption::DATABASE_HOST) . ';dbname=' . $this->config->get(ConfigurationOption::DATABASE_NAME) . ';charset=utf8',
                $this->config->get(ConfigurationOption::DATABASE_USER), $this->config->get(ConfigurationOption::DATABASE_PASSWORD));

            if($this->config->get(ConfigurationOption::DEBUGGING_ENABLED)) { $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING); }
        }
        catch(\PDOException $e) {
            // TODO: Throw error
            echo $e->getMessage();
            // $e->getMessage(); $this->pdo->errorCode(); $this->pdo->errorInfo();
        }
    }

    private function query(string $statement, array $args = array()) : bool {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);
        }
        catch(\PDOException $e) {
            // TODO: Throw error
            return false;
        }

        return true;
    }

    private function fetch(string $statement, array $args = array()) {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);

            return $query->fetch(\PDO::FETCH_ASSOC);
        }
        catch(\PDOException $e) {
            // TODO: Throw error
            return false;
        }
    }

    private function fetchValue(string $statement, array $args = array()) {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);

            return $query->fetch(\PDO::FETCH_COLUMN);
        }
        catch(\PDOException $e) {
            // TODO: Throw error
            return false;
        }
    }

    private function fetchAll(string $statement, array $args = array()) {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);

            return $query->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch(\PDOException $e) {
            // TODO: Throw error
            return false;
        }
    }

    // Generally, these functions should not be accessed directly but rather be proxied by a more specific class.

    public function readConfigValue(string $property) : string {
        if($result = $this->fetchValue('SELECT value FROM config WHERE property LIKE :property', array('property' => $property))) {
            return $result;
        }
        else {
            // TODO: Throw error
            return false;
        }
    }

    public function writeConfigValue(string $property, $value) : bool {
        return $this->query('UPDATE config SET value = :value WHERE property LIKE :property', array('property' => $property, 'value' => $value));
    }

    public function insertUser(string $username, string $passwd_hash, string $name, string $email) : int {
        $this->query('INSERT INTO users (username, passwd_hash, name, email) VALUES (:username, :passwd_hash, :name, :email)',
            array('username' => $username, 'passwd_hash' => $passwd_hash, 'name' => $name, 'email' => $email));
        return (int)$this->pdo->lastInsertId();
    }

    public function getUserByUsername(string $username) : array {
        if($result = $this->fetchValue('SELECT * FROM users WHERE username = :username', array('username' => $username))) {
            return $result;
        }
        else {
            // TODO: Throw error
            return false;
        }
    }

    public function addFile(string $filename, string $extension, string $context, string $uuid) : int {
        $this->query('INSERT INTO files (filename, extension, context, uuid, date_added) VALUES (:filename, :extension, :context, :uuid, NOW())',
            array('filename' => $filename, 'extension' => $extension, 'context' => $context, 'uuid' => $uuid));
        return (int)$this->pdo->lastInsertId();
    }

    public function getFileWithId(int $id) : array {
        if($result = $this->fetch('SELECT * FROM files WHERE id = :id', array('id' => $id))) {
            return $result;
        }
        else {
            // TODO: Throw error
            return false;
        }
    }

    public function deleteFileWithId(int $id) : bool {
        return $this->query('DELETE FROM files WHERE id = :id', array('id' => $id));
    }
}
