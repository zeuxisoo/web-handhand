<?php
namespace Hand\Helpers;

use Slim\Slim;

class View extends \Twig_Extension {

    public function getName() {
        return 'hand';
    }

    public function __construct() {
        $this->slim = Slim::getInstance();
    }

    public function getFunctions() {
        return [
            new \Twig_SimpleFunction('itemImageUrl', [$this, 'itemImageUrl']),
        ];
    }

    public function getFilters() {
        return [
            new \Twig_SimpleFilter('cutString', [$this, 'cutString']),
        ];
    }

    public function itemImageUrl($filename, $folder = '') {
        $request = $this->slim->request();
        $prefix  = $this->slim->config('app.config')['upload']['item']['www'];

        return join('/', [$request->getUrl(), $prefix, $folder, $filename]);
    }

    public function cutString($string, $max = 25) {
        $string_length    = mb_strlen($string, 'UTF-8');
        $cut_length_count = 0;
        $final_string     = "";

        for ($i=0; $i<$string_length; $i++) {
            $s = mb_substr($string, $i, 1, 'UTF-8');

            if (strlen($s) == 1) {
                $cut_length_count++;
            }else{
                $cut_length_count += 2;
            }

            $final_string .= $s;

            if ($cut_length_count >= $max) {
                return $final_string;
            }
        }

        return $final_string;
    }

}
