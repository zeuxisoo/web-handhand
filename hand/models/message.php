<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class Message extends Eloquent\Model {

    protected $table   = 'message';
    protected $guarded = array('id');

    public function sender() {
        return $this->belongsTo('Hand\Models\User', 'sender_id');
    }

}
