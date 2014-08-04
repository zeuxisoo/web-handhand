<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class ItemComment extends Eloquent\Model {

    protected $table   = 'item_comment';
    protected $guarded = array('id');

    public function user() {
        return $this->belongsTo('Hand\Models\User');
    }

    public function scopeWhereItemId($query, $item_id) {
        return $query->where('item_id', $item_id);
    }

}
