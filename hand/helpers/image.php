<?php
namespace Hand\Helpers;

class Image {

    const IS_RESIZE = 1;
    const IS_CROP   = 2;
    const IS_FILL   = 3;

    private static $instance = null;

    private $save_root   = "";  // overwrite orgin file if empty
    private $prefix_name = "";

    public static function instance($settings = []) {
        if (self::$instance === null) {
            $class_name = __class__;
            self::$instance = new $class_name();
        }

        foreach($settings as $key => $value) {
            self::$instance->$key = $value;
        }

        return self::$instance;
    }

    public function fileExtension($file_name) {
        return strtolower(trim(substr(strrchr($file_name, '.'), 1)));
    }

    public function singleResize($image_path, $width, $height) {
        $current_image = $this->imageSize($image_path);

        if ($current_image['width'] < $width && $current_image['height'] < $height) {
            $new_width = $current_image['width'];
            $new_height = $current_image['height'];
        }

        if($current_image['width'] > $current_image['height']) {
            $new_width = $width;
            $new_height = round(($width / $current_image['width']) * $current_image['height']);
        }else{
            $new_height = $height;
            $new_width = round(($height / $current_image['height']) * $current_image['width']);
        }

        $file_extension = $this->fileExtension(basename($image_path));

        if (in_array(strtolower($file_extension), ['jpg', 'gif', 'png', 'jpeg']) === true) {
            $resized_image = imagecreatetruecolor($new_width, $new_height);
            $background = imagecolorallocate($resized_image, 255, 255, 255);
            imagefill($resized_image, 0, 0, $background);

            $source_image = $this->createSourceImage($image_path);

            imagecopyresampled($resized_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $current_image['width'], $current_image['height']);

            $save_path = $this->createSavePath($image_path);

            $this->createImage($resized_image, $file_extension, $save_path);

            imagedestroy($resized_image);
            imagedestroy($source_image);

            return $this->status(self::IS_RESIZE, $image_path, $save_path);
        }else{
            return false;
        }
    }

    public function multiResize($image_paths, $width, $height) {
        $status = [];

        if (is_array($image_paths) === true) {
            foreach($image_paths as $path) {
                $status[] = self::singleResize($path, $width, $height);
            }
        }

        return $status;
    }

    public function crop($image_path, $width, $height) {
        $current_image = $this->imageSize($image_path);
        $file_extension = $this->fileExtension(basename($image_path));

        if (in_array(strtolower($file_extension), ['jpg', 'gif', 'png', 'jpeg']) === true) {
            $source_image = $this->createSourceImage($image_path);
            $cropped_image= imagecreatetruecolor($width, $height);

            $wm = $current_image['width'] / $width;
            $hm = $current_image['height'] / $height;
            $h_height = $height / 2;
            $w_height = $width / 2;

            if ($current_image['width'] > $current_image['height']) {
                $adjusted_width = $current_image['width'] / $hm;
                $half_width = $adjusted_width / 2;
                $int_width = $half_width - $w_height;

                imagecopyresampled($cropped_image, $source_image, -$int_width , 0, 0, 0, $adjusted_width, $height, $current_image['width'], $current_image['height']);
            }else{
                $adjusted_height = $current_image['height'] / $wm;
                $half_height = $adjusted_height / 2;
                $int_height = $half_height - $h_height;

                imagecopyresampled($cropped_image, $source_image, 0, -$int_height, 0, 0, $width, $adjusted_height, $current_image['width'], $current_image['height']);
            }

            $save_path = $this->createSavePath($image_path);

            $this->createImage($cropped_image, $file_extension, $save_path);

            imagedestroy($cropped_image);
            imagedestroy($source_image);

            return $this->status(self::IS_CROP, $image_path, $save_path);
        }else{
            return false;
        }
    }

    public function fill($image_path, $width, $height) {
        $current_image = $this->imageSize($image_path);
        $file_extension = $this->fileExtension(basename($image_path));

        if (in_array(strtolower($file_extension), ['jpg', 'gif', 'png', 'jpeg']) === true) {
            $source_image = $this->createSourceImage($image_path);

            $x_ratio = $width / $current_image['width'];
            $y_ratio = $height / $current_image['height'];

            if (($current_image['width'] <= $width) && ($current_image['height'] <= $height)) {
                $new_width  = $current_image['width'];
                $new_height = $current_image['height'];
            } elseif (($x_ratio * $current_image['height']) < $height) {
                $new_height = ceil($x_ratio * $current_image['height']);
                $new_width  = $width;
            } else {
                $new_width  = ceil($y_ratio * $current_image['width']);
                $new_height = $height;
            }

            $new_image = imagecreatetruecolor(round($new_width), round($new_height));
            imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $current_image['width'], $current_image['height']);

            $background_image = imagecreatetruecolor($width, $height);
            $background_color = imagecolorallocate($background_image, 255, 255, 255);
            imagefill($background_image, 0, 0, $background_color);

            imagecopy($background_image, $new_image, (($width - $new_width)/ 2), (($height - $new_height) / 2), 0, 0, $new_width, $new_height);

            $save_path = $this->createSavePath($image_path);

            $this->createImage($background_image, $file_extension, $save_path);

            return $this->status(self::IS_FILL, $image_path, $save_path);
        }else{
            return false;
        }
    }

    private function imageSize($image_path) {
        $image_size = getimagesize($image_path);
        $current_image['width'] = $image_size[0];
        $current_image['height'] = $image_size[1];
        unset($image_size);

        return $current_image;
    }

    private function createSourceImage($image_path) {
        $file_extension = $this->fileExtension(basename($image_path));

        $source_image = null;
        if ($file_extension == 'jpg' || $file_extension == 'jpeg') {
            $source_image = imagecreatefromjpeg($image_path);
        }elseif ($file_extension == 'gif') {
            $source_image = imagecreatefromgif($image_path);
        }elseif($file_extension == 'png') {
            $source_image = imagecreatefrompng($image_path);
        }
        return $source_image;
    }

    private function createSavePath($image_path) {
        $file_name = basename($image_path);

        // Add prefix name in filename
        if (empty($this->prefix_name) === false) {
            $file_name = $this->prefix_name.$file_name;
        }

        // Declare save_path, save to current path or different path
        // If current path exists same name file will overwrite it
        $this->save_root = trim($this->save_root);
        if (empty($this->save_root) === false) {
            if (is_dir($this->save_root) === false && file_exists($this->save_root) == false) {
                mkdir($this->save_root, 0777, true);
            }

            $save_path = $this->save_root.'/'.$file_name;
        }else{
            $save_path = dirname($image_path).'/'.$file_name;
        }

        return $save_path;
    }

    private function createImage($image, $file_extension, $save_path) {
        if($file_extension == 'jpg' || $file_extension == 'jpeg') {
            imagejpeg($image, $save_path, 100);
        }elseif($file_extension == 'gif') {
            imagegif($image, $save_path);
        }elseif($file_extension == 'png') {
            imagepng($image, $save_path, 0, null);
        }
    }

    private function status($type, $image_path, $save_path) {
        $field_name = "";
        switch($type) {
            case self::IS_RESIZE:
                $field_name = "resized_file";
                break;
            case self::IS_CROP:
                $field_name = "cropped_file";
                break;
            case self::IS_FILL:
                $field_name = "filled_file";
                break;
        }

        return [
            'status' => true,
            'orgin_file' => [
                'name' => basename($image_path),
                'path' => $image_path,
            ],
            $field_name => [
                'name' => basename($save_path),
                'path' => $save_path,
            ]
        ];
    }

}
