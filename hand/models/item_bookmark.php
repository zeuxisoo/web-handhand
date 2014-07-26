<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class ItemBookmark extends Eloquent\Model {

    protected $table   = 'item_bookmark';
    protected $guarded = array('id');

    public function item() {
        return $this->belongsTo('Hand\Models\Item');
    }

    public function socpeWhereUserIdAndItemId($query, $user_id, $item_id) {
        return $this->where('user_id', $user_id)->where('item_id', $item_id);
    }

}
