<?php

namespace boilerplate\Core;

use boilerplate\DataIo\DatabaseConnection;
use boilerplate\Utility\Singleton;
use Monolog\Logger;

// PHP doesn't allow the use of expressions in the definition of a const, therefore we have to use this
// see: http://stackoverflow.com/a/2787565
define('rootdir', \ComposerLocator::getRootPath());
define('libdir', rootdir . '/lib');
define('composerdir', rootdir . '/vendor');
define('configfile', rootdir . '/config.ini');

class Application extends Singleton {
    const ROOT_DIR = rootdir;
    const LIB_DIR = libdir;
    const COMPOSER_DIR = composerdir;

    const CONFIG_FILE = configfile;

    const VERSION_TEXT = '0.1.0';
    const VERSION_CODE = 1;

    public $db_con;
    public $config;
    public $logger;
    public $renderer;

    public function __construct() {
        $this->db_con = new DatabaseConnection();
        $this->config = new Configuration(true, $this->db_con);
        $this->logger = new Logger('default');
        $this->renderer = new Renderer($this->config->get(ConfigurationOption::VIEW_DIR));
    }
}
