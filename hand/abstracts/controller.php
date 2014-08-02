<?php
namespace Hand\Abstracts;

use Slim\Slim;

class Controller {

    public function __construct() {
        $this->slim = Slim::getInstance();
    }

    public function isAdmin() {
        return in_array($_SESSION['user']['id'], $this->slim->config('app.config')['default']['admin_ids']);
    }

}
