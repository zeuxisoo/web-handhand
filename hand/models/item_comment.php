<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;
use Hand\Mixins;

class ItemComment extends Eloquent\Model {

    protected $table   = 'item_comment';
    protected $guarded = array('id');

    use Mixins\ItemRelation;

}
