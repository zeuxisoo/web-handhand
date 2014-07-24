<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class User extends Eloquent\Model {

    protected $table   = 'user';
    protected $guarded = array('id');

}
