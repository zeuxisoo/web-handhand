<?php
namespace Hand\Commands\Locale\Action;

use Hand\Abstracts\Command;

class Clear extends Command {

    public function __construct($locale_path) {
        $this->locale_path  = $locale_path;
    }

    public function execute() {
        $status = $this->deleteDirectory($this->locale_path);

        if ($status === false) {
            $this->fail('==> Failed, not found locale directory');
        }else{
            $this->createEmptyDriectory($this->locale_path);
            $this->success('==> Success, locale cleanned');
        }
    }

    private function deleteDirectory($directory, $preserve = false) {
        if (is_dir($directory) === false) {
            return false;
        }else{
            $items = new \FilesystemIterator($directory);

            foreach ($items as $item) {
                if ($item->isDir() === true) {
                    $this->deleteDirectory($item->getRealPath());
                }else{
                    @unlink($item->getRealPath());
                }
            }

            if ($preserve === false) {
                @rmdir($directory);
            }

            return true;
        }
    }

    private function createEmptyDriectory($directory) {
        mkdir($directory, 0777, true);
        touch($directory.DIRECTORY_SEPARATOR.'.gitkeep');
        chmod($directory.DIRECTORY_SEPARATOR.'.gitkeep', 0777);
    }

}
