<?php

namespace boilerplate\DataType;


use boilerplate\Core\Application;
use boilerplate\Core\ConfigurationOption;

class File {
    public $id;
    public $filename;
    public $extension;
    public $context;
    public $uuid;
    public $date_added;

    public $error = null; // TODO: Decide if this should be an error code or an error message

    protected $db_con;
    protected $config;

    /*
     * these are the different constructors, implemented via static methods due to PHP's limitations
     */

    public function __construct(int $id, string $filename, string $extension, string $context, string $uuid, string $date_added) {
        $this->id = $id;
        $this->filename = $filename;
        $this->extension = $extension;
        $this->context = $context;
        $this->uuid = $uuid;
        $this->date_added = $date_added;

        $this->db_con = Application::instance()->db_con;
        $this->config = Application::instance()->config;
    }

    public static function fromId(int $id) : File {
        $db_result = Application::instance()->db_con->getFileWithId($id);

        if($db_result) {
            return new File($db_result['id'], $db_result['filename'], $db_result['extension'], $db_result['context'], $db_result['uuid'], $db_result['date_added']);
        }
        else {
            // TODO: Throw error
            return File::errorFile('Fetching file with ID ' . $id . ' from database failed.');
        }
    }

    // $files_array is the PHP $_FILES
    public static function fromUpload(string $context, array $files_array, string $field_name = 'file') : File {
        $uuid = File::generateUuid($context);

        $dir_for_context = File::getDirForContext($context);
        if (!is_dir($dir_for_context)) {
            mkdir($dir_for_context, 0750, true);
        }
        $file_path = $dir_for_context . '/' . $uuid;

        if (!move_uploaded_file($files_array[$field_name]['tmp_name'], $file_path)) {
            // TODO: Throw error
            return File::errorFile('Uploading file failed, PHP error code: ' . $files_array[$field_name]['error']);
        }

        $id = Application::instance()->db_con->addFile(pathinfo($files_array[$field_name]['name'], PATHINFO_FILENAME), pathinfo($files_array[$field_name]['name'], PATHINFO_EXTENSION),
            $context, $uuid);
        return File::fromId($id);
    }

    // this is the file that will be returned in case of an error
    public static function errorFile($error) : File {
        $file = new File(-1, '', '', '', '', '');
        $file->error = $error;
        return $file;
    }

    /*
     * actual object methods
     */

    public function delete() : bool {
        $path = File::getFullFilePath($this->uuid, $this->context);
        $this->db_con->deleteFileWithId($this->id);
        unlink($path);
        return true;
    }

    // if $download_file_name is an empty string, the original file name at the time of the upload will be used
    public function download(string $download_file_name = '') {
        $file = $this->getFilePath();
        $download_file_name = $download_file_name == '' ? $this->filename . '.' . $this->extension : $download_file_name;

        header('Content-Type: ' . finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file));
        header('Content-Disposition: attachment; filename="' . $download_file_name . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    public function getFilePath() : string {
        return File::getFullFilePath($this->uuid, $this->context);
    }

    /*
     * static helper functions
     */

    protected static function getDirForContext(string $context) : string {
        return Application::instance()->config->get(ConfigurationOption::FILE_DIR) . '/' . $context;
    }

    protected static function getFullFilePath(string $uuid, string $context) : string {
        return File::getDirForContext($context) . '/' . $uuid;
    }

    protected static function generateUuid(string $context) : string {
        // see http://rogerstringer.com/2013/11/15/generate-uuids-php/
        @$uuid = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0x0fff) | 0x4000,
            mt_rand(0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $possible_duplicate = glob(File::getDirForContext($context) . '/' . $uuid);

        return count($possible_duplicate) > 0 ? File::generateUuid($context) : $uuid;
    }
}
