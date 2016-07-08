<?php

namespace boilerplate\Utility;

use baltpeter\Utility\PasswordHash;
use boilerplate\Core\User;
use boilerplate\DataIo\DatabaseConnection;

class UserManager
{
    private $db_con;

    public function __construct($db_con = null)
    {
        $this->db_con = $db_con == null ? new DatabaseConnection() : $db_con;
    }

    public function addUser(string $username, string $password, string $name, string $email) : int
    {
        $passwd_hash = PasswordHash::create_hash($password);
        return $this->db_con->insertUser($username, $passwd_hash, $name, $email);
    }

    public function getUserByUsername(string $username) : User
    {
        $result_array = $this->db_con->getUserByUsername($username);
        return new User($result_array['username'], $result_array['passwd_hash'], $result_array['name'], $result_array['email'], $result_array['id']);
    }

    // this function provides a basic login form
    // override this function to customize the form
    public static function printLoginForm(bool $wrong_info = false)
    {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Login</title>
        </head>
        <body>
        <div class="login-box">
            <?php if ($wrong_info) {?>
                <div>
                    <h4>Login Error</h4>
                    <p>The login data you entered is incorrect. Please try again. Should the problem persist, please contact your system adminstrator.</p>
                </div>
            <?php } ?>
            <div>
                <p>Please log in.</p>
                <form id="login-form" name="login-form" action="#" method="post">
                    Username: <input type="text" name="name" placeholder="regozijando.van-leedvermaak" required autofocus><br>
                    Password: <input type="password" name="password" placeholder="Password" required><br>
                    <input type="submit" value="Submit">
                </form>
            </div>
        </div>
        </body>
        </html>
        <?php
    }
}
