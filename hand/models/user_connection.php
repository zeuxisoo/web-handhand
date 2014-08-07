<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class UserConnection extends Eloquent\Model {

    protected $table   = 'user_connection';
    protected $guarded = array('id');

    public function user() {
        return $this->belongsTo('Hand\Models\User');
    }

}
