<?php
namespace Hand\Models;

use Slim\Slim;
use Illuminate\Database\Eloquent;

class User extends Eloquent\Model {

    protected $table   = 'user';
    protected $guarded = array('id');

    public function settings() {
        return $this->hasOne('Hand\Models\UserSettings');
    }

    public function avatar($size = 48, $default_image = 'identicon', $max_rate = 'g') {
        $schema = Slim::getInstance()->request->getScheme();

        return $schema."://www.gravatar.com/avatar/".md5($this->email)."?".http_build_query([
            's' => $size,
            'd' => $default_image,
            'r' => $max_rate,
        ]);
    }

}
