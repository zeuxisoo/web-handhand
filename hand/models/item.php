<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class Item extends Eloquent\Model {

    protected $table   = 'item';
    protected $guarded = array('id');

    public static function fillItemImage($items) {
        $item_images = ItemImage::whereIn('item_id', $items->modelKeys())->get();

        foreach($items as $item) {
            $item->images = $item_images->filter(function($item_image) use ($item) {
                return $item_image->item_id == $item->id;
            });
        }

        return $items;
    }

}
