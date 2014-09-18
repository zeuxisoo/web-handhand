<?php
namespace Hand\Commands\Locale\Action;

use Hand\Abstracts\Command;

class Create extends Command {

    public function __construct($locale_path) {
        $this->locale_path  = $locale_path;
    }

    public function execute() {
        if (is_dir($this->locale_path) === false) {
            mkdir($this->locale_path, 0777, true);

            $this->success('==> Success, locale created');
        }else{
            $this->fail('==> Failed, locale already exists');
        }
    }

}
