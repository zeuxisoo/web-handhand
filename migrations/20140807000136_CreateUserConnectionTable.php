<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUserConnectionTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('user_connection', function($table) {
            $table->increments('id');
            $table->integer('user_id')->references('id')->on('user');
            $table->string('provider_name', 30);
            $table->string('provider_uid', 30);
            $table->string('email', 180);
            $table->string('display_name', 80)->nullable();
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('profile_url', 180)->nullable();
            $table->string('website_url', 180)->nullable();
            $table->string('photo_url', 180)->nullable();
            $table->text('tokens');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('user_connection');
    }
}
