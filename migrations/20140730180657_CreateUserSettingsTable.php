<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUserSettingsTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('user_settings', function($table) {
            $table->increments('id');
            $table->integer('user_id')->references('id')->on('user');
            $table->tinyInteger('notify_trade')->default(1);
            $table->tinyInteger('notify_comment')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('user_settings');
    }
}
