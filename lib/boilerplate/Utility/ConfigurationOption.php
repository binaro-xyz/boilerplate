<?php

namespace boilerplate\Utility;

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
