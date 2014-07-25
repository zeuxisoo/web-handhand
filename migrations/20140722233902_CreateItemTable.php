<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateItemTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('item', function($table) {
            $table->increments('id');
            $table->integer('user_id')->references('id')->on('user');
            $table->string('title', 120);
            $table->tinyInteger('category');
            $table->tinyInteger('property');
            $table->text('description');
            $table->float('price');
            $table->tinyInteger('delivery');
            $table->enum('status', ['review', 'hide', 'publish', 'trade', 'done'])->default('hide');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('item');
    }
}
