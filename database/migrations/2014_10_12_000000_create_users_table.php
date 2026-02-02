<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 20)->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();

            $table->date('birth_date')->nullable();
            $table->enum('gender', ['Hombre', 'Mujer'])->nullable();
            $table->string('instagram')->nullable();

            $table->boolean('is_verified')->default(false);

            $table->timestamp('phone_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
