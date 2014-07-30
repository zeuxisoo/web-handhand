<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class UserSettings extends Eloquent\Model {

    protected $table   = 'user_settings';
    protected $guarded = array('id');

}
