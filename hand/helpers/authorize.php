<?php
namespace Hand\Helpers;

use Slim\Slim;

class Authorize {

    public static function initLoginSession($user) {
        $_SESSION['user'] = [
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
            'status'   => $user->status,
            'settings' => $user->settings()->select('notify_trade', 'notify_comment')->first()->toArray(),
        ];
    }

    public static function resetLoginSession($slim) {
        unset($_SESSION['user']);
        $slim->deleteCookie($slim->config('app.config')['remember']['name']);
    }

}
