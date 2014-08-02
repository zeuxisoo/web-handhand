<?php
namespace Hand\Middlewares;

use Slim\Slim;
use Hand\Helpers\Authorize;

class Route {

    public static function requireLogin() {
        return function() {
            $app = Slim::getInstance();

            if (empty($_SESSION['user']) === true || empty($_SESSION['user']['id']) === true) {
                $app->flash('error', 'Please sign in first');
                $app->redirect($app->urlFor('index.signin'));
            }
        };
    }

}
