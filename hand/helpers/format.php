<?php
namespace Hand\Helpers;

class Format {

    public static function toUploadedPaths($uploaded_infos) {
        $uploaded_paths = array();
        foreach($uploaded_infos as $info) {
            if (empty($info['saved_file']['path']) === false) {
                $uploaded_paths[] = $info['saved_file']['path'];
            }
        }
        return $uploaded_paths;
    }

    public static function hasError($uploaded_infos) {
        foreach($uploaded_infos as $info) {
            if (isset($info['status']) === true && $info['status'] === false) {
                return true;
            }
        }
        return false;
    }

}
