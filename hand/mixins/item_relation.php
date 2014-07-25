<?php
namespace Hand\Mixins;

use Illuminate\Database\Eloquent\Collection;
use Hand\Models\ItemImage;
use Hand\Models\User;

trait ItemRelation {

    public static function fillItemImage($items) {
        if ($items instanceof Collection) {
            $item_images = ItemImage::whereIn('item_id', $items->modelKeys())->get();

            foreach($items as $item) {
                $item->images = $item_images->filter(function($item_image) use ($item) {
                    return $item_image->item_id == $item->id;
                });
            }
        }else{
            $items->images = ItemImage::where('item_id', $items->id)->get();
        }

        return $items;
    }

    public static function fillUser($items) {
        if ($items instanceof Collection) {
            $users = User::whereIn('id', $items->fetch('user_id')->toArray())->get();

            foreach($items as $item) {
                $item->user = $users->filter(function($user) use ($item) {
                    return $user->id == $item->user_id;
                })->first();
            }
        }else{
            $items->user = User::find($items->user_id);
        }

        return $items;
    }

}
