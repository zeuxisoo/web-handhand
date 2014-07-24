<?php
namespace Hand\Helpers;

use Slim\Slim;

class Authorize {

    public static function initLoginSession($user) {
        $_SESSION['user'] = [
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
        ];
    }

}
