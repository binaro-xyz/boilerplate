<?php

namespace boilerplate\DataIo;

use boilerplate\Core\Application;
use boilerplate\Core\Configuration;
use boilerplate\Utility\ConfigurationOption;

class DatabaseConnection {
    protected $pdo;
    protected $config;

    public function __construct() {
        $this->config = new Configuration(false);

        try {
            $this->pdo = new \PDO('mysql:host=' . $this->config->get(ConfigurationOption::DATABASE_HOST) . ';dbname=' . $this->config->get(ConfigurationOption::DATABASE_NAME) . ';charset=utf8',
                $this->config->get(ConfigurationOption::DATABASE_USER), $this->config->get(ConfigurationOption::DATABASE_PASSWORD));

            if($this->config->get(ConfigurationOption::DEBUGGING_ENABLED)) { $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING); }
        }
        catch(\PDOException $e) {
            Application::instance()->logger->error('Tried to initalize connection to the database but PDOException occurred.',
                array('message' => $e->getMessage(), 'error_code' => $this->pdo->errorCode(), 'error_info' => $this->pdo->errorInfo()));
        }
    }

    protected function query(string $statement, array $args = array()) : bool {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);
        }
        catch(\PDOException $e) {
            Application::instance()->logger->error('Tried to query database but PDOException occurred.',
                array('statement' => $statement, 'args' => $args, 'exception' => $e));
            return null;
        }

        return true;
    }

    protected function fetch(string $statement, array $args = array()) {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);

            if($result = $query->fetch(\PDO::FETCH_ASSOC)) return $result;
            else {
                Application::instance()->logger->error('Tried to fetch from database but result is null.',
                    array('statement' => $statement, 'args' => $args, 'result' => $result));
                return null;
            }
        }
        catch(\PDOException $e) {
            Application::instance()->logger->error('Tried to fetch from database but PDOException occurred.',
                    array('statement' => $statement, 'args' => $args, 'exception' => $e));
            return null;
        }
    }

    protected function fetchValue(string $statement, array $args = array()) {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);

            if($result = $query->fetch(\PDO::FETCH_COLUMN)) return $result;
            else {
                Application::instance()->logger->error('Tried to fetch database value but result is null.',
                    array('statement' => $statement, 'args' => $args, 'result' => $result));
                return null;
            }
        }
        catch(\PDOException $e) {
            Application::instance()->logger->error('Tried to fetch database value but PDOException occurred.',
                    array('statement' => $statement, 'args' => $args, 'exception' => $e));
            return null;
        }
    }

    protected function fetchAll(string $statement, array $args = array()) {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($args);

            if($result = $query->fetchAll(\PDO::FETCH_ASSOC)) return $result;
            else {
                Application::instance()->logger->error('Tried to fetch all from database but result is null.',
                    array('statement' => $statement, 'args' => $args, 'result' => $result));
                return null;
            }
        }
        catch(\PDOException $e) {
            Application::instance()->logger->error('Tried to fetch all from database but PDOException occurred.',
                    array('statement' => $statement, 'args' => $args, 'exception' => $e));
            return null;
        }
    }

    // Generally, these functions should not be accessed directly but rather be proxied by a more specific class.

    public function readConfigValue(string $property) {
        return $this->fetchValue('SELECT value FROM config WHERE property LIKE :property', array('property' => $property));
    }

    public function writeConfigValue(string $property, $value) : bool {
        return $this->query('UPDATE config SET value = :value WHERE property LIKE :property', array('property' => $property, 'value' => $value));
    }

    public function getMetaValue(string $property) : string {
        return $this->fetchValue('SELECT value FROM meta WHERE property = :property', array('property' => $property)) ?? '';
    }

    public function setMetaValue(string $property, string $value) : bool {
        return $this->query('REPLACE INTO meta SET property = :property, value = :value', array('property' => $property, 'value' => $value));
    }

    public function insertUser(string $username, string $passwd_hash, string $name, string $email) : int {
        if($this->query('INSERT INTO users (username, passwd_hash, name, email) VALUES (:username, :passwd_hash, :name, :email)',
            array('username' => $username, 'passwd_hash' => $passwd_hash, 'name' => $name, 'email' => $email)))
        {
            return (int)$this->pdo->lastInsertId();
        }
        else {
            return -1;
        }
    }

    public function getUserById(int $id) {
        return $this->fetch('SELECT * FROM users WHERE id = :id', array('id' => $id));
    }

    public function getUserByUsername(string $username) {
        return $this->fetch('SELECT * FROM users WHERE username = :username', array('username' => $username));
    }

    public function deleteUserWithId(int $id) : bool {
        return $this->query('DELETE FROM users WHERE id = :id', array('id' => $id));
    }

    public function addFile(string $filename, string $extension, string $context, string $uuid) : int {
        $this->query('INSERT INTO files (filename, extension, context, uuid, date_added) VALUES (:filename, :extension, :context, :uuid, NOW())',
            array('filename' => $filename, 'extension' => $extension, 'context' => $context, 'uuid' => $uuid));
        return (int)$this->pdo->lastInsertId();
    }

    public function getFileWithId(int $id) {
        return $this->fetch('SELECT * FROM files WHERE id = :id', array('id' => $id));
    }

    public function deleteFileWithId(int $id) : bool {
        return $this->query('DELETE FROM files WHERE id = :id', array('id' => $id));
    }
}
