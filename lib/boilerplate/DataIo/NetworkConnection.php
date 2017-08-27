<?php

namespace boilerplate\DataIo;

use boilerplate\Core\Application;

class NetworkConnection
{
    // extend this class and override this property to change the user agent string
    public static $user_agent_string = 'Boilerplate/' . Application::VERSION_TEXT . ' (gzip; +https://example.org)';

    public static function curlRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, NetworkConnection::$user_agent_string);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        $answer = curl_exec($ch);
        curl_close($ch);

        return $answer;
    }
}
