<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class Item extends Eloquent\Model {

    protected $table   = 'item';
    protected $guarded = array('id');

    public function user() {
        return $this->belongsTo('Hand\Models\User');
    }

    public function images() {
        return $this->hasMany('Hand\Models\ItemImage');
    }

    public function comments() {
        return $this->hasMany('Hand\Models\ItemComment');
    }

    public function bookmarks() {
        return $this->hasMany('Hand\Models\ItemBookmark');
    }

    public function trade() {
        return $this->hasOne('Hand\Models\ItemTrade');
    }

}
