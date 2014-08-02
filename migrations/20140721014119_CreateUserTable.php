<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUserTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('user', function($table) {
            $table->increments('id');
            $table->string('username', 30)->unique();
            $table->string('email', 80)->unique();
            $table->string('password', 64);
            $table->enum('status', ['banned', 'inactive', 'active'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('user');
    }
}
