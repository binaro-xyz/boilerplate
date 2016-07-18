<?php

namespace boilerplate\DataType;


class File {
    public $id;
    public $filename;
    public $extension;
    public $context;
    public $uuid;
    public $date_added;

    public function __construct(int $id, string $filename, string $extension, string $context, string $uuid, string $date_added) {
        $this->id = $id;
        $this->filename = $filename;
        $this->extension = $extension;
        $this->context = $context;
        $this->uuid = $uuid;
        $this->date_added = $date_added;
    }
}
