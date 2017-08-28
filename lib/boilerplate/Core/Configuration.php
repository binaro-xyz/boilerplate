<?php

namespace boilerplate\Core;

use boilerplate\DataIo\DatabaseConnection;
use MyCLabs\Enum\Enum;

class ConfigurationOption extends Enum {
    const DATABASE_HOST = array('property' => 'db_host', 'source' => 'ini');
    const DATABASE_NAME = array('property' => 'db_name', 'source' => 'ini');
    const DATABASE_USER = array('property' => 'db_user', 'source' => 'ini');
    const DATABASE_PASSWORD = array('property' => 'db_pw', 'source' => 'ini');
    const DEBUGGING_ENABLED = array('property' => 'debugging_enabled', 'source' => 'ini');

    const BASE_URL = array('property' => 'base_url', 'source' => 'db');
    const FILE_DIR = array('property' => 'file_dir', 'source' => 'db');
    const VIEW_DIR = array('property' => 'view_dir', 'source' => 'db');
}

class Configuration {
    protected $ini;
    protected $db_con;

    public function __construct(bool $enable_db = true, DatabaseConnection $db_con = null) {
        $this->ini = new \Config_Lite(Application::CONFIG_FILE, LOCK_EX);

        if($enable_db) {
            $this->db_con = $db_con ?? new DatabaseConnection();
        }
    }

    public function get(array $option) {
        switch($option['source']) {
            case 'ini':
                $value = $this->getIniValue($option['property']);
                break;
            default:
            case 'db':
                $value = $this->getDatabaseValue($option['property']);
                break;
        }

        // special processing is necessary for some properties
        switch($option['property']) {
            case 'base_url':
                $value = rtrim($value, '/');
                break;
            case 'file_dir': // fallthrough intentional
            case 'view_dir':
                $value = rtrim(Application::ROOT_DIR . '/' . $value, '/');
                break;
            case 'debugging_enabled':
                $value = (bool)$value;
        }

        return $value;
    }

    public function set(array $option, string $value) : bool {
        switch($option['source']) {
            case 'ini':
                return $this->setIniValue($option['property'], $value);
                break;
            default:
            case 'db':
                return $this->setDatabaseValue($option['property'], $value);
                break;
        }
    }

    protected function getDatabaseValue(string $property) {
        if($this->db_con == null) {
            Application::instance()->logger->error('Tried to get database value but no database initialized.', array('property' => $property));
            return false;
        }

        return $this->db_con->readConfigValue($property) ?? false;
    }

    protected function getIniValue(string $property) {
        return $this->ini[$property];
    }

    protected function setDatabaseValue(string $property, $value) : bool {
        if($this->db_con == null) {
            Application::instance()->logger->error('Tried to write database value but no database initialized.', array('property' => $property, 'value' => $value));
            return false;
        }

        return $this->db_con->writeConfigValue($property, $value);
    }

    protected function setIniValue(string $property, $value) : bool {
        $this->ini[$property] = (string)$value;
        return $this->ini->save();
    }
}
