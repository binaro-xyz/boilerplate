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

    /*
     * these are the different constructors, implemented via static methods due to PHP's limitations
     */

    public function __construct(string $username, string $passwd_hash, string $name = '', string $email = '', int $id = -1) {
        $this->username = $username;
        $this->passwd_hash = $passwd_hash;
        $this->name = $name;
        $this->email = $email;
        $this->id = $id;
    }

    public static function newUser(string $username, string $password, string $name, string $email) : User {
        $passwd_hash = PasswordHash::create_hash($password);
        if($id = Application::instance()->db_con->insertUser($username, $passwd_hash, $name, $email)) {
            return User::fromId($id);
        }
        else {
            return User::errorUser();
        }
    }

    public static function fromId(int $id) : User {
        if($result_array = Application::instance()->db_con->getUserById($id)) {
            return new User($result_array['username'], $result_array['passwd_hash'], $result_array['name'], $result_array['email'], $result_array['id']);
        }
        else {
            return User::errorUser();
        }
    }

    public static function fromUsername(string $username) : User {
        if($result_array = Application::instance()->db_con->getUserByUsername($username)) {
            return new User($result_array['username'], $result_array['passwd_hash'], $result_array['name'], $result_array['email'], $result_array['id']);
        }
        else {
            return User::errorUser();
        }
    }

    // Users with an ID of -1 should be considered invalid
    // TODO: Maybe add an error property similar to File?
    public static function errorUser() {
        return new User('error', '', 'Error User', '', -1);
    }

    /*
     * actual object methods
     */

    public function isAuthenticated() : bool {
        return $this->authenticated;
    }

    public function authenticate(string $password) : bool {
        $this->authenticated = PasswordHash::validate_password($password, $this->passwd_hash);
        return $this->isAuthenticated();
    }

    public function delete() : bool {
        if($this->id != -1) return Application::instance()->db_con->deleteUserWithId($this->id);
        else return false;
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
    public function getGravatar(string $s = '80', string $d = 'mm', string $r = 'g', bool $img = false, array $atts = array()) : string {
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
