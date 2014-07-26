<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateItemBookmarkTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('item_bookmark', function($table) {
            $table->increments('id');
            $table->integer('user_id')->references('id')->on('user');
            $table->integer('item_id')->references('id')->on('item');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('item_bookmark');
    }
}
