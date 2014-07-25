<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class ItemComment extends Eloquent\Model {

    protected $table   = 'item_comment';
    protected $guarded = array('id');

    function user() {
        return $this->belongsTo('Hand\Models\User');
    }

}
