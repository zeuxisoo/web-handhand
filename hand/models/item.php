<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;
use Hand\Mixins;

class Item extends Eloquent\Model {

    protected $table   = 'item';
    protected $guarded = array('id');

    use Mixins\ItemRelation;

}
