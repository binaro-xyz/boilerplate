<?php

namespace boilerplate\Core;

// PHP doesn't allow the use of expressions in the definition of a const, therefore we have to use this
// see: http://stackoverflow.com/a/2787565
define('rootdir', \ComposerLocator::getRootPath());
define('libdir', Application::ROOT_DIR . '/lib');
define('composerdir', Application::ROOT_DIR . '/vendor');
define('configfile', Application::ROOT_DIR . '/config.ini');

class Application {
    const ROOT_DIR = rootdir;
    const LIB_DIR = libdir;
    const COMPOSER_DIR = composerdir;

    const CONFIG_FILE = configfile;

    const VERSION_TEXT = '0.1.0';
    const VERSION_CODE = 1;
}
