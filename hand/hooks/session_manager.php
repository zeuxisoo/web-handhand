<?php
namespace Hand\Hooks;

use Slim\Slim;
use Hand\Helpers\Secure;
use Hand\Models\User;
use Hand\Helpers\Authorize;

class SessionManager {

    public function __construct() {
        $this->app        = Slim::getInstance();
        $this->app_config = $this->app->config('app.config');
    }

    public function setLoginSession() {
        $remember_token = $this->app->getCookie($this->app_config['remember']['name']);

        // Check session is or not exists, remember is or not exists
        if (empty($_SESSION['user']) === false && empty($_SESSION['user']['id']) === false) {
            $user = User::find($_SESSION['user']['id']);

            if (empty($user) === false) {
                Authorize::initLoginSession($user);
            }
        }else if (empty($remember_token) === false) {
            list($user_id, $signin_token, $auth_key) = explode(":", Secure::makeAuth($remember_token, "DECODE"));

            $user       = User::find($user_id);
            $key_verify = hash('sha256', $user_id.$signin_token.$this->app_config['cookie']['secret_key']) === $auth_key;

            if (empty($user) === false && $key_verify === true) {
                Authorize::initLoginSession($user);
            }
        }
    }

    public function checkBannedUser() {
        if (empty($_SESSION['user']['status']) === false && $_SESSION['user']['status'] === 'banned') {
            Authorize::resetLoginSession($this->app);
            $this->app->flash('error', 'Your account was banned');
            $this->app->redirect($this->app->urlFor('index.signin'));
        }
    }

}
