<?php

namespace boilerplate\Utility;

use Symfony\Component\HttpFoundation\Response;

interface ProvidesResponse {
    public function getResponse() : Response;
}
