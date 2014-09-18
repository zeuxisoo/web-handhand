<?php
namespace Hand\Commands\Locale\Action;

use Hand\Abstracts\Command;

class Remove extends Command {

    public function __construct($locale_path) {
        $this->locale_path  = $locale_path;
    }

    public function execute() {
        if (is_dir($this->locale_path) === true) {
            rmdir($this->locale_path);

            $this->success('==> Success, locale removed');
        }else{
            $this->fail('==> Failed, locale not exists');
        }
    }

}
