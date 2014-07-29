<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class ItemTrade extends Eloquent\Model {

    protected $table   = 'item_trade';
    protected $guarded = array('id');

    public function item() {
        return $this->belongsTo('Hand\Models\Item');
    }

}
