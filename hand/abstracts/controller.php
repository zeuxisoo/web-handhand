<?php
namespace Hand\Abstracts;

use Slim\Slim;
use Hand\Helpers\Authorize;

class Controller {

    public function __construct() {
        $this->slim = Slim::getInstance();
    }

    public function isAdmin() {
        return Authorize::isAdmin();
    }

}
