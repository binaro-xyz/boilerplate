<?php

namespace boilerplate\DataIo;

use boilerplate\Core\Application;
use boilerplate\Core\Configuration;
use boilerplate\Core\ConfigurationOption;
use boilerplate\DataType\File;

class FileManager
{
    private $db_con;
    private $config;

    public function __construct() {
        $this->db_con = Application::instance()->db_con;
        $this->config = Application::instance()->config;
    }

    // $files_array is the PHP $_FILES
    public function uploadFile(string $context, array $files_array, string $field_name = 'file') : File {
        $uuid = $this->generateUuid($context);

        $dir_for_context = $this->getDirForContext($context);
        if(!is_dir($dir_for_context)) {
            mkdir($dir_for_context, 0750, true);
        }
        $file_path = $dir_for_context . '/' . $uuid;

        if(!move_uploaded_file($files_array[$field_name]['tmp_name'], $file_path)) {
            // TODO: Throw error
            return new File(-1, '', '', '', '', '');
        }

        $id = $this->db_con->addFile(pathinfo($files_array[$field_name]['name'], PATHINFO_FILENAME), pathinfo($files_array[$field_name]['name'], PATHINFO_EXTENSION),
            $context, $uuid);
        return new File($id, pathinfo($files_array[$field_name]['name'], PATHINFO_FILENAME), pathinfo($files_array[$field_name]['name'], PATHINFO_EXTENSION), $context, $uuid, '');
    }

    public function deleteFileWithId(int $id) {
        $file = $this->db_con->getFileWithId($id);
        $path = $this->getFullFilePath($file['uuid'], $file['context']);
        $this->db_con->deleteFileWithId($id);
        unlink($path);
    }

    private function getDirForContext(string $context) : string {
        return $this->config->get(ConfigurationOption::FILE_DIR) . '/' . $context;
    }

    private function getFullFilePath(string $uuid, string $context) : string {
        return $this->getDirForContext($context) . '/' . $uuid;
    }

    private function generateUuid(string $context) {
        // see http://rogerstringer.com/2013/11/15/generate-uuids-php/
        @$uuid = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0x0fff) | 0x4000,
            mt_rand(0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $possible_duplicate = glob($this->getDirForContext($context) . '/' . $uuid);

        return count($possible_duplicate) > 0 ? $this->generateUuid($context) : $uuid;
    }
}
