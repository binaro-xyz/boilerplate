<?php

namespace boilerplate\Core;

use baltpeter\Utility\PasswordHash;

class User {
    private $id;
    public $username;
    private $passwd_hash;
    public $name;
    public $email;

    private $authenticated = false;

    public function __construct(string $username, string $passwd_hash, string $name = '', string $email = '', int $id = -1) {
        $this->username = $username;
        $this->passwd_hash = $passwd_hash;
        $this->name = $name;
        $this->email = $email;
        if($id != -1) { $this->id = $id; }
    }

    public function isAuthenticated() : bool {
        return $this->authenticated;
    }

    public function authenticate(string $password) : bool {
        $this->authenticated = PasswordHash::validate_password($password, $this->passwd_hash);
        return $this->isAuthenticated();
    }

    /**
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     *
     * @return String containing either just a URL or a complete image tag
     */
    public function getGravatar(string $s = 80, string $d = 'mm', string $r = 'g', bool $img = false, array $atts = array()) : string {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($this->email)));
        $url .= "?s=$s&d=$d&r=$r";
        if($img) {
            $url = '<img src="' . $url . '"';
            foreach ($atts as $key => $val)
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }
}
