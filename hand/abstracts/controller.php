<?php
namespace Hand\Abstracts;

use Slim\Slim;

class Controller {

    public function __construct() {
        $this->slim = Slim::getInstance();
    }

}
