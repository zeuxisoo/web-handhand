<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateMessageTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('message', function($table) {
            $table->increments('id');
            $table->integer('sender_id')->references('id')->on('user');
            $table->integer('receiver_id')->references('id')->on('user');
            $table->enum('category', ['normal', 'system'])->default('normal');
            $table->string('subject', 180);
            $table->text('content');
            $table->tinyInteger('have_read')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('message');
    }
}
