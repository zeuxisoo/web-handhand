<?php
namespace Hand\Middlewares;

use Slim\Slim;
use Hand\Helpers\Secure;
use Hand\Helpers\Authorize;
use Hand\Models\User;

class Route {

    const REACTIVE_YES  = 1;
    const REACTIVE_NO   = 2;

    private static function reactiveUserSession($remember_token, $app_config) {
        // Try to reload user session when remember token exists but user_session not exists
        if (empty($remember_token) === false && isset($_SESSION['user']) === false) {
            list($user_id, $signin_token, $auth_key) = explode(":", Secure::makeAuth($remember_token, "DECODE"));

            $user       = User::find($user_id);
            $key_verify = hash('sha256', $user_id.$signin_token.$app_config['cookie']['secret_key']) === $auth_key;

            if (empty($user) === false && $key_verify === true) {
                Authorize::initLoginSession($user);
                return self::REACTIVE_YES;
            }else{
                return self::REACTIVE_NO;
            }
        }
    }

    private static function doReactiveAction($status, $app, $app_config) {
        if ($status === null) return;

        switch($status) {
            // Active the $_SESSION now by reflash page
            case self::REACTIVE_YES:
                $app->redirect($app->urlFor($app->router()->getCurrentRoute()->getName()));
                break;
            // Remove remember token if active failed
            case self::REACTIVE_NO:
                $app->deleteCookie($app_config['remember']['name']);
                break;
        }
    }

    public static function requireLogin() {
        return function() {
            $app            = Slim::getInstance();
            $app_config     = $app->config('app.config');
            $remember_token = $app->getCookie($app_config['remember']['name']);

            // When remember exists, try to reactive session again
            $status = self::reactiveUserSession($remember_token, $app_config);

            // If user_session also not exists, ask to sign in
            // If user_session exists, do the active action by active status
            if (isset($_SESSION['user']) === false) {
                $app->deleteCookie($app_config['remember']['name']);

                $app->flash('error', 'The user session was expired, Please sign in again.');
                $app->redirect($app->urlFor('index.signin'));
            }else{
                self::doReactiveAction($status, $app, $app_config);
            }
        };
    }

    public static function reloadUserSession() {
        // Fix auth_token exists but session not exists case, re-create session
        return function() {
            $app            = Slim::getInstance();
            $app_config     = $app->config('app.config');
            $remember_token = $app->getCookie($app_config['remember']['name']);

            $status = self::reactiveUserSession($remember_token, $app_config);

            self::doReactiveAction($status, $app, $app_config);
        };
    }
}
